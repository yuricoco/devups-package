<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * creer un pays depuis l'ecran admin (admin/devupscms/ModuleLocation/
 * country/list -> Creer) levait systematiquement une 500 : "SQLSTATE
 * [23000]: Integrity constraint violation: 1048 Column 'iso3' cannot be
 * null". CountryForm ne collecte ni "iso3" ni "numcode" (seul
 * Country::seed(), teste par LocationSeedTest, les renseigne depuis
 * database/seed/countries.php) - AdminScreensTest ne le voyait jamais
 * car il ne teste que le chargement des formulaires, pas une soumission
 * reelle. Corrige en donnant aux deux proprietes une valeur par defaut
 * '' (la colonne SQL reste NOT NULL, mais '' est une chaine valide).
 */
final class CountryCreateViaFormTest extends HttpTestCase
{
    private ?int $countryId = null;

    protected function tearDown(): void
    {
        if ($this->countryId !== null) {
            $pdo = \Model::pdo();
            $pdo->exec("DELETE FROM country_lang WHERE country_id = " . $this->countryId);
            $pdo->exec("DELETE FROM country WHERE id = " . $this->countryId);
        }
        parent::tearDown();
    }

    public function testCreatingACountryFromTheAdminScreenSucceeds(): void
    {
        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'admin/api/country/create', [
            'country_form' => [
                'name' => 'Etapev4landRegression',
                'iso' => 'ZZ',
                'phonecode' => '',
                'status' => '',
            ],
        ]);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur country/create :\n" . implode("\n", $errors));

        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "creation echouee :\n" . $res['body']);
        $this->countryId = (int)$data['country']['id'];
    }
}
