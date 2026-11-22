<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE 5.2 (v8.0) - LOT 9 : quand le generateur recoit un $component/
 * $module (desormais fournis par __Generator::__entity() via
 * self::$projectcore->name / self::$modulecore->name), chaque classe
 * generee (Entity/Controller/Datatable/Form) doit porter sa propre
 * declaration "namespace" et son class_alias() de compatibilite - voir
 * TODO.md "LOT 9". Sans $component/$module (valeur par defaut null,
 * comme avant ce lot), le comportement doit rester inchange : aucun
 * namespace, aucun alias - voir GeneratorRequiredRelationNullableFalseTest
 * qui verifie deja ce chemin pour entityGenerator().
 */
final class GeneratorNamespaceEmissionTest extends TestCase
{
    private string $workdir;
    private string $cwdBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cwdBefore = getcwd();
        $this->workdir = sys_get_temp_dir() . '/lot9_namespace_' . uniqid();
        foreach (['Entity', 'Controller', 'Datatable', 'Form'] as $dir)
            mkdir($this->workdir . '/' . $dir, 0777, true);
        chdir($this->workdir);
    }

    protected function tearDown(): void
    {
        chdir($this->cwdBefore);
        foreach (['Entity', 'Controller', 'Datatable', 'Form'] as $dir)
            foreach (glob($this->workdir . '/' . $dir . '/*') ?: [] as $path)
                unlink($path);
        foreach (['Entity', 'Controller', 'Datatable', 'Form'] as $dir)
            rmdir($this->workdir . '/' . $dir);
        rmdir($this->workdir);
        parent::tearDown();
    }

    private function entity(): object
    {
        return (object)[
            'name' => 'Widget',
            'attribut' => [],
            'relation' => [],
        ];
    }

    public function testEntityGeneratorEmitsNamespaceAndAliasWhenComponentAndModuleGiven(): void
    {
        (new \BackendGenerator())->entityGenerator($this->entity(), 'devups', 'ModuleDemo');

        $contenu = file_get_contents($this->workdir . '/Entity/Widget.php');

        $this->assertStringContainsString("namespace devups\\ModuleDemo\\Entity;", $contenu);
        $this->assertStringContainsString("class_alias(Widget::class, 'Widget');", $contenu);
    }

    public function testEntityGeneratorOmitsNamespaceAndAliasWithoutComponentAndModule(): void
    {
        (new \BackendGenerator())->entityGenerator($this->entity());

        $contenu = file_get_contents($this->workdir . '/Entity/Widget.php');

        $this->assertStringNotContainsString('namespace ', $contenu);
        $this->assertStringNotContainsString('class_alias', $contenu);
    }

    public function testControllerGeneratorEmitsNamespaceAndAlias(): void
    {
        (new \BackendGenerator())->controllerGenerator($this->entity(), false, 'devups', 'ModuleDemo');

        $contenu = file_get_contents($this->workdir . '/Controller/WidgetController.php');

        $this->assertStringContainsString("namespace devups\\ModuleDemo\\Controller;", $contenu);
        $this->assertStringContainsString("class_alias(WidgetController::class, 'WidgetController');", $contenu);
    }

    public function testFrontControllerGeneratorEmitsNamespaceAndAlias(): void
    {
        (new \BackendGenerator())->controllerGenerator($this->entity(), true, 'devups', 'ModuleDemo');

        $contenu = file_get_contents($this->workdir . '/Controller/WidgetFrontController.php');

        $this->assertStringContainsString("namespace devups\\ModuleDemo\\Controller;", $contenu);
        $this->assertStringContainsString("class_alias(WidgetFrontController::class, 'WidgetFrontController');", $contenu);
    }

    public function testTableGeneratorEmitsNamespaceAndAlias(): void
    {
        (new \BackendGenerator())->tableGenerator($this->entity(), [], 'devups', 'ModuleDemo');

        $contenu = file_get_contents($this->workdir . '/Datatable/WidgetTable.php');

        $this->assertStringContainsString("namespace devups\\ModuleDemo\\Datatable;", $contenu);
        $this->assertStringContainsString("class_alias(WidgetTable::class, 'WidgetTable');", $contenu);
    }

    public function testFormGeneratorEmitsNamespaceAndAlias(): void
    {
        (new \BackendGenerator())->formGenerator($this->entity(), [], 'devups', 'ModuleDemo');

        $contenu = file_get_contents($this->workdir . '/Form/WidgetForm.php');

        $this->assertStringContainsString("namespace devups\\ModuleDemo\\Form;", $contenu);
        $this->assertStringContainsString("class_alias(WidgetForm::class, 'WidgetForm');", $contenu);
    }
}
