<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE F.3 (v7.1) - non-regression CI : `cache/` est entierement ignore
 * par git (.gitignore, `/cache/`) - un clone neuf (celui de la CI) n'a
 * donc aucun de ses sous-dossiers, y compris `cache/views`. Avant ce
 * correctif, le premier rendu Blade d'un tel clone echouait avec
 * `file_put_contents(.../cache/views/xxx.php): Failed to open stream`
 * (AdminAuthTest, AdminScreensTest, ReportingmodelCollectTest... sur la
 * CI) - `php devups install`/`init:caches` le cree, mais seule la CI ne
 * l'appelle jamais avant `composer test`.
 *
 * `BladeD::__construct()` (Genesis.php, point d'entree UNIQUE de toute
 * instanciation de Blade dans ce framework) cree desormais `cache/views`
 * elle-meme si absent, avant d'initialiser Blade.
 */
final class BladeViewCacheAutoCreateTest extends TestCase
{
    private const CACHE_DIR = ROOT . 'cache/views';

    protected function tearDown(): void
    {
        // laisse le dossier en place (recree par tout rendu normal) -
        // seul son ABSENCE prealable est le scenario teste.
        parent::tearDown();
    }

    public function testRenderingAViewAfterDeletingTheCacheDirectoryNeverErrors(): void
    {
        self::deleteDir(self::CACHE_DIR);
        $this->assertDirectoryDoesNotExist(self::CACHE_DIR, 'precondition : le dossier doit etre absent, pas seulement vide');

        $html = \Genesis::getView('404');

        $this->assertDirectoryExists(self::CACHE_DIR, "BladeD doit avoir recree cache/views elle-meme, sans passer par 'php devups install'");
        $this->assertIsString($html);
        $this->assertStringContainsString('404', $html);
    }

    private static function deleteDir(string $dir): void
    {
        if (!is_dir($dir))
            return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? self::deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
