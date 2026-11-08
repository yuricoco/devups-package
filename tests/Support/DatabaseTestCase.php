<?php

namespace Devups\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base des tests d'integration : chaque test s'execute dans une transaction
 * annulee a la fin, la base de test reste donc propre d'un test a l'autre.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('DEVUPS_TEST_SKIP_DB') === '1')
            $this->markTestSkipped('DEVUPS_TEST_SKIP_DB=1');
        \Model::beginTransaction();
    }

    protected function tearDown(): void
    {
        // annule tout ce que le test a ecrit, y compris les niveaux imbriques
        // laisses ouverts par un test en echec
        while (\Model::transactionDepth() > 0)
            \Model::rollBack();
        parent::tearDown();
    }

    /** Nombre de lignes d'une table, en SQL brut (independant de l'ORM). */
    protected function countRows(string $table, string $where = '1', array $params = []): int
    {
        $stmt = \Model::pdo()->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
