<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE D.7 (v7.1) - defaut D7 (tome4.md) : regenerer une entite deux fois
 * de suite (`core:g:crud` sans `--rewrite`) duplique son bloc de require
 * (Entity/Form/Datatable/Controller) dans le fichier de dependances du
 * module (`<projet>.<module>.php`) - __Generator::dependencies() passe en
 * mode "a+" (append pur, sans aucune verification de presence) dans la
 * seule branche prise quand ce fichier existe deja.
 *
 * Reproduit directement __Generator::dependencies() (methode privee,
 * appelee via Reflection) dans un dossier temporaire isole, sans passer
 * par la chaine complete findentity()/projet/base de donnees.
 */
final class GeneratorDependenciesNoDuplicateRequireTest extends TestCase
{
    private string $workdir;
    private string $cwdBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cwdBefore = getcwd();
        $this->workdir = sys_get_temp_dir() . '/etape_d7_' . uniqid();
        mkdir($this->workdir, 0777, true);
        mkdir($this->workdir . '/ModuleTest', 0777, true);
        chdir($this->workdir);
    }

    protected function tearDown(): void
    {
        chdir($this->cwdBefore);
        $this->removeDir($this->workdir);
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $path) {
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function callDependencies($project, $module, $entity): void
    {
        $method = new \ReflectionMethod(\__Generator::class, 'dependencies');
        $method->setAccessible(true);
        $method->invoke(null, $project, $module, $entity);
    }

    public function testRegeneratingTheSameEntityTwiceDoesNotDuplicateItsRequireBlock(): void
    {
        $project = (object)['name' => 'TestProject'];
        $module = (object)['name' => 'ModuleTest'];
        $entity = (object)['name' => 'Widget', 'relation' => []];

        $this->callDependencies($project, $module, $entity);
        $this->callDependencies($project, $module, $entity);

        $contenu = file_get_contents($this->workdir . '/ModuleTest/testproject.moduletest.php');

        $this->assertSame(
            1,
            substr_count($contenu, "require 'Entity/Widget.php';"),
            "regenerer la meme entite deux fois ne doit pas dupliquer son bloc require"
        );
    }
}
