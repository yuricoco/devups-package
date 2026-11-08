<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * t($cle, $params = []) : traduction depuis lang/<iso2>.php (charge une
 * fois par requete dans lang.php), repli sur la cle si absente, aucune
 * requete SQL dans le chemin normal. t_lang($cle, $iso, $params) : meme
 * mecanisme pour une langue EXPLICITE, independante de la session.
 *
 * Toutes les fixtures sont creees/nettoyees par le test lui-meme (jamais
 * de dependance a une cle deja collectee par l'e2e ou une run precedente :
 * LANG_DIR pointe vers un dossier temporaire, propre a cette execution,
 * mais partage par toute la suite - l'ordre d'execution ne doit donc pas
 * compter).
 */
final class LangTest extends TestCase
{
    private function langFile(string $iso): string
    {
        return LANG_DIR . $iso . '.php';
    }

    private function removeKeyFromAllLangFiles(string $cle): void
    {
        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            if (!array_key_exists($cle, $translations))
                continue;
            unset($translations[$cle]);
            file_put_contents($file, "<?php\n\nreturn " . var_export($translations, true) . ";\n");
        }
    }

    public function testReturnsTheStoredTranslationForTheCurrentLanguage(): void
    {
        // injecte directement dans $dv_translations (charge une seule fois
        // par lang.php au demarrage) plutot que de dependre d'une cle deja
        // presente dans un fichier lang/*.php : fixture auto-suffisante.
        global $dv_translations;
        $cle = 'cle_test_lookup_' . uniqid();
        $dv_translations[$cle] = 'valeur traduite de test';

        $this->assertSame('valeur traduite de test', t($cle));

        unset($dv_translations[$cle]);
    }

    public function testSubstitutesNamedParams(): void
    {
        $this->assertSame('bonjour Jean', t('bonjour :nom', ['nom' => 'Jean']));
        $this->removeKeyFromAllLangFiles('bonjour :nom');
    }

    public function testFallsBackToTheKeyWhenMissing(): void
    {
        $cle = 'cle_test_absente_' . uniqid();
        $this->assertSame($cle, t($cle));
        $this->removeKeyFromAllLangFiles($cle);
    }

    public function testFallbackAlsoSubstitutesParams(): void
    {
        $cle = 'cle absente :nom ' . uniqid();
        $this->assertSame(str_replace(':nom', 'Paul', $cle), t($cle, ['nom' => 'Paul']));
        $this->removeKeyFromAllLangFiles($cle);
    }

    public function testIgnoresALegacyStringSecondArgumentInsteadOfCrashing(): void
    {
        // ancienne signature t($cle, $default) des datatables generees :
        // ne doit jamais planter, juste etre ignoree (pas de substitution).
        $cle = 'cle_test_legacy_' . uniqid();
        $this->assertSame($cle, @t($cle, 'ancien texte par defaut'));
        $this->removeKeyFromAllLangFiles($cle);
    }

    public function testMissingKeyIsAutoCollectedInAllLanguageFilesInDev(): void
    {
        $this->assertFalse((bool)(defined('__prod') && __prod), 'ce test suppose un environnement non-prod (APP_PROD=false dans phpunit.xml.dist)');
        $this->assertTrue((bool)(defined('LANG_COLLECT') ? LANG_COLLECT : true), 'ce test suppose LANG_COLLECT actif (defaut)');

        $cle = 'cle_test_autocollect_' . uniqid();
        t($cle);

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertSame($cle, $translations[$cle] ?? null, "la cle doit etre ajoutee dans $file");
        }

        $this->removeKeyFromAllLangFiles($cle);
    }

    public function testMissingKeyIsNeverAutoCollectedInProd(): void
    {
        $cle = 'cle_test_prod_' . uniqid();

        $script = realpath(__DIR__ . '/../Support/scripts/trigger_lang.php');
        $env = getenv();
        $env['APP_PROD'] = '1';

        $process = proc_open(
            [PHP_BINARY, $script, $cle],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath(__DIR__ . '/../..'),
            $env
        );
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertArrayNotHasKey($cle, $translations, "la cle ne doit JAMAIS etre ajoutee en prod ($file)");
        }
    }

    public function testMissingKeyIsNeverAutoCollectedWhenLangCollectIsOff(): void
    {
        $cle = 'cle_test_collect_off_' . uniqid();

        $script = realpath(__DIR__ . '/../Support/scripts/trigger_lang.php');
        $env = getenv();
        $env['LANG_COLLECT'] = 'false';

        $process = proc_open(
            [PHP_BINARY, $script, $cle],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath(__DIR__ . '/../..'),
            $env
        );
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertArrayNotHasKey($cle, $translations, "E.1 : LANG_COLLECT=false doit couper la collecte independamment de __prod ($file)");
        }
    }

    public function testCollectedKeyIsAddedToInMemoryTranslationsWithoutARereadForTheSameKey(): void
    {
        // E.3 : apres une premiere collecte, la cle est trouvee directement
        // dans $dv_translations - verifie en supprimant le fichier lang
        // courant *apres* le premier appel : un second appel qui redeviendrait
        // sur le disque echouerait (fichier absent), celui qui reste en
        // memoire renvoie toujours la meme valeur.
        global $dv_translations;
        $cle = 'cle_test_memoire_' . uniqid();

        t($cle);
        $this->assertArrayHasKey($cle, $dv_translations, 'E.3 : la cle collectee doit etre ajoutee a $dv_translations en memoire');
        $this->assertSame($cle, t($cle), 'un second appel doit utiliser la valeur en memoire, pas re-lire/re-verrouiller le fichier');

        $this->removeKeyFromAllLangFiles($cle);
        unset($dv_translations[$cle]);
    }

    public function testTFunctionNeverQueriesTheDatabase(): void
    {
        // t() doit rester un simple lookup memoire (lang.php charge le
        // fichier une fois par requete) : aucune reference a une classe
        // ORM/PDO ne doit apparaitre dans son code source.
        $reflection = new \ReflectionFunction('t');
        $file = $reflection->getFileName();
        $lines = array_slice(
            file($file),
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        );
        $source = implode('', $lines);

        foreach (['::where(', '::find(', 'PDO', '->query(', '::create('] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source, "t() ne doit jamais faire d'acces base ($forbidden)");
        }
    }

    public function testTLangReadsAnExplicitLanguageIndependentlyOfTheCurrentSession(): void
    {
        $this->assertSame('fr', local(), 'ce test suppose la langue de session par defaut (fr)');

        $cle = 'cle_test_t_lang_' . uniqid();
        foreach (['fr' => 'valeur fr', 'en' => 'valeur en'] as $iso => $valeur) {
            $translations = require $this->langFile($iso);
            $translations[$cle] = $valeur;
            file_put_contents($this->langFile($iso), "<?php\n\nreturn " . var_export($translations, true) . ";\n");
        }

        // la session courante est 'fr', mais t_lang('en', ...) doit
        // retourner la version anglaise malgre tout.
        $this->assertSame('valeur en', t_lang($cle, 'en'));
        $this->assertSame('valeur fr', t_lang($cle, 'fr'));

        $this->removeKeyFromAllLangFiles($cle);
    }

    public function testTLangSubstitutesParamsAndFallsBackToTheKeyWhenMissing(): void
    {
        $cle = 'cle absente t_lang :nom ' . uniqid();
        $this->assertSame(str_replace(':nom', 'Alice', $cle), t_lang($cle, 'en', ['nom' => 'Alice']));
    }

    public function testTLangNeverAutoCollectsAMissingKey(): void
    {
        $cle = 'cle_test_t_lang_no_collect_' . uniqid();
        t_lang($cle, 'en');

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertArrayNotHasKey($cle, $translations, 't_lang() ne doit jamais collecter (seul t() le fait, dans la langue de la requete courante)');
        }
    }
}
