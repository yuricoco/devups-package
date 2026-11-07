<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * php devups lang:scan : trouve les appels t('...') du code et complete
 * les fichiers lang/*.php avec les cles manquantes (valeur = la cle).
 */
final class LangScanCommandTest extends DatabaseTestCase
{
    private ?string $probeFile = null;
    private string $cle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cle = 'cle_test_lang_scan_' . uniqid();
    }

    protected function tearDown(): void
    {
        if ($this->probeFile && file_exists($this->probeFile))
            unlink($this->probeFile);

        foreach (glob(ROOT . 'lang/*.php') ?: [] as $file) {
            $translations = require $file;
            if (!array_key_exists($this->cle, $translations))
                continue;
            unset($translations[$this->cle]);
            file_put_contents($file, "<?php\n\nreturn " . var_export($translations, true) . ";\n");
        }

        parent::tearDown();
    }

    public function testScanFindsALiteralKeyAndCompletesLangFiles(): void
    {
        // t() scanne par le CLI sous un repertoire reellement parcouru
        // (web/views), via un fichier jetable nettoye en tearDown().
        $this->probeFile = ROOT . 'web/views/_lang_scan_probe_' . uniqid() . '.blade.php';
        file_put_contents($this->probeFile, "<?php echo t('" . $this->cle . "'); ?>");

        $output = shell_exec('cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups lang:scan 2>&1');

        $this->assertStringContainsString('cle(s) trouvee(s)', (string)$output);

        foreach (glob(ROOT . 'lang/*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertSame($this->cle, $translations[$this->cle] ?? null, "lang:scan doit ajouter la cle dans $file");
        }
    }
}
