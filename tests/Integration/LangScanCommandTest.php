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

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
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

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertSame($this->cle, $translations[$this->cle] ?? null, "lang:scan doit ajouter la cle dans $file");
        }
    }

    /**
     * ETAPE P.7 : le tokenizer (token_get_all(), pas une regex) gere
     * correctement l'apostrophe echappee (\') a l'interieur d'une cle
     * litterale - sans quoi la cle serait tronquee juste avant
     * l'echappement (c'est ce qui avait laisse 'Aucun élément pour l\\'
     * dans lang/en.php et lang/fr.php, retire par ce meme commit).
     */
    public function testScanHandlesAnEscapedApostropheInsideTheKey(): void
    {
        $this->cle = "cle_test_lang_scan_apostrophe_" . uniqid() . "_l'instant";
        $this->probeFile = ROOT . 'web/views/_lang_scan_probe_' . uniqid() . '.blade.php';
        file_put_contents($this->probeFile, "<?php echo t('" . addslashes($this->cle) . "'); ?>");

        $output = (string)shell_exec('cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups lang:scan 2>&1');
        $this->assertStringContainsString('cle(s) trouvee(s)', $output);

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertSame($this->cle, $translations[$this->cle] ?? null, "la cle complete (avec l'apostrophe) doit etre ajoutee dans $file");

            $truncated = substr($this->cle, 0, strpos($this->cle, "'")) . '\\';
            foreach (array_keys($translations) as $existing)
                $this->assertFalse(str_starts_with($existing, $truncated), "aucune cle tronquee ($existing) avant l'apostrophe echappee ne doit etre ajoutee dans $file");
        }
    }

    /**
     * E.4 : une cle CONSTRUITE (concatenation) n'est jamais ajoutee
     * automatiquement - juste signalee en sortie, puisque le tokenizer ne
     * peut pas en connaitre la valeur sans executer le code.
     */
    public function testScanIgnoresAConcatenatedKeyAndReportsIt(): void
    {
        $fragment = 'fragment_concat_' . uniqid();
        $this->probeFile = ROOT . 'web/views/_lang_scan_probe_' . uniqid() . '.blade.php';
        file_put_contents($this->probeFile, "<?php echo t('" . $fragment . "' . \$suffix); ?>");

        $output = (string)shell_exec('cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups lang:scan 2>&1');

        $this->assertStringContainsString('appel(s) ignore(s)', $output);
        $this->assertStringContainsString($fragment, $output);

        foreach (glob(LANG_DIR . '*.php') ?: [] as $file) {
            $translations = require $file;
            $this->assertArrayNotHasKey($fragment, $translations, "une cle construite ne doit jamais etre ajoutee ($file)");
        }
    }
}
