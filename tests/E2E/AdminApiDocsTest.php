<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE N.3 : page Swagger UI back-office (route\Admin::apidocsView()),
 * protegee par can('read', 'Documentation') - le role "admin" (session de
 * test) passe toujours (bypass maitre de can()), une session anonyme est
 * refusee.
 */
final class AdminApiDocsTest extends HttpTestCase
{
    /**
     * Doit s'executer avant login (jar de cookies partage pour toute la
     * classe, cf. HttpTestCase::$cookieJar) - l'ordre de declaration des
     * methodes de test correspond a l'ordre d'execution de PHPUnit ici
     * (pas de randomisation configuree, cf. phpunit.xml.dist).
     */
    public function testAnonymousRequestIsRefused(): void
    {
        $res = $this->get('admin/apidocs');

        $this->assertSame(403, $res['status']);
    }

    public function testAdminSessionCanOpenTheSwaggerUiPage(): void
    {
        $this->loginAdmin();

        $res = $this->get('admin/apidocs');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('swagger-ui', $res['body']);
        $this->assertStringContainsString('"Admin', $res['body'], 'le spec embarque doit inclure le tag des routes admin (session)');
    }
}
