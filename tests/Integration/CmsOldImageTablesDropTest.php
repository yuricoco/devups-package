<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE T : la migration PHP
 * database/migration/2026_11_08_phase4_drop_old_images.php supprime
 * `imagecms`/`dv_image` (et la colonne `tree_item_image.image_id`)
 * SEULEMENT si la reprise de donnees de 2026_11_08_phase4_cms_rename.sql
 * est verifiee complete par comptage - sinon elle echoue (exception avec
 * les comptages en clair) sans rien supprimer.
 *
 * Meme principe de base de test dediee que CmsMigrationTest (ancien
 * schema reconstitue a la main) : jamais sur `devups_phpunit`, deja au
 * nouveau schema (imagecms/dv_image n'y existent pas).
 */
final class CmsOldImageTablesDropTest extends TestCase
{
    private const DB = 'devups_phpunit_drop_images';

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

        $this->createOldSchema();
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $server = new \PDO('mysql:host=' . dbhost, dbuser, dbpassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $server->exec('DROP DATABASE IF EXISTS `' . self::DB . '`');
        parent::tearDown();
    }

    /** Memes tables minimales que CmsMigrationTest::createOldSchema(). */
    private function createOldSchema(): void
    {
        $this->pdo->exec('CREATE TABLE `user` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `tree_item` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `cmstext` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `reference` varchar(125) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `cmstext_lang` (
            `cmstext_id` int(11) NOT NULL,
            `dvups_lang_id` int(11) NOT NULL,
            `title` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`cmstext_id`, `dvups_lang_id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `dv_image` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `reference` varchar(123) DEFAULT NULL,
            `name` varchar(123) DEFAULT NULL,
            `image` varchar(255) NOT NULL,
            `uploaddir` varchar(255) NOT NULL,
            `size` float DEFAULT NULL,
            `width` float DEFAULT NULL,
            `height` float DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `imagecms` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cmstext_id` int(11) DEFAULT NULL,
            `image_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_imagecms_cmstext` FOREIGN KEY (`cmstext_id`) REFERENCES `cmstext` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_imagecms_image` FOREIGN KEY (`image_id`) REFERENCES `dv_image` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB');

        $this->pdo->exec('CREATE TABLE `tree_item_image` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tree_item_id` int(11) DEFAULT NULL,
            `width_size` varchar(255) DEFAULT NULL,
            `image_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_tree_item_image_tree_item` FOREIGN KEY (`tree_item_id`) REFERENCES `tree_item` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tree_item_image_image` FOREIGN KEY (`image_id`) REFERENCES `dv_image` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB');
    }

    private function runRenameScript(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase4_cms_rename.sql');
        $this->pdo->exec($sql);
    }

    private function runDropMigration(): void
    {
        $migration = require __DIR__ . '/../../database/migration/2026_11_08_phase4_drop_old_images.php';
        $migration->up($this->pdo);
    }

    private function tableExists(string $name): bool
    {
        return (bool)$this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote($name))->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool)$this->pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->pdo->quote($column))->fetchColumn();
    }

    public function testCompleteRecoveryDropsTheOldTablesAndTheImageIdColumn(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (10, 'img-accueil', 'logo', 'abc123.png', 'gallery/2024/', 512, 100, 100)");
        $this->pdo->exec('INSERT INTO imagecms (id, cmstext_id, image_id) VALUES (100, 1, 10)');

        $this->pdo->exec("INSERT INTO tree_item (id, name) VALUES (1, 'racine')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (20, 'img-arbre', 'icone', 'def456.png', 'gallery/tree/', 256, 50, 50)");
        $this->pdo->exec("INSERT INTO tree_item_image (id, tree_item_id, width_size, image_id) VALUES (200, 1, '50px', 20)");

        $this->runRenameScript();
        $this->runDropMigration();

        $this->assertFalse($this->tableExists('imagecms'), 'imagecms doit avoir ete supprimee');
        $this->assertFalse($this->tableExists('dv_image'), 'dv_image doit avoir ete supprimee');
        $this->assertFalse($this->columnExists('tree_item_image', 'image_id'), 'tree_item_image.image_id doit avoir ete retiree');

        // les donnees reprises restent intactes
        $row = $this->pdo->query('SELECT * FROM cmsresource WHERE cmscontent_id = 1')->fetch();
        $this->assertSame('abc123.png', $row['image']);
        $treeRow = $this->pdo->query('SELECT * FROM tree_item_image WHERE id = 200')->fetch();
        $this->assertSame('def456.png', $treeRow['image']);
    }

    public function testNoOldDataAtAllIsANoopNotAFailure(): void
    {
        // aucune ligne imagecms/dv_image/tree_item_image a reprendre :
        // la reprise est trivialement "complete" (rien a comparer).
        $this->runRenameScript();
        $this->runDropMigration();

        $this->assertFalse($this->tableExists('imagecms'));
        $this->assertFalse($this->tableExists('dv_image'));
        $this->assertFalse($this->columnExists('tree_item_image', 'image_id'));
    }

    public function testIncompleteImagecmsRecoveryFailsWithoutDroppingAnything(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (10, 'img-accueil', 'logo', 'abc123.png', 'gallery/2024/', 512, 100, 100)");
        $this->pdo->exec('INSERT INTO imagecms (id, cmstext_id, image_id) VALUES (100, 1, 10)');

        $this->runRenameScript();

        // simule une reprise incomplete : la ligne cmsresource correspondante
        // a disparu (ex. script de reprise interrompu/rejoue partiellement)
        // sans que la ligne imagecms d'origine ait ete touchee.
        $this->pdo->exec('DELETE FROM cmsresource WHERE cmscontent_id = 1');

        try {
            $this->runDropMigration();
            $this->fail('une reprise imagecms incomplete doit faire echouer la migration');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('imagecms', $e->getMessage());
            $this->assertStringContainsString('cmsresource=0', $e->getMessage());
            $this->assertStringContainsString('imagecms=1', $e->getMessage());
        }

        $this->assertTrue($this->tableExists('imagecms'), 'imagecms ne doit PAS etre supprimee si la reprise est incomplete');
        $this->assertTrue($this->tableExists('dv_image'), 'dv_image ne doit PAS etre supprimee si imagecms echoue');
        $this->assertTrue($this->columnExists('tree_item_image', 'image_id'), 'image_id ne doit PAS etre retiree si imagecms echoue');
    }

    public function testIncompleteTreeItemImageRecoveryFailsWithoutDroppingAnything(): void
    {
        $this->pdo->exec("INSERT INTO tree_item (id, name) VALUES (1, 'racine')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (20, 'img-arbre', 'icone', 'def456.png', 'gallery/tree/', 256, 50, 50)");
        $this->pdo->exec("INSERT INTO tree_item_image (id, tree_item_id, width_size, image_id) VALUES (200, 1, '50px', 20)");

        $this->runRenameScript();

        // simule une reprise incomplete : la colonne ImageCore n'a pas ete
        // remplie pour cette ligne, alors que image_id (l'ancienne relation)
        // est toujours renseigne.
        $this->pdo->exec('UPDATE tree_item_image SET image = NULL WHERE id = 200');

        try {
            $this->runDropMigration();
            $this->fail('une reprise tree_item_image incomplete doit faire echouer la migration');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tree_item_image', $e->getMessage());
            $this->assertStringContainsString('1 ligne', $e->getMessage());
        }

        // imagecms est verifie AVANT tree_item_image, mais la suppression
        // (DROP) n'a lieu qu'une fois TOUTES les verifications passees -
        // la validation imagecms (0 lignes ici) passe, mais la migration
        // s'arrete ensuite sur tree_item_image : rien n'est donc supprime,
        // imagecms compris.
        $this->assertTrue($this->tableExists('imagecms'), 'imagecms ne doit PAS etre supprimee si tree_item_image echoue ensuite');
        $this->assertTrue($this->tableExists('dv_image'), 'dv_image ne doit PAS etre supprimee si tree_item_image echoue');
        $this->assertTrue($this->columnExists('tree_item_image', 'image_id'), 'image_id ne doit PAS etre retiree si tree_item_image echoue');
    }

    public function testRunningTwiceIsIdempotentAfterACompleteRecovery(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (10, 'img-accueil', 'logo', 'abc123.png', 'gallery/2024/', 512, 100, 100)");
        $this->pdo->exec('INSERT INTO imagecms (id, cmstext_id, image_id) VALUES (100, 1, 10)');

        $this->runRenameScript();
        $this->runDropMigration();
        // un deuxieme passage (ex. rejoue par erreur) ne doit pas echouer :
        // imagecms/dv_image n'existent deja plus, rien a faire.
        $this->runDropMigration();

        $this->assertFalse($this->tableExists('imagecms'));
        $this->assertFalse($this->tableExists('dv_image'));
    }
}
