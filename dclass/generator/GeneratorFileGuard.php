<?php

/**
 * Garde par renommage pour toute ecriture du
 * generateur (`core:g:module`/`core:g:crud` et consorts) sur un fichier deja
 * present. Avant ce correctif, chaque site d'ecriture faisait
 * `fopen(..., 'w')` sans jamais verifier l'existant - un Controller/Form/
 * Datatable/Entity ecrit a la main (actions metier, requetes ajustees)
 * etait perdu sans avertissement des qu'on relancait le generateur dessus.
 *
 * write() renomme l'ancien fichier EN PLACE (meme dossier, suffixe
 * horodate qui change l'extension loin de '.php'/'.json') avant d'ecrire le
 * neuf. Mesure, pas supposee : aucun `glob()` n'existe dans dclass/ ou
 * admin/ ; le seul `scandir()` sans filtre d'extension est
 * Core::findentitycore() sur le dossier Core/ (lit tout fichier qui n'est
 * pas un dossier comme un Core.json) - d'ou writeEntityCoreJson() ci-dessous,
 * seul cas qui renomme dans un sous-dossier plutot qu'en place. Composer
 * (composer.json : "classmap": ["dclass/", "src/"]) ne retient que les
 * fichiers finissant en '.php' - un renomme en '.old' lui est invisible.
 *
 * rename() et mkdir() PEUVENT echouer (fichier
 * ouvert ailleurs - cas normal sous Windows, projet ouvert dans un editeur
 * pendant que le generateur tourne) et leur valeur de retour doit toujours
 * etre verifiee : une garde qui continue sur un rename() rate ecraserait
 * l'original via le file_put_contents() suivant, en plus de mentir dans
 * $renamed en y inscrivant un chemin "old" qui ne contient pas l'ancien
 * contenu. En cas d'echec : rien n'est ecrit pour CE fichier (les autres du
 * meme run continuent), $failed journalise pourquoi, et un message est
 * imprime immediatement (pas seulement dans printSummary(), pour rester
 * visible meme si un run plante avant la fin).
 */
class GeneratorFileGuard
{
    /** @var bool --force : ecrase sans renommer, comme schema:update --force (builder.php) */
    public static $force = false;

    /** @var array{new:string,old:string}[] renommages reellement reussis de ce run */
    public static $renamed = [];

    /** @var array{path:string,reason:string}[] fichiers refuses (rien ecrit) de ce run */
    public static $failed = [];

    /**
     * Ecrit $content dans $path. Si $path existe deja et que --force n'est
     * pas actif, renomme l'ancien AVANT d'ecrire. Si le renommage echoue,
     * n'ecrit rien et retourne false - l'appelant peut ignorer la valeur de
     * retour (le echec est deja signale sur la sortie standard et dans
     * self::$failed) mais une future verification explicite reste possible.
     *
     * @return bool true si ecrit, false si refuse (echec de renommage)
     */
    public static function write($path, $content)
    {
        if (!self::renameIfPresent($path)) {
            return false;
        }
        file_put_contents($path, $content);
        return true;
    }

    /**
     * Meme garde, mais pour les Core.json d'ENTITE (dossier Core/) : l'ancien
     * est deplace dans un sous-dossier Core/_old/, jamais a cote du neuf.
     *
     * La vraie raison pour laquelle un sous-dossier
     * est sans danger aujourd'hui, corrigee (celle du commit precedent etait
     * fausse - verifiee en relisant Core::findentitycore(), pas supposee) :
     * CETTE fonction n'a PAS de test is_dir() dans sa branche a callback
     * (celle utilisee par Dvups_role::updateConfigs(), le chemin role-scope)
     * - un sous-dossier comme _old y est bel et bien passe au callback tel
     * quel, comme n'importe quel nom de fichier. Ce qui le neutralise, c'est
     * le callback lui-meme : celui de Dvups_role::updateConfigs()
     * (src/devups/ModuleAdmin/Entity/Dvups_role.php:137-138) fait
     * in_array(strtolower($core), $ents), toujours faux pour '_old' puisque
     * ce n'est le nom d'aucune entite du role, donc retourne null -
     * accidentellement sans danger, pas protege par construction. Le
     * is_dir() manquant est corrige directement dans Core::findentitycore()
     * (dclass/devups/Core.php) : un dossier n'est pas une entite, dans
     * aucune des deux branches, independamment de ce qu'un callback ferait.
     */
    public static function writeEntityCoreJson($path, $content)
    {
        if (file_exists($path) && !self::$force) {
            $dir = dirname($path);
            $oldDir = $dir . '/_old';
            if (!is_dir($oldDir)) {
                if (!@mkdir($oldDir, 0777, true) && !is_dir($oldDir)) {
                    self::reportFailure($path, "impossible de creer le dossier $oldDir");
                    return false;
                }
            }
            $old = self::uniquePath($oldDir . '/' . basename($path));
            if (!@rename($path, $old)) {
                self::reportFailure($path, "rename() a echoue vers $old (fichier probablement ouvert ailleurs)");
                return false;
            }
            self::$renamed[] = ['new' => $path, 'old' => $old];
        }
        file_put_contents($path, $content);
        return true;
    }

    private static function renameIfPresent($path)
    {
        if (!file_exists($path)) {
            return true;
        }
        if (self::$force) {
            return true;
        }
        $old = self::uniquePath($path);
        if (!@rename($path, $old)) {
            self::reportFailure($path, "rename() a echoue vers $old (fichier probablement ouvert ailleurs)");
            return false;
        }
        self::$renamed[] = ['new' => $path, 'old' => $old];
        return true;
    }

    private static function reportFailure($path, $reason)
    {
        self::$failed[] = ['path' => $path, 'reason' => $reason];
        echo "\n[GeneratorFileGuard] ECHEC : $path n'a pas pu etre protege ($reason) - RIEN n'est ecrit pour ce fichier, l'original est intact.\n";
    }

    private static function uniquePath($path)
    {
        $suffix = date('Y-m-d_His');
        $candidate = $path . '.' . $suffix . '.old';
        $i = 2;
        while (file_exists($candidate)) {
            $candidate = $path . '.' . $suffix . '_' . $i . '.old';
            $i++;
        }
        return $candidate;
    }

    /**
     * A appeler une fois, a la fin d'une commande core:g:*, pour que le
     * developpeur sache ou aller recuperer son code - et ce qui a ete
     * refuse, s'il y a lieu.
     */
    public static function printSummary()
    {
        if (self::$renamed) {
            echo "\n\nFichiers renommes avant ecriture (ancien -> nouveau emplacement) :\n";
            foreach (self::$renamed as $r) {
                echo "  " . $r['new'] . "\n    -> " . $r['old'] . "\n";
            }
        }
        if (self::$failed) {
            echo "\n\nECHECS - rien n'a ete ecrit pour ces fichiers, verifiez qu'ils ne sont pas ouverts ailleurs :\n";
            foreach (self::$failed as $f) {
                echo "  " . $f['path'] . " : " . $f['reason'] . "\n";
            }
        }
    }
}
