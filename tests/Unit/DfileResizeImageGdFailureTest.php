<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE A.3 (v7.1) : Dfile::resizeimage() appelait imagealphablending()
 * sur le resultat de "@imagecreatefrompng($source_url)" sans jamais
 * verifier qu'il avait reussi - un fichier source que GD ne sait pas
 * decoder (PNG corrompu/tronque, cas limite d'une image 1x1 pixel sur
 * certains environnements, voir TODO.md ETAPE V et tuto_doc/source/tome4.md
 * dette D15) faisait echouer imagecreatefrompng() (false), et
 * imagealphablending(false, ...) levait alors un TypeError PHP8
 * ("Argument #1 ($image) must be of type GdImage, bool given") - 500
 * fatal au lieu de garder le fichier original deja enregistre et de
 * renoncer silencieusement (hors journalisation) a la seule variante
 * redimensionnee.
 *
 * Reproduit directement (reflection sur la methode privee resizeimage())
 * avec un fichier ".png" delibere corrompu - fiable sur toute machine,
 * contrairement a une vraie image 1x1 dont l'echec GD depend de la build
 * libpng locale (voir CmsresourceImageUploadTest::
 * testUploadingAOnePixelImageSucceedsAndKeepsTheOriginal, qui exerce le
 * meme chemin de bout en bout mais ne peut pas forcer l'echec GD).
 */
final class DfileResizeImageGdFailureTest extends TestCase
{
    private string $uploadSubdir = 'test_a3_resize_regression/';
    private ?string $corruptPng = null;

    protected function tearDown(): void
    {
        $dir = UPLOAD_DIR . $this->uploadSubdir;
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
        if ($this->corruptPng && file_exists($this->corruptPng)) {
            @unlink($this->corruptPng);
        }
        \Model::pdo()->exec("DELETE FROM dvups_log WHERE message LIKE 'resizeimage(): imagecreatefrompng%'");
        parent::tearDown();
    }

    private function callResizeimage(\Dfile $dfile, string $sourceUrl, array $param): void
    {
        $method = new \ReflectionMethod(\Dfile::class, 'resizeimage');
        $method->setAccessible(true);
        $method->invoke($dfile, $sourceUrl, $param);
    }

    private function setPrivate(\Dfile $dfile, string $property, $value): void
    {
        $prop = new \ReflectionProperty(\Dfile::class, $property);
        $prop->setAccessible(true);
        $prop->setValue($dfile, $value);
    }

    public function testAnUndecodablePngDoesNotCrashAndIsLogged(): void
    {
        $this->corruptPng = tempnam(sys_get_temp_dir(), 'dvups_corrupt_') . '.png';
        file_put_contents($this->corruptPng, "ceci n'est pas un PNG valide");

        $dfile = new \Dfile(null);
        $dfile->extension = 'png';
        $this->setPrivate($dfile, 'file_name', 'corrupt.png');
        $this->setPrivate($dfile, 'imagesize', [10, 10]);
        $this->setPrivate($dfile, 'compressionquality', 80);

        \Model::pdo()->exec("DELETE FROM dvups_log WHERE message LIKE 'resizeimage(): imagecreatefrompng%'");

        // N'echoue pas (aucune exception/erreur fatale ne remonte).
        $this->callResizeimage($dfile, $this->corruptPng, [
            'resize' => [150, 150], 'sufix' => 'test_', 'uploaddir' => $this->uploadSubdir, 'crop' => true, 'quality' => 80,
        ]);

        // Aucun fichier redimensionne n'a ete produit (variante abandonnee).
        $this->assertFileDoesNotExist(UPLOAD_DIR . $this->uploadSubdir . 'test_corrupt.png');

        $logged = \Model::pdo()->query(
            "SELECT message FROM dvups_log WHERE level = 'warning' AND message LIKE 'resizeimage(): imagecreatefrompng%' ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        $this->assertNotFalse($logged, "l'echec GD doit etre journalise via Log::warning(), pas avale en silence");
    }

    public function testAnUndecodableJpegDoesNotCrashAndIsLogged(): void
    {
        $corruptJpg = tempnam(sys_get_temp_dir(), 'dvups_corrupt_') . '.jpg';
        file_put_contents($corruptJpg, "ceci n'est pas un JPEG valide");

        $dfile = new \Dfile(null);
        $dfile->extension = 'jpg';
        $this->setPrivate($dfile, 'file_name', 'corrupt.jpg');
        $this->setPrivate($dfile, 'imagesize', [10, 10]);
        $this->setPrivate($dfile, 'compressionquality', 80);

        \Model::pdo()->exec("DELETE FROM dvups_log WHERE message LIKE 'resizeimage(): imagecreatefromjpeg%'");

        $this->callResizeimage($dfile, $corruptJpg, [
            'resize' => [150, 150], 'sufix' => 'test_', 'uploaddir' => $this->uploadSubdir, 'crop' => true, 'quality' => 80,
        ]);
        @unlink($corruptJpg);

        $logged = \Model::pdo()->query(
            "SELECT message FROM dvups_log WHERE level = 'warning' AND message LIKE 'resizeimage(): imagecreatefromjpeg%' ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        $this->assertNotFalse($logged, "l'echec GD doit etre journalise via Log::warning(), pas avale en silence");

        \Model::pdo()->exec("DELETE FROM dvups_log WHERE message LIKE 'resizeimage(): imagecreatefromjpeg%'");
    }
}
