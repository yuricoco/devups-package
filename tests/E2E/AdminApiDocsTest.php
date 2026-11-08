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

    /** @return int l'id du role cree */
    private function loginWithRole(array $privileges = []): int
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-apidocs' . uniqid(), 'alias' => 'x']);
        foreach ($privileges as [$entity, $action]) {
            $row = \Dvups_action::where('entity', $entity)->andwhere('action', $action)->firstOrNull();
            $actionId = ($row && $row->getId())
                ? $row->getId()
                : \Dvups_action::create(['entity' => $entity, 'action' => $action, 'label' => ucfirst($action)]);
            \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);
        }
        \Dvups_role::clearRightsCache($roleId);

        $login = 'e2e-apidocs' . uniqid();
        \Dvups_admin::create([
            'name' => 'e2e', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);
        $this->loginAdmin($login, 'secret');

        return $roleId;
    }

    /**
     * ETAPE P.6 : Documentation/read (action "virtuelle",
     * Dvups_action::VIRTUAL_ACTIONS) rend l'acces au Swagger attribuable a
     * un role non maitre - sans elle, seul "admin" (bypass de can())
     * pouvait l'ouvrir.
     */
    public function testNonMasterRoleWithTheDocumentationReadPrivilegeCanOpenTheSwaggerUiPage(): void
    {
        $this->loginWithRole([['Documentation', 'read']]);

        $res = $this->get('admin/apidocs');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('swagger-ui', $res['body']);
    }

    public function testNonMasterRoleWithoutTheDocumentationReadPrivilegeIsRefused(): void
    {
        $this->loginWithRole();

        $res = $this->get('admin/apidocs');

        $this->assertSame(403, $res['status']);
    }
}
