<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE J.3 : Country::seed() (CLI `php devups location:seed`) peuple la
 * table country a partir de database/seed/countries.php - de facon
 * idempotente (ni doublon, ni perte des mises a jour si relance).
 *
 * ETAPE V : Country::seed() cree un NOUVEAU pays via Model::__insert(),
 * qui (Country ayant dvtranslate=true) passe par
 * DBAL::createDbal() -> DBAL::_createDbal(<nom_de_classe>, ...) pour
 * creer la ligne country_lang compagnon. Ce dernier utilisait
 * $this->table (TOUJOURS en minuscules) comme nom de CLASSE PHP
 * ("country_lang" au lieu de "Country_lang") - en executant
 * `php devups location:seed` pour de vrai sur la base de dev (processus
 * CLI frais, aucune classe pre-chargee), l'autoload composer (classmap,
 * sensible a la casse) echouait : "Class 'country_lang' does not
 * exist". Corrige dans DBAL::createDbal() (utilise $this->objectName,
 * qui garde la casse reelle de la classe). Ce test ne reproduit PAS le
 * plantage lui-meme (tests/bootstrap.php precharge deja toutes les
 * entites via Doctrine SchemaTool, ce qui masque un nom de classe en
 * minuscules - PHP est insensible a la casse pour une classe DEJA
 * declaree) mais verifie que la ligne country_lang compagnon est bien
 * creee avec les bonnes donnees, ce que le code fautif (avant
 * correction) ne faisait jamais echouer ICI mais aurait echoue en CLI.
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

    public function testSeedCreatesTheCompanionCountryLangRowForEachActiveLanguage(): void
    {
        \Country::seed();

        $activeLangCount = $this->countRows('dvups_lang');
        $this->assertGreaterThan(0, $activeLangCount, 'precondition : au moins une langue active');

        $countryCount = $this->countRows('country');
        $this->assertSame(
            $countryCount * $activeLangCount,
            $this->countRows('country_lang'),
            'chaque pays doit avoir exactement une ligne country_lang par langue active'
        );
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
