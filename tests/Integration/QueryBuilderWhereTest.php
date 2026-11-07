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
}
