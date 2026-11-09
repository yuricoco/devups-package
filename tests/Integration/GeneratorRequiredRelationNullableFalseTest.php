<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE D.6 (v7.1) - defaut D6 (tome4.md) : une relation declaree "not"
 * nullable dans le datacore (@ManyToOne/@OneToOne obligatoire) ne se
 * repercutait jamais dans l'annotation Doctrine generee par
 * BackendGenerator::entityGenerator() - @JoinColumn ne portait que
 * "onDelete", jamais "nullable=false" : le schema genere par Doctrine
 * acceptait alors NULL sur une relation censee etre obligatoire.
 */
final class GeneratorRequiredRelationNullableFalseTest extends TestCase
{
    private string $workdir;
    private string $cwdBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cwdBefore = getcwd();
        $this->workdir = sys_get_temp_dir() . '/etape_d6_' . uniqid();
        mkdir($this->workdir . '/Entity', 0777, true);
        chdir($this->workdir);
    }

    protected function tearDown(): void
    {
        chdir($this->cwdBefore);
        foreach (glob($this->workdir . '/Entity/*') ?: [] as $path)
            unlink($path);
        rmdir($this->workdir . '/Entity');
        rmdir($this->workdir);
        parent::tearDown();
    }

    public function testARequiredManyToOneRelationGetsNullableFalse(): void
    {
        $entity = (object)[
            'name' => 'Widget',
            'attribut' => [],
            'relation' => [
                (object)['entity' => 'parent', 'cardinality' => 'manyToOne', 'nullable' => 'not', 'ondelete' => 'cascade'],
                (object)['entity' => 'optionalfoo', 'cardinality' => 'manyToOne', 'nullable' => 'default', 'ondelete' => 'cascade'],
            ],
        ];

        (new \BackendGenerator())->entityGenerator($entity);

        $contenu = file_get_contents($this->workdir . '/Entity/Widget.php');

        $this->assertMatchesRegularExpression(
            '/@JoinColumn\(onDelete="cascade", nullable=false\)\s*\n\s*\* @var .*Parent/',
            $contenu,
            "une relation 'not' nullable doit produire nullable=false"
        );
        $this->assertDoesNotMatchRegularExpression(
            '/@JoinColumn\(onDelete="cascade", nullable=false\)\s*\n\s*\* @var .*Optionalfoo/',
            $contenu,
            "une relation 'default' nullable ne doit pas produire nullable=false"
        );
    }
}
