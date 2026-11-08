<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE H (securite langue), de bout en bout : un parametre ?lang
 * malveillant ne doit jamais faire planter une page (traversee de
 * repertoire vers un fichier hors lang/, injection de balise) - la page
 * doit retomber silencieusement sur la langue par defaut.
 *
 * Le controle precis ("aucun fichier hors lang/ n'est inclus") est verifie
 * au niveau unitaire par tests/Unit/LangSecurityTest.php (dv_valid_lang(),
 * dv_lang_file_path()), independamment de l'emplacement reel de LANG_DIR
 * pendant les tests (dossier temporaire recree a chaque run - voir
 * tests/bootstrap.php) : ce test e2e verifie seulement le comportement
 * observable cote client, qui doit rester sain en toutes circonstances.
 */
final class LangSecurityTest extends HttpTestCase
{
    public static function maliciousLangProvider(): array
    {
        return [
            'traversee simple' => ['../config/constante'],
            'traversee avec slash encode' => ['..%2F..%2Fcron'],
            'traversee profonde' => ['../../../../../../etc/passwd'],
            'balise script' => ['<script>alert(1)</script>'],
        ];
    }

    /**
     * @dataProvider maliciousLangProvider
     */
    public function testFrontPageNeverErrorsOnAMaliciousLangParam(string $lang): void
    {
        $res = $this->get('/?lang=' . $lang);

        $this->assertSame(200, $res['status'], "?lang=$lang ne doit jamais faire planter la page d'accueil");
        $this->assertNoServerError("avec ?lang=$lang");
        $this->assertStringContainsString('</html>', $res['body'], 'la page doit rester rendue entierement');
    }

    /**
     * @dataProvider maliciousLangProvider
     */
    public function testAdminLoginPageNeverErrorsOnAMaliciousLangParam(string $lang): void
    {
        $res = $this->get('admin/?lang=' . $lang);

        $this->assertNotSame(500, $res['status'], "?lang=$lang sur l'admin ne doit jamais provoquer une erreur serveur");
        $this->assertNoServerError("admin avec ?lang=$lang");
    }

    public function testScriptTagInLangNeverAppearsUnescapedInGeneratedLinks(): void
    {
        $res = $this->get('/?lang=' . rawurlencode('<script>alert(1)</script>'));

        $this->assertNoServerError();
        // d_url()/route() injecte local() dans les liens generes (voir
        // hello.blade.php, route("contact")) : si la valeur brute du
        // parametre lang y atterrissait, un tag <script> non echappe
        // apparaitrait tel quel dans le corps de la page.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $res['body'], 'la valeur brute de ?lang ne doit jamais apparaitre non echappee dans la page');
    }
}
