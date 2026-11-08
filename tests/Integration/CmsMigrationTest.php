<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE I : verifie le script de migration SQL
 * database/migration/2026_11_08_phase4_cms_rename.sql sur une base
 * DEDIEE qui recree l'ANCIEN schema (cmstext, imagecms, dv_image,
 * tree_item_image avec son ancienne colonne image_id) avec quelques
 * lignes, execute le script, puis verifie que les donnees ont bien ete
 * reprises - jamais sur `devups_phpunit` (deja au NOUVEAU schema, cree
 * par Doctrine a partir des entites actuelles : cmstext n'y existe
 * plus, le RENAME TABLE du script y echouerait).
 *
 * Les DROP (`imagecms`, `dv_image`) restent commentes dans le fichier
 * (jamais executes, y compris ici) : ce test ne verifie donc que la
 * reprise de donnees (INSERT/UPDATE), pas la suppression des anciennes
 * tables.
 */
final class CmsMigrationTest extends TestCase
{
    private const DB = 'devups_phpunit_migration';

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

    /** Reconstitue le schema AVANT l'ETAPE G (colonnes minimales utilisees par le script de migration). */
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

        // ImageCore : memes colonnes que celles reprises par le script
        // (reference/name/image/uploaddir/size/width/height) + dates.
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

        // ancien tree_item_image : width_size + relation image_id vers
        // dv_image (remplacee par l'ETAPE G.4 par des colonnes directes,
        // ajoutees par le script de migration lui-meme).
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

    private function runMigrationScript(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase4_cms_rename.sql');
        $this->assertNotFalse($sql, 'script de migration introuvable');
        // les lignes commentees (DROP imagecms/dv_image, comptages
        // manuels) commencent par "--" : la syntaxe SQL standard les
        // ignore a l'execution, inutile de les retirer avant exec().
        $this->pdo->exec($sql);
    }

    public function testRenamesCmstextToCmscontent(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO cmstext_lang (cmstext_id, dvups_lang_id, title) VALUES (1, 1, 'Accueil')");

        $this->runMigrationScript();

        $row = $this->pdo->query('SELECT reference FROM cmscontent WHERE id = 1')->fetch();
        $this->assertSame('accueil', $row['reference'] ?? null, 'cmstext doit avoir ete renommee en cmscontent, avec ses lignes');

        $langRow = $this->pdo->query('SELECT title FROM cmscontent_lang WHERE cmscontent_id = 1')->fetch();
        $this->assertSame('Accueil', $langRow['title'] ?? null, 'cmstext_lang.cmstext_id doit avoir ete renommee en cmscontent_id');
    }

    public function testMigratesImagecmsRowsIntoCmsresourceKeepingTheOriginalUploaddir(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (10, 'img-accueil', 'logo', 'abc123.png', 'gallery/2024/', 512, 100, 100)");
        $this->pdo->exec('INSERT INTO imagecms (id, cmstext_id, image_id) VALUES (100, 1, 10)');

        $avant = (int)$this->pdo->query('SELECT COUNT(*) FROM imagecms')->fetchColumn();
        $this->assertSame(1, $avant);

        $this->runMigrationScript();

        $row = $this->pdo->query('SELECT * FROM cmsresource WHERE cmscontent_id = 1')->fetch();
        $this->assertNotFalse($row, 'la ligne imagecms doit avoir ete reprise dans cmsresource');
        $this->assertSame('abc123.png', $row['image']);
        // le fichier physique n'est jamais deplace : uploaddir garde sa
        // valeur d'ORIGINE (celle ecrite par l'ancien Dv_image), jamais
        // forcee a "cms/" (le chemin par defaut des nouveaux uploads).
        $this->assertSame('gallery/2024/', $row['uploaddir'], 'uploaddir doit rester celui d\'origine : aucun fichier ne doit etre repute deplace');
        $this->assertEquals(512, $row['size']);

        $apres = (int)$this->pdo->query('SELECT COUNT(*) FROM cmsresource')->fetchColumn();
        $this->assertSame($avant, $apres, 'le comptage avant/apres doit correspondre (aucune ligne perdue ni dupliquee)');

        // imagecms n'est PAS supprimee (DROP reste commente) : la table
        // d'origine doit encore exister et contenir sa ligne.
        $stillThere = (int)$this->pdo->query('SELECT COUNT(*) FROM imagecms')->fetchColumn();
        $this->assertSame(1, $stillThere, 'imagecms ne doit pas etre supprimee : le DROP reste commente par securite');
    }

    public function testMigratesTreeItemImageColumnsFromDvImageKeepingTheOriginalUploaddir(): void
    {
        $this->pdo->exec("INSERT INTO tree_item (id, name) VALUES (1, 'racine')");
        $this->pdo->exec("INSERT INTO dv_image (id, reference, name, image, uploaddir, size, width, height) VALUES
            (20, 'img-arbre', 'icone', 'def456.png', 'gallery/tree/', 256, 50, 50)");
        $this->pdo->exec("INSERT INTO tree_item_image (id, tree_item_id, width_size, image_id) VALUES (200, 1, '50px', 20)");

        $avant = (int)$this->pdo->query('SELECT COUNT(*) FROM tree_item_image WHERE image_id IS NOT NULL')->fetchColumn();
        $this->assertSame(1, $avant);

        $this->runMigrationScript();

        $row = $this->pdo->query('SELECT * FROM tree_item_image WHERE id = 200')->fetch();
        $this->assertSame('def456.png', $row['image'], 'les colonnes ImageCore de tree_item_image doivent avoir ete reprises depuis dv_image');
        $this->assertSame('gallery/tree/', $row['uploaddir'], 'uploaddir doit rester celui d\'origine : aucun fichier ne doit etre repute deplace');
        $this->assertEquals(256, $row['size']);
        // width_size (colonne propre a tree_item_image, independante de
        // dv_image) ne doit pas avoir ete touchee par la reprise.
        $this->assertSame('50px', $row['width_size']);

        $apres = (int)$this->pdo->query('SELECT COUNT(*) FROM tree_item_image WHERE image IS NOT NULL')->fetchColumn();
        $this->assertSame($avant, $apres, 'le comptage avant/apres doit correspondre');

        // dv_image n'est PAS supprimee (DROP reste commente).
        $stillThere = (int)$this->pdo->query('SELECT COUNT(*) FROM dv_image')->fetchColumn();
        $this->assertSame(1, $stillThere, 'dv_image ne doit pas etre supprimee : le DROP reste commente par securite');
    }

    public function testCreatesCmscommentTableReadyToUse(): void
    {
        $this->pdo->exec("INSERT INTO cmstext (id, reference) VALUES (1, 'accueil')");
        $this->pdo->exec("INSERT INTO user (id, username) VALUES (5, 'visiteur')");

        $this->runMigrationScript();

        $this->pdo->exec("INSERT INTO cmscomment (cmscontent_id, user_id, pseudo, comment) VALUES (1, 5, 'Jean', 'Bravo !')");
        $row = $this->pdo->query('SELECT pseudo, comment FROM cmscomment WHERE cmscontent_id = 1')->fetch();
        $this->assertSame('Jean', $row['pseudo']);
        $this->assertSame('Bravo !', $row['comment']);
    }
}
