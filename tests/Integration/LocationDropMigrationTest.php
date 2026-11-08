<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE V : database/migration/2026_11_08_phase5_location_drop.sql
 * echouait (1451, contrainte de cle etrangere) sur la base de dev reelle
 * car `department`, `district`, `region` et `town` ont des FK ENTRE
 * elles (department.region_id -> region, district.town_id -> town,
 * town.department_id -> department) - un DROP TABLE sur la table
 * REFERENCEE echoue tant que la table qui la reference existe encore.
 * L'ordre des DROP dans le fichier a ete corrige en consequence (sans
 * desactiver FOREIGN_KEY_CHECKS) ; ce test reproduit la meme chaine de
 * FK sur une base jetable pour verifier que le fichier s'execute
 * desormais sans erreur, quel que soit l'ordre dans lequel MySQL
 * resoudrait autrement le probleme.
 */
final class LocationDropMigrationTest extends TestCase
{
    private const DB = 'devups_phpunit_location_drop';

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

        // Meme chaine de FK que sur la base de dev reelle :
        // region <- department <- town <- district, continent isole.
        $this->pdo->exec('CREATE TABLE `region` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `department` (`id` int(11) NOT NULL AUTO_INCREMENT, `region_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`), CONSTRAINT `fk_department_region` FOREIGN KEY (`region_id`) REFERENCES `region` (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `town` (`id` int(11) NOT NULL AUTO_INCREMENT, `department_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`), CONSTRAINT `fk_town_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `district` (`id` int(11) NOT NULL AUTO_INCREMENT, `town_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`), CONSTRAINT `fk_district_town` FOREIGN KEY (`town_id`) REFERENCES `town` (`id`)) ENGINE=InnoDB');
        $this->pdo->exec('CREATE TABLE `continent` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB');
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $server = new \PDO('mysql:host=' . dbhost, dbuser, dbpassword, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $server->exec('DROP DATABASE IF EXISTS `' . self::DB . '`');
        parent::tearDown();
    }

    private function tableExists(string $name): bool
    {
        return (bool)$this->pdo->query('SHOW TABLES LIKE ' . $this->pdo->quote($name))->fetchColumn();
    }

    public function testDropsAllFiveTablesDespiteTheForeignKeyChainBetweenThem(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase5_location_drop.sql');

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement)
            $this->pdo->exec($statement);

        foreach (['department', 'district', 'region', 'town', 'continent'] as $table)
            $this->assertFalse($this->tableExists($table), $table . ' doit avoir ete supprimee');
    }
}
