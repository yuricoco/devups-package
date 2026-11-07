<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * can() en bout en bout : un role sans privilege attribue se voit refuser
 * (403) les actions CRUD, meme authentifie ; le role "admin" passe
 * toujours (bypass maitre).
 */
final class AdminPrivilegeTest extends HttpTestCase
{
    private function loginAsPowerlessRole(): void
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-powerless' . uniqid(), 'alias' => 'powerless']);
        $login = 'e2e' . uniqid();
        \Dvups_admin::create([
            'name' => 'e2e', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);
        $this->loginAdmin($login, 'secret');
    }

    public function testRoleWithoutPrivilegeIsRefusedOnAnAdminApiAction(): void
    {
        $this->loginAsPowerlessRole();

        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_PRIVILEGE', '_value' => 'x'],
        ]);

        $this->assertSame(403, $res['status']);
        $this->assertSame(0, (int)\Configuration::where('this._key', 'E2E_PRIVILEGE')->count());
    }

    public function testGrantingThePrivilegeLiftsTheRefusal(): void
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-granted' . uniqid(), 'alias' => 'granted']);
        $login = 'e2e' . uniqid();
        \Dvups_admin::create([
            'name' => 'e2e', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);

        $actionRow = \Dvups_action::where('entity', 'Configuration')->andwhere('action', 'create')->firstOrNull();
        $actionId = ($actionRow && $actionRow->getId())
            ? $actionRow->getId()
            : \Dvups_action::create(['entity' => 'Configuration', 'action' => 'create', 'label' => 'Create']);
        \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);
        \Dvups_role::clearRightsCache($roleId);

        $this->loginAdmin($login, 'secret');

        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_GRANTED', '_value' => 'x', '_type' => 'string', 'comment' => 'e2e'],
        ]);

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertSame(1, (int)\Configuration::where('this._key', 'E2E_GRANTED')->count());
    }
}
