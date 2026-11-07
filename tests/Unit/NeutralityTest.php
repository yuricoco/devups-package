<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Le framework ne doit embarquer aucune reference a un projet client.
 * Ajouter ici tout terme propre a une application passee par le depot.
 */
final class NeutralityTest extends TestCase
{
    private const FORBIDDEN = [
        '3ag', 'comicbook', 'kolacoin', 'littleshopp', 'bgfi', 'reader3ag',
        'GiftDistributor', 'Employee::', 'App_role', 'aiMemory',
    ];

    private const SCANNED = ['dclass', 'src', 'route', 'config', 'admin/views', 'admin/generator', 'web/views', 'documentation'];

    public function testNoClientProjectReference(): void
    {
        $root = realpath(__DIR__ . '/../..');
        $hits = [];
        foreach (self::SCANNED as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/' . $dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!preg_match('/\.(php|js|json|html)$/', $file->getFilename()))
                    continue;
                $rel = substr($file->getPathname(), strlen($root) + 1);
                // fichiers locaux, hors depot
                if (in_array($rel, ['config/constante.php'], true))
                    continue;
                $content = file_get_contents($file->getPathname());
                foreach (self::FORBIDDEN as $term) {
                    if (stripos($content, $term) !== false)
                        $hits[] = "$rel : $term";
                }
            }
        }
        $this->assertSame([], $hits, "References a un projet client :\n" . implode("\n", $hits));
    }
}
