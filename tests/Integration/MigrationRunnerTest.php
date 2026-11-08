<?php

namespace Devups\Tests\Integration;

use dclass\devups\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;

/**
 * ETAPE Q.5 : MigrationRunner joue dans l'ordre, enregistre dans
 * dvups_migration, est idempotent, supporte --dry-run et refuse une
 * migration destructive sans confirmation.
 *
 * N'etend PAS DatabaseTestCase : les migrations testees contiennent du DDL
 * (CREATE/DROP TABLE), qui valide (COMMIT) implicitement en MySQL - une
 * transaction englobante ne protegerait rien. Nettoyage manuel en
 * tearDown() a la place (table de scratch + lignes dvups_migration de ce
 * test, jamais les vraies migrations de database/migration/).
 */
final class MigrationRunnerTest extends TestCase
{
    private string $dir;
    private string $probeTable;

    protected function setUp(): void
    {
        if (getenv('DEVUPS_TEST_SKIP_DB') === '1')
            $this->markTestSkipped('DEVUPS_TEST_SKIP_DB=1');

        $this->dir = sys_get_temp_dir() . '/devups-migration-test-' . uniqid() . '/';
        mkdir($this->dir, 0777, true);
        $this->probeTable = 'migration_runner_probe_' . uniqid();
    }

    protected function tearDown(): void
    {
        \Model::pdo()->exec('DROP TABLE IF EXISTS `' . $this->probeTable . '`');
        $stmt = \Model::pdo()->prepare("DELETE FROM `dvups_migration` WHERE `migration` LIKE :p");
        $stmt->execute(['p' => basename(rtrim($this->dir, '/')) . '%']);

        foreach (glob($this->dir . '*') ?: [] as $file)
            unlink($file);
        rmdir($this->dir);

        parent::tearDown();
    }

    private function writeMigration(string $name, string $sql): string
    {
        // prefixe (dossier unique du test) dans le NOM du fichier, pour que
        // tearDown() ne puisse retrouver/supprimer que les lignes
        // dvups_migration de CE test (le nom du fichier est la cle
        // primaire logique de la table).
        $file = basename(rtrim($this->dir, '/')) . '_' . $name;
        file_put_contents($this->dir . $file, $sql);
        return $file;
    }

