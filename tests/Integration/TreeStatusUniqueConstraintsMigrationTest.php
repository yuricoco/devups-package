<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE A.5 (v7.1) : database/migration/2026_11_09_v71_a5_unique_constraints.php
 * pose trois contraintes d'unicite (tree.name, tree_item.(tree_id, slug),
 * status.(entity, _key)) - mais refuse, sans rien modifier, si des
 * doublons existent deja pour l'une d'elles.
 *
 * Base de test dediee (meme principe que CmsOldImageTablesDropTest) :
 * schema minimal reconstitue a la main, AVANT la migration (donc sans
 * les index que la migration doit poser) - jamais la base de test
 * principale (`devups_phpunit`), qui a deja ces index via les
 * annotations @UniqueConstraint (SchemaTool les cree au bootstrap).
 */
final class TreeStatusUniqueConstraintsMigrationTest extends TestCase
{
    private const DB = 'devups_phpunit_unique_constraints';

    private ?\PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('DEVUPS_TEST_SKIP_DB') === '1')
            $this->markTestSkipped('DEVUPS_TEST_SKIP_DB=1');

        $server = new \PDO('mysql:host=' . dbhost, dbuser, dbpassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $server->exec('DROP DATABASE IF EXISTS `' . self::DB . '`');
        $server->exec('CREATE DATABASE `' . self::DB . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->pdo = new \PDO(
            'mysql:host=' . dbhost . ';dbname=' . self::DB,
            dbuser,
            dbpassword,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $this->createSchemaWithoutUniqueIndexes();
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $server = new \PDO('mysql:host=' . dbhost, dbuser, dbpassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $server->exec('DROP DATABASE IF EXISTS `' . self::DB . '`');
        parent::tearDown();
    }

    private function createSchemaWithoutUniqueIndexes(): void
    {
        $this->pdo->exec('CREATE TABLE `tree` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `tree_item` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tree_id` int(11) DEFAULT NULL,
            `slug` varchar(55) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `status` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `entity` varchar(55) DEFAULT NULL,
            `_key` varchar(55) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');
    }

    private function runMigration(): void
    {
        $migration = require __DIR__ . '/../../database/migration/2026_11_09_v71_a5_unique_constraints.php';
        $migration->up($this->pdo);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $this->pdo->quote($table) . "
               AND INDEX_NAME = " . $this->pdo->quote($indexName)
        );
        return (int)$stmt->fetchColumn() > 0;
    }

    public function testRefusesAndChangesNothingWhenTreeNameHasDuplicates(): void
    {
        $this->pdo->exec("INSERT INTO tree (name) VALUES ('racine')");
        $this->pdo->exec("INSERT INTO tree (name) VALUES ('racine')");

        try {
            $this->runMigration();
            $this->fail('un doublon tree.name doit faire echouer la migration');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tree.name', $e->getMessage());
        }

        $this->assertFalse($this->indexExists('tree', 'uq_tree_name'));
        $this->assertFalse($this->indexExists('tree_item', 'uq_tree_item_tree_id_slug'), 'aucun index ne doit etre pose si une AUTRE contrainte a des doublons');
        $this->assertFalse($this->indexExists('status', 'uq_status_entity_key'));
    }

    public function testRefusesAndChangesNothingWhenTreeItemSlugHasDuplicates(): void
    {
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (1, 'accueil')");

        try {
            $this->runMigration();
            $this->fail('un doublon tree_item.(tree_id, slug) doit faire echouer la migration');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tree_item.(tree_id, slug)', $e->getMessage());
        }

        $this->assertFalse($this->indexExists('tree_item', 'uq_tree_item_tree_id_slug'));
    }

    public function testRefusesAndChangesNothingWhenStatusEntityKeyHasDuplicates(): void
    {
        $this->pdo->exec("INSERT INTO status (entity, `_key`) VALUES ('article', 'published')");
        $this->pdo->exec("INSERT INTO status (entity, `_key`) VALUES ('article', 'published')");

        try {
            $this->runMigration();
            $this->fail('un doublon status.(entity, _key) doit faire echouer la migration');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('status.(entity, _key)', $e->getMessage());
        }

        $this->assertFalse($this->indexExists('status', 'uq_status_entity_key'));
    }

    public function testMultipleNullSlugsAreNeverTreatedAsDuplicates(): void
    {
        // NULL n'est jamais egal a NULL (ni dans la detection de doublons,
        // ni dans un index UNIQUE MySQL) : plusieurs tree_item d'un meme
        // arbre sans slug doivent rester possibles.
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (1, NULL)");
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (1, NULL)");

        $this->runMigration();

        $this->assertTrue($this->indexExists('tree_item', 'uq_tree_item_tree_id_slug'));
    }

    public function testCleanDataGetsAllThreeUniqueIndexesApplied(): void
    {
        $this->pdo->exec("INSERT INTO tree (name) VALUES ('racine')");
        $this->pdo->exec("INSERT INTO tree (name) VALUES ('secondaire')");
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO tree_item (tree_id, slug) VALUES (2, 'accueil')");
        $this->pdo->exec("INSERT INTO status (entity, `_key`) VALUES ('article', 'published')");
        $this->pdo->exec("INSERT INTO status (entity, `_key`) VALUES ('page', 'published')");

        $this->runMigration();

        $this->assertTrue($this->indexExists('tree', 'uq_tree_name'));
        $this->assertTrue($this->indexExists('tree_item', 'uq_tree_item_tree_id_slug'));
        $this->assertTrue($this->indexExists('status', 'uq_status_entity_key'));

        // l'index pose refuse bien un NOUVEAU doublon desormais
        try {
            $this->pdo->exec("INSERT INTO tree (name) VALUES ('racine')");
            $this->fail("l'index unique doit refuser un doublon tree.name apres la migration");
        } catch (\PDOException $e) {
            $this->assertStringContainsString('uq_tree_name', $e->getMessage());
        }
    }

    public function testRunningTwiceIsIdempotent(): void
    {
        $this->pdo->exec("INSERT INTO tree (name) VALUES ('racine')");

        $this->runMigration();
        $this->runMigration();

        $this->assertTrue($this->indexExists('tree', 'uq_tree_name'));
    }
}
