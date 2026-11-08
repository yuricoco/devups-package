<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * admin/devupscms/ModuleLocation/country/list renvoyait un 500 ("Call to
 * a member function hydrate() on null") des que la table `country`
 * contient des lignes, a cause d'une colonne de CountryTable
 * (buildindextable()) qui referencait encore la relation `continent.name`
 * - Continent a pourtant ete entierement retire du code (ETAPE J, "seul
 * Country est conserve").
 *
 * AdminScreensTest::screens() couvre deja cette URL mais ne le voyait
 * jamais : la table `country` y est toujours VIDE (aucun seed), donc la
 * boucle de rendu par ligne (celle qui resout `continent.name`) ne
 * s'executait jamais. Ce test seede au moins une ligne avant d'appeler
 * l'ecran, pour exercer reellement ce chemin.
 */
final class CountryListWithDataTest extends HttpTestCase
{
    public function testListRendersWithoutErrorOnceCountryHasRows(): void
    {
        \Country::seed();

        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->get('admin/devupscms/ModuleLocation/country/list');
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status']);
        $this->assertSame([], $errors, "erreurs PHP sur country/list :\n" . implode("\n", $errors));
        $this->assertStringContainsString('</html>', $res['body'], 'rendu tronque sur country/list');
    }
}