    public function testRunPlaysMigrationsInOrderAndRecordsThem(): void
    {
        $first = $this->writeMigration('1_create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY, label VARCHAR(50));");
        $second = $this->writeMigration('2_seed.sql', "INSERT INTO `{$this->probeTable}` (id, label) VALUES (1, 'a');");

        $result = MigrationRunner::run(['dir' => $this->dir]);

        $this->assertSame([$first, $second], $result['ran']);
        $this->assertSame([], $result['refused_destructive']);

        $row = \Model::pdo()->query("SELECT COUNT(*) FROM `{$this->probeTable}`")->fetchColumn();
        $this->assertSame('1', (string)$row);

        foreach ([$first, $second] as $name) {
            $stmt = \Model::pdo()->prepare('SELECT `batch` FROM `dvups_migration` WHERE `migration` = :m');
            $stmt->execute(['m' => $name]);
            $this->assertNotFalse($stmt->fetchColumn(), "migration $name doit etre enregistree");
        }
    }

    public function testRunIsIdempotent(): void
    {
        $file = $this->writeMigration('create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY);");

        $first = MigrationRunner::run(['dir' => $this->dir]);
        $second = MigrationRunner::run(['dir' => $this->dir]);

        $this->assertSame([$file], $first['ran']);
        $this->assertSame([], $second['ran'], 'une migration deja jouee ne doit pas etre rejouee');
    }

    public function testDryRunDoesNotExecuteOrRecordAnything(): void
    {
        $file = $this->writeMigration('create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY);");

        $result = MigrationRunner::run(['dir' => $this->dir, 'dry_run' => true]);

        $this->assertCount(1, $result['dry_run']);
        $this->assertSame($file, $result['dry_run'][0]['migration']);
        $this->assertStringContainsString('CREATE TABLE', $result['dry_run'][0]['sql']);

        $stmt = \Model::pdo()->prepare('SELECT COUNT(*) FROM `dvups_migration` WHERE `migration` = :m');
        $stmt->execute(['m' => $file]);
        $this->assertSame('0', (string)$stmt->fetchColumn(), 'dry-run ne doit rien enregistrer');

        $exists = \Model::pdo()->query("SHOW TABLES LIKE '{$this->probeTable}'")->fetchColumn();
        $this->assertFalse($exists, 'dry-run ne doit pas executer le SQL');
    }

    public function testDestructiveMigrationIsRefusedWithoutConfirmation(): void
    {
        $file = $this->writeMigration('drop.sql', "DROP TABLE IF EXISTS `{$this->probeTable}`;");

        $result = MigrationRunner::run(['dir' => $this->dir]);

        $this->assertSame([], $result['ran']);
        $this->assertSame([$file], $result['refused_destructive']);

        $stmt = \Model::pdo()->prepare('SELECT COUNT(*) FROM `dvups_migration` WHERE `migration` = :m');
        $stmt->execute(['m' => $file]);
        $this->assertSame('0', (string)$stmt->fetchColumn());
    }

    public function testDestructiveMigrationRunsWithForce(): void
    {
        \Model::pdo()->exec("CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY)");
        $file = $this->writeMigration('drop.sql', "DROP TABLE `{$this->probeTable}`;");

        $result = MigrationRunner::run(['dir' => $this->dir, 'force' => true]);

        $this->assertSame([$file], $result['ran']);
        $exists = \Model::pdo()->query("SHOW TABLES LIKE '{$this->probeTable}'")->fetchColumn();
        $this->assertFalse($exists);
    }

    public function testDestructiveMigrationRunsWhenConfirmCallbackReturnsTrue(): void
    {
        \Model::pdo()->exec("CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY)");
        $file = $this->writeMigration('drop.sql', "DROP TABLE `{$this->probeTable}`;");

        $result = MigrationRunner::run(['dir' => $this->dir, 'confirm' => fn() => true]);

        $this->assertSame([$file], $result['ran']);
    }

    public function testDestructiveMigrationIsRefusedInProdEvenWithConfirmCallback(): void
    {
        \Model::pdo()->exec("CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY)");
        $file = $this->writeMigration('drop.sql', "DROP TABLE `{$this->probeTable}`;");

        $result = MigrationRunner::run(['dir' => $this->dir, 'prod' => true, 'confirm' => fn() => true]);

        $this->assertSame([], $result['ran']);
        $this->assertSame([$file], $result['refused_destructive']);
    }

    public function testDestructiveMigrationRunsInProdWithForce(): void
    {
        \Model::pdo()->exec("CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY)");
        $file = $this->writeMigration('drop.sql', "DROP TABLE `{$this->probeTable}`;");

        $result = MigrationRunner::run(['dir' => $this->dir, 'prod' => true, 'force' => true]);

        $this->assertSame([$file], $result['ran']);
    }

    public function testMarkAllAsBaselineRecordsWithoutExecuting(): void
    {
        $file = $this->writeMigration('create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY);");

        $marked = MigrationRunner::markAllAsBaseline($this->dir);

        $this->assertSame(1, $marked);
        $exists = \Model::pdo()->query("SHOW TABLES LIKE '{$this->probeTable}'")->fetchColumn();
        $this->assertFalse($exists, 'markAllAsBaseline ne doit jamais executer le SQL');

        $stmt = \Model::pdo()->prepare('SELECT `batch` FROM `dvups_migration` WHERE `migration` = :m');
        $stmt->execute(['m' => $file]);
        $this->assertSame('0', (string)$stmt->fetchColumn());

        $this->assertSame(0, MigrationRunner::markAllAsBaseline($this->dir), 'idempotent : ne remarque pas une migration deja enregistree');
    }

    public function testStatusListsPlayedAndPendingMigrations(): void
    {
        $played = $this->writeMigration('1_create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY);");
        MigrationRunner::run(['dir' => $this->dir]);

        $pending = $this->writeMigration('2_more.sql', "ALTER TABLE `{$this->probeTable}` ADD COLUMN label VARCHAR(10);");

        $rows = MigrationRunner::status($this->dir);
        $byName = [];
        foreach ($rows as $row)
            $byName[$row['migration']] = $row;

        $this->assertTrue($byName[$played]['played']);
        $this->assertNotNull($byName[$played]['batch']);
        $this->assertFalse($byName[$pending]['played']);
        $this->assertNull($byName[$pending]['batch']);
    }

    public function testStepAssignsASeparateBatchToEachMigration(): void
    {
        $first = $this->writeMigration('1_create.sql', "CREATE TABLE `{$this->probeTable}` (id INT PRIMARY KEY);");
        $second = $this->writeMigration('2_seed.sql', "INSERT INTO `{$this->probeTable}` (id) VALUES (1);");

        MigrationRunner::run(['dir' => $this->dir, 'step' => true]);

        $stmt = \Model::pdo()->prepare('SELECT `batch` FROM `dvups_migration` WHERE `migration` = :m');
        $stmt->execute(['m' => $first]);
        $batch1 = $stmt->fetchColumn();
        $stmt->execute(['m' => $second]);
        $batch2 = $stmt->fetchColumn();

        $this->assertNotSame($batch1, $batch2, '--step doit attribuer un lot distinct a chaque migration');
    }

    public function testPhpMigrationFileWithAnUpMethodIsSupported(): void
    {
        $probe = $this->probeTable;
        $file = basename(rtrim($this->dir, '/')) . '_php_migration.php';
        file_put_contents($this->dir . $file, "<?php\nreturn new class {\n    public function up(\\PDO \$pdo) {\n        \$pdo->exec('CREATE TABLE `$probe` (id INT PRIMARY KEY)');\n    }\n};\n");

        $result = MigrationRunner::run(['dir' => $this->dir]);

        $this->assertSame([$file], $result['ran']);
        $exists = \Model::pdo()->query("SHOW TABLES LIKE '{$this->probeTable}'")->fetchColumn();
        $this->assertNotFalse($exists);
    }
}
