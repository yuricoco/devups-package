<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * Cycle de vie complet d'une entite simple (Dvups_log) via l'ORM Tchutte.
 */
final class ModelCrudTest extends DatabaseTestCase
{
    public function testCreateFindUpdateDelete(): void
    {
        $id = \Dvups_log::create(['object' => 'phpunit', 'log' => 'premier']);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $log = \Dvups_log::find($id);
        $this->assertSame($id, (int)$log->getId());
        $this->assertSame('phpunit', $log->object);
        $this->assertSame('premier', $log->log);

        $log->log = 'modifie';
        $log->__update();
        $this->assertSame('modifie', \Dvups_log::find($id)->log);

        \Dvups_log::find($id)->__delete();
        $this->assertSame(0, $this->countRows('dvups_log', 'id = ?', [$id]));
    }

    public function testFindOnMissingIdGivesEmptyEntity(): void
    {
        $log = \Dvups_log::find(999999);
        $this->assertFalse((bool)$log->getId());
    }

    public function testCountAndWhere(): void
    {
        \Dvups_log::create(['object' => 'phpunit-a', 'log' => '1']);
        \Dvups_log::create(['object' => 'phpunit-a', 'log' => '2']);
        \Dvups_log::create(['object' => 'phpunit-b', 'log' => '3']);

        $this->assertSame(2, (int)\Dvups_log::where('this.object', 'phpunit-a')->count());
        $this->assertSame(3, (int)\Dvups_log::where('this.object', 'like', 'phpunit-%')->count());
        $this->assertCount(2, \Dvups_log::where('this.object', 'phpunit-a')->get());
    }

    public function testFirstOrNull(): void
    {
        $this->assertNull(\Dvups_log::where('this.object', 'phpunit-none')->firstOrNull());
        \Dvups_log::create(['object' => 'phpunit-one', 'log' => 'x']);
        $this->assertSame('x', \Dvups_log::where('this.object', 'phpunit-one')->firstOrNull()->log);
    }

    public function testNestedTransactionRollsBackOnlyInnerLevel(): void
    {
        // le test tourne deja dans une transaction (niveau 1)
        \Dvups_log::create(['object' => 'phpunit-tx', 'log' => 'outer']);
        try {
            \Model::transaction(function () {
                \Dvups_log::create(['object' => 'phpunit-tx', 'log' => 'inner']);
                throw new \RuntimeException('echec volontaire');
            });
            $this->fail('exception attendue');
        } catch (\RuntimeException $e) {
            $this->assertSame('echec volontaire', $e->getMessage());
        }
        $this->assertSame(1, $this->countRows('dvups_log', 'object = ?', ['phpunit-tx']));
        $this->assertSame(1, \Model::transactionDepth());
    }

    public function testUpdateAfterHydrateWritesTheChange(): void
    {
        // regression : hydrate() gardait une REFERENCE dans dvold, donc
        // entityKeyUpdate() ne voyait jamais de difference
        $id = \Dvups_log::create(['object' => 'phpunit-h', 'log' => 'avant']);
        $log = (new \Dvups_log($id))->hydrate();
        $log->log = 'apres';
        $log->__update();
        $this->assertSame('apres', \Dvups_log::find($id)->log);
    }

    public function testUpdateOfANonHydratedEntityWritesItsValues(): void
    {
        $id = \Dvups_log::create(['object' => 'phpunit-nh', 'log' => 'avant']);
        $log = new \Dvups_log($id);
        $log->object = 'phpunit-nh';
        $log->log = 'apres';
        $log->__update();
        $this->assertSame('apres', \Dvups_log::find($id)->log);
    }
}
