<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE J.3 : Country::seed() (CLI `php devups location:seed`) peuple la
 * table country a partir de database/seed/countries.php - de facon
 * idempotente (ni doublon, ni perte des mises a jour si relance).
 */
final class LocationSeedTest extends DatabaseTestCase
{
    public function testSeedCreatesOneCountryPerSeedRow(): void
    {
        $rows = require ROOT . 'database/seed/countries.php';

        $result = \Country::seed();

        $this->assertSame(count($rows), $result['total']);
        $this->assertSame($this->countRows('country'), $result['total']);

        $france = \Country::where('iso', 'FR')->__getOne();
        $this->assertNotNull($france);
        $this->assertSame('France', $france->getName());
        $this->assertSame(33, $france->getPhonecode());
    }

    public function testSeedIsIdempotentAndUpdatesRatherThanDuplicates(): void
    {
        $first = \Country::seed();
        $countAfterFirst = $this->countRows('country');

        $second = \Country::seed();

        $this->assertSame(0, $second['created'], 'une seconde execution ne doit creer aucune ligne');
        $this->assertSame($first['total'], $second['updated']);
        $this->assertSame($countAfterFirst, $this->countRows('country'), 'le nombre de pays ne doit pas doubler');
    }
}
