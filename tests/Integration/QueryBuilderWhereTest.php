<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * where('col', <valeur fausse>) : la forme a deux arguments doit comparer a
 * la valeur, y compris 0, '' ou NULL (regression : `if ($operator)` faisait
 * sauter le predicat et inversait le filtre).
 */
final class QueryBuilderWhereTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach ([['PHPUnit Zero', 'zz', 0], ['PHPUnit One', 'zo', 1]] as [$name, $iso, $active]) {
            \Dvups_lang::create([
                'name' => $name, 'iso_code' => $iso, 'active' => $active, 'main' => 0,
                'language_code' => $iso, 'locale' => $iso,
                'date_format_lite' => 'd/m/Y', 'date_format_full' => 'd/m/Y H:i:s',
            ]);
        }
    }

    public function testTwoArgumentFormWithZero(): void
    {
        $rows = \Dvups_lang::where('this.active', 0)->where('this.name', 'like', 'PHPUnit%')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('zz', $rows[0]->iso_code);
    }

    public function testThreeArgumentFormWithZero(): void
    {
        $rows = \Dvups_lang::where('this.active', '=', 0)->where('this.name', 'like', 'PHPUnit%')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('zz', $rows[0]->iso_code);
    }

    public function testArrayForm(): void
    {
        $rows = \Dvups_lang::where(['this.iso_code' => 'zo'])->get();
        $this->assertCount(1, $rows);
        $this->assertSame('PHPUnit One', $rows[0]->name);
    }

    public function testWhereIn(): void
    {
        $this->assertSame(2, (int)\Dvups_lang::select()->whereIn('this.iso_code', ['zz', 'zo'])->count());
    }

    public function testValuesAreBoundNotInterpolated(): void
    {
        // une valeur contenant une apostrophe ne doit ni casser ni injecter la requete
        $this->assertSame(0, (int)\Dvups_lang::where('this.name', "x' OR '1'='1")->count());
    }

    /**
     * ETAPE B.1 (v7.1) : whereIn() liait deja ses valeurs via
     * qb_sanitize() (guillemets simples, SANS echappement) - une valeur
     * avec une apostrophe cassait la requete ou pouvait injecter.
     * Desormais liee en parametre.
     */
    public function testWhereInWithAnApostropheDoesNotErrorOrOvermatch(): void
    {
        $this->assertSame(1, (int)\Dvups_lang::select()->whereIn('this.name', ["x' OR '1'='1", 'PHPUnit Zero'])->count());
    }

    /**
     * ETAPE B.1 (v7.1) : la forme chaine brute de whereIn() est
     * depreciee - elle n'accepte plus qu'une liste d'entiers.
     */
    public function testWhereInRawStringFormOnlyAcceptsIntegers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \Dvups_lang::select()->whereIn('this.id', "1,2' OR '1'='1")->count();
    }

    public function testWhereInRawStringFormWithIntegersStillWorks(): void
    {
        $ids = \Dvups_lang::select()->whereIn('this.name', ['PHPUnit Zero', 'PHPUnit One'])->get();
        $csv = implode(',', array_map(fn($r) => $r->getId(), $ids));

        $this->assertSame(2, (int)\Dvups_lang::select()->whereIn('this.id', $csv)->count());
    }

    /**
     * ETAPE B.2 (v7.1) : whereBetween()/between() interpolaient leurs
     * bornes entre guillemets simples sans echappement.
     */
    public function testWhereBetweenWithAnApostropheInTheBoundsDoesNotError(): void
    {
        $this->assertSame(0, (int)\Dvups_lang::select()->whereBetween('this.name', ["x'", "y'"])->count());
    }
}
