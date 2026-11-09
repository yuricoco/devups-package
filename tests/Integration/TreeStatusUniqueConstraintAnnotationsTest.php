<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE A.5 (v7.1) : complement de TreeStatusUniqueConstraintsMigrationTest
 * (qui prouve le comportement de la MIGRATION sur une base dediee, avant
 * application). Ici, sur la base de test PRINCIPALE - dont le schema est
 * reconstruit a chaque run directement depuis les annotations
 * (tests/bootstrap.php : SchemaTool::createSchema()) - on verifie que les
 * `@UniqueConstraint` portees par Tree/Tree_item/Status (meme nom d'index
 * que la migration) sont bien efficaces, et qu'un schema:update derive
 * des memes annotations ne rapporte plus aucune difference (la consigne
 * "schema:update --dump-sql vide ensuite").
 */
final class TreeStatusUniqueConstraintAnnotationsTest extends DatabaseTestCase
{
    public function testDuplicateTreeNameIsRejectedByTheDatabase(): void
    {
        \Tree::create(['name' => 'etapea5_dup_tree']);

        try {
            \Tree::create(['name' => 'etapea5_dup_tree']);
            $this->fail("l'index uq_tree_name doit refuser un second tree de meme name");
        } catch (\RuntimeException $e) {
            // Model::create() remonte l'erreur SQL via DBALException (qui
            // enveloppe le PDOException d'origine), pas le PDOException
            // lui-meme.
            $this->assertStringContainsString('uq_tree_name', $e->getMessage());
        }
    }

    public function testDuplicateStatusEntityKeyIsRejectedByTheDatabase(): void
    {
        \Status::create(['entity' => 'etapea5_entity', '_key' => 'published']);

        try {
            \Status::create(['entity' => 'etapea5_entity', '_key' => 'published']);
            $this->fail("l'index uq_status_entity_key doit refuser un second status de meme (entity, _key)");
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('uq_status_entity_key', $e->getMessage());
        }
    }

    public function testTwoTreeItemsOfTheSameTreeWithoutSlugAreStillAllowed(): void
    {
        $treeId = \Tree::create(['name' => 'etapea5_tree_for_items']);

        $firstId = \Tree_item::create(['tree_id' => $treeId]);
        $secondId = \Tree_item::create(['tree_id' => $treeId]);

        $this->assertNotSame($firstId, $secondId);
    }

    public function testSchemaUpdateReportsNoPendingDifference(): void
    {
        global $em;

        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $sql = $tool->getUpdateSchemaSql($em->getMetadataFactory()->getAllMetadata(), false);

        $this->assertSame([], $sql, "schema:update --dump-sql doit etre vide (annotations <-> base) :\n" . implode("\n", $sql));
    }
}
