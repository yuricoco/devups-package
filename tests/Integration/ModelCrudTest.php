<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * Cycle de vie complet d'une entite simple (Configuration) via l'ORM
 * Tchutte. Dvups_log servait a cela avant sa refonte (Phase 2, regroupement
 * par empreinte) : Configuration a deux colonnes libres (_key/_value) tout
 * aussi pratiques pour exercer l'ORM sans dependre d'une entite metier.
 */
final class ModelCrudTest extends DatabaseTestCase
{
    public function testCreateFindUpdateDelete(): void
    {
        $id = \Configuration::create(['_key' => 'phpunit', '_value' => 'premier']);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $conf = \Configuration::find($id);
        $this->assertSame($id, (int)$conf->getId());
        $this->assertSame('phpunit', $conf->_key);
        $this->assertSame('premier', $conf->_value);

        $conf->_value = 'modifie';
        $conf->__update();
        $this->assertSame('modifie', \Configuration::find($id)->_value);

        \Configuration::find($id)->__delete();
        $this->assertSame(0, $this->countRows('configuration', 'id = ?', [$id]));
    }

    public function testFindOnMissingIdGivesEmptyEntity(): void
    {
        $conf = \Configuration::find(999999);
        $this->assertFalse((bool)$conf->getId());
    }

    public function testCountAndWhere(): void
    {
        \Configuration::create(['_key' => 'phpunit-a', '_value' => '1']);
        \Configuration::create(['_key' => 'phpunit-a', '_value' => '2']);
        \Configuration::create(['_key' => 'phpunit-b', '_value' => '3']);

        $this->assertSame(2, (int)\Configuration::where('this._key', 'phpunit-a')->count());
        $this->assertSame(3, (int)\Configuration::where('this._key', 'like', 'phpunit-%')->count());
        $this->assertCount(2, \Configuration::where('this._key', 'phpunit-a')->get());
    }

    public function testFirstOrNull(): void
    {
        $this->assertNull(\Configuration::where('this._key', 'phpunit-none')->firstOrNull());
        \Configuration::create(['_key' => 'phpunit-one', '_value' => 'x']);
        $this->assertSame('x', \Configuration::where('this._key', 'phpunit-one')->firstOrNull()->_value);
    }

    public function testNestedTransactionRollsBackOnlyInnerLevel(): void
    {
        // le test tourne deja dans une transaction (niveau 1)
        \Configuration::create(['_key' => 'phpunit-tx', '_value' => 'outer']);
        try {
            \Model::transaction(function () {
                \Configuration::create(['_key' => 'phpunit-tx', '_value' => 'inner']);
                throw new \RuntimeException('echec volontaire');
            });
            $this->fail('exception attendue');
        } catch (\RuntimeException $e) {
            $this->assertSame('echec volontaire', $e->getMessage());
        }
        $this->assertSame(1, $this->countRows('configuration', '_key = ?', ['phpunit-tx']));
        $this->assertSame(1, \Model::transactionDepth());
    }

    public function testUpdateAfterHydrateWritesTheChange(): void
    {
        // regression : hydrate() gardait une REFERENCE dans dvold, donc
        // entityKeyUpdate() ne voyait jamais de difference
        $id = \Configuration::create(['_key' => 'phpunit-h', '_value' => 'avant']);
        $conf = (new \Configuration($id))->hydrate();
        $conf->_value = 'apres';
        $conf->__update();
        $this->assertSame('apres', \Configuration::find($id)->_value);
    }

    public function testUpdateOfANonHydratedEntityWritesItsValues(): void
    {
        $id = \Configuration::create(['_key' => 'phpunit-nh', '_value' => 'avant']);
        $conf = new \Configuration($id);
        $conf->_key = 'phpunit-nh';
        $conf->_value = 'apres';
        $conf->__update();
        $this->assertSame('apres', \Configuration::find($id)->_value);
    }
}
