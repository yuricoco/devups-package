<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE V.3 : database/migration/2026_11_08_phase10_image_extension_column.sql
 * ajoute `extensionnpm` (dclass/extends/ImageCore.php) a `cmsresource` et
 * `tree_item_image` - colonne manquante depuis phase4_cms_rename.sql,
 * decouverte via `doctrine orm:schema-tool:update --dump-sql` sur la base
 * de dev reelle (sans elle, tout INSERT/UPDATE Doctrine sur ces deux
 * entites echoue : "Unknown column 'extensionnpm'").
 */
final class ImageExtensionColumnMigrationTest extends TestCase
{
    private const DB = 'devups_phpunit_image_extension';

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

        // Schema minimal necessaire a phase4_cms_rename.sql (memes tables
        // que CmsOldImageTablesDropTest::createOldSchema()).
        $this->pdo->exec('CREATE TABLE `user` (`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `tree_item` (`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `cmstext` (`id` int(11) NOT NULL AUTO_INCREMENT, `reference` varchar(125) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `cmstext_lang` (`cmstext_id` int(11) NOT NULL, `dvups_lang_id` int(11) NOT NULL, PRIMARY KEY (`cmstext_id`, `dvups_lang_id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `dv_image` (`id` int(11) NOT NULL AUTO_INCREMENT, `reference` varchar(123) DEFAULT NULL, `name` varchar(123) DEFAULT NULL, `image` varchar(255) NOT NULL, `uploaddir` varchar(255) NOT NULL, `size` float DEFAULT NULL, `width` float DEFAULT NULL, `height` float DEFAULT NULL, `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `imagecms` (`id` int(11) NOT NULL AUTO_INCREMENT, `cmstext_id` int(11) DEFAULT NULL, `image_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `tree_item_image` (`id` int(11) NOT NULL AUTO_INCREMENT, `tree_item_id` int(11) DEFAULT NULL, `width_size` varchar(255) DEFAULT NULL, `image_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');

        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase4_cms_rename.sql'));
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $server = new \PDO('mysql:host=' . dbhost, dbuser, dbpassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $server->exec('DROP DATABASE IF EXISTS `' . self::DB . '`');
        parent::tearDown();
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool)$this->pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->pdo->quote($column))->fetchColumn();
    }

    public function testAddsTheExtensionColumnToCmsresourceAndTreeItemImage(): void
    {
        $this->assertFalse($this->columnExists('cmsresource', 'extensionnpm'), 'precondition : colonne absente avant cette migration');
        $this->assertFalse($this->columnExists('tree_item_image', 'extensionnpm'), 'precondition : colonne absente avant cette migration');

        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase10_image_extension_column.sql'));

        $this->assertTrue($this->columnExists('cmsresource', 'extensionnpm'));
        $this->assertTrue($this->columnExists('tree_item_image', 'extensionnpm'));
    }
}
