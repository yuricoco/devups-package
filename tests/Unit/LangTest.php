<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * t($cle, $params = []) : traduction depuis lang/<iso2>.php (charge une
 * fois par requete dans lang.php), repli sur la cle si absente, aucune
 * requete SQL dans le chemin normal.
 */
final class LangTest extends TestCase
{
    private function langFile(string $iso): string
    {
        return ROOT . 'lang/' . $iso . '.php';
    }

    private function removeKeyFromAllLangFiles(string $cle): void
    {
        foreach (glob(ROOT . 'lang/*.php') ?: [] as $file) {
            $translations = require $file;
            if (!array_key_exists($cle, $translations))
                continue;
            unset($translations[$cle]);
            file_put_contents($file, "<?php\n\nreturn " . var_export($translations, true) . ";\n");
        }
    }

    public function testReturnsTheStoredTranslationForTheCurrentLanguage(): void
    {
        $this->assertSame('fr', local(), 'ce test suppose la langue par defaut (fr) des fixtures phpunit');
        $this->assertSame('Save', t('Save'), 'cle deja collectee par une execution precedente des tests e2e');
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

        $cle = 'cle_test_autocollect_' . uniqid();
        t($cle);

        foreach (glob(ROOT . 'lang/*.php') ?: [] as $file) {
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

        foreach (glob(ROOT . 'lang/*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertArrayNotHasKey($cle, $translations, "la cle ne doit JAMAIS etre ajoutee en prod ($file)");
        }
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
}
