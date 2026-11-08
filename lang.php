<?php
/**
 * Traductions : chaque langue a un fichier lang/<iso2>.php qui retourne
 * un tableau cle => traduction. Charge UNE SEULE FOIS par requete, pour
 * la langue courante (local(), meme reference iso2 que la session/l'URL/
 * la base - cf. Dvups_lang::iso_code). t() ne fait JAMAIS de requete SQL :
 * c'est un simple lookup dans ce tableau.
 */

global $dv_translations;
$dv_translations = [];

// local() ne renvoie plus jamais qu'un code valide (ETAPE H -
// dv_valid_lang()) ; dv_lang_file_path() ajoute un controle realpath en
// plus, par securite (voir dvups_functions.php).
$dv_lang_file = dv_lang_file_path(local());
if ($dv_lang_file && file_exists($dv_lang_file))
    $dv_translations = require $dv_lang_file;

/**
 * t($cle, $params = []) : traduction de $cle dans la langue courante, ou
 * $cle elle-meme si elle est absente (avec substitution :param dans les
 * deux cas). Quand LANG_COLLECT est actif (par defaut : dev uniquement,
 * jamais en prod - cf. config/constante.example.php), une cle absente est
 * ajoutee a tous les fichiers lang/*.php (valeur = la cle elle-meme) pour
 * que le traducteur n'ait plus qu'a les remplir.
 */
function t($cle, $params = [])
{
    global $dv_translations;

    if (array_key_exists($cle, $dv_translations)) {
        $translation = $dv_translations[$cle];
    } else {
        if (!defined('LANG_COLLECT') || LANG_COLLECT)
            dv_lang_collect_missing_key($cle);
        $translation = $cle;
    }

    // Les anciens appels t('cle', 'texte par defaut') passent une chaine
    // en 2e argument (ancienne signature) : on l'ignore plutot que de
    // planter, seul un tableau de parametres :nom est substitue.
    if (is_array($params)) {
        foreach ($params as $search => $value)
            $translation = str_replace(':' . $search, $value, $translation);
    }

    return $translation;
}

/**
 * Variante utilisee dans les vues (ancien bouton d'edition inline retire
 * avec l'ancien systeme de traduction en base, cf. TODO.md).
 */
function tt($cle)
{
    return t($cle);
}

/**
 * Variante de t() pour une langue EXPLICITE, independante de la session/
 * l'URL courante (local()) : un email envoye a un destinataire dont la
 * langue differe de celle de l'admin/l'utilisateur qui declenche l'envoi.
 * Mise en cache par langue (un seul require par iso et par requete).
 * Jamais de collecte automatique : une cle absente retourne simplement la
 * cle (la collecte dev reste le role de t(), dans la langue de la requete
 * courante).
 */
function t_lang($cle, string $iso, $params = [])
{
    global $dv_translations_by_lang;
    if (!isset($dv_translations_by_lang))
        $dv_translations_by_lang = [];

    // ETAPE H : $iso passe lui aussi par dv_valid_lang() (repli sur la
    // langue par defaut si invalide/inactive), jamais utilise brut pour
    // construire un chemin de fichier.
    $iso = dv_valid_lang($iso) ?? __default_lang;

    if (!array_key_exists($iso, $dv_translations_by_lang)) {
        $file = dv_lang_file_path($iso);
        $dv_translations_by_lang[$iso] = ($file && file_exists($file)) ? require $file : [];
    }

    $translation = $dv_translations_by_lang[$iso][$cle] ?? $cle;

    if (is_array($params)) {
        foreach ($params as $search => $value)
            $translation = str_replace(':' . $search, $value, $translation);
    }

    return $translation;
}

function p($ref, $default = "no_image")
{
    return "";
}

/**
 * Ajoute $cle (valeur = $cle elle-meme) a tous les fichiers lang/*.php ou
 * elle manque encore (sauf si LANG_COLLECT est coupe - cf. t()). Met
 * aussi a jour $dv_translations en memoire : un appel ulterieur a t() avec
 * la meme cle, dans la MEME requete, ne redeclenche pas un verrou/require
 * par fichier - il la trouve deja dans le tableau charge en memoire.
 */
function dv_lang_collect_missing_key(string $cle): void
{
    global $dv_translations;

    foreach (glob(LANG_DIR . '*.php') ?: [] as $file)
        dv_lang_write_missing_key($file, $cle);

    $dv_translations[$cle] = $cle;
}

/**
 * Ecriture surete d'une cle manquante dans un fichier de langue : verrou
 * exclusif sur le fichier, ecriture dans un fichier temporaire puis
 * renommage atomique (jamais d'ecriture directe qui laisserait un fichier
 * a moitie ecrit si deux requetes se chevauchent).
 */
function dv_lang_write_missing_key(string $file, string $cle): void
{
    // Verrou sur un fichier DEDIE (jamais sur $file lui-meme : le garder
    // ouvert puis le require() dans le meme souffle echoue sous Windows,
    // ou un fichier ouvert en ecriture ne peut pas etre relu ailleurs).
    $lock = @fopen($file . '.lock', 'c');
    if (!$lock)
        return;

    try {
        if (!flock($lock, LOCK_EX))
            return;

        $translations = file_exists($file) ? (require $file) : [];
        if (array_key_exists($cle, $translations))
            return;

        $translations[$cle] = $cle;
        ksort($translations);

        $tmp = $file . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, "<?php\n\nreturn " . var_export($translations, true) . ";\n");
        rename($tmp, $file);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
