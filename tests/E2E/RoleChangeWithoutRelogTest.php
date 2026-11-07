<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * $_SESSION[ADMIN] est un snapshot serialise de Dvups_admin a la
 * connexion : can() ne doit pas se fier a son dvups_role (figea a cet
 * instant) mais relire l'admin courant, pour qu'un changement de role
 * fait par un autre admin soit visible sans que la session en cours se
 * reconnecte.
 */
final class RoleChangeWithoutRelogTest extends HttpTestCase
{
    public function testChangingTheRoleInDbTakesEffectOnTheNextRequestWithoutRelogin(): void
    {
        $powerlessRoleId = \Dvups_role::create(['name' => 'e2e-powerless' . uniqid(), 'alias' => 'x']);
        $grantedRoleId = \Dvups_role::create(['name' => 'e2e-granted' . uniqid(), 'alias' => 'x']);

        $actionRow = \Dvups_action::where('entity', 'Configuration')->andwhere('action', 'create')->firstOrNull();
        $actionId = ($actionRow && $actionRow->getId())
            ? $actionRow->getId()
            : \Dvups_action::create(['entity' => 'Configuration', 'action' => 'create', 'label' => 'Create']);
        \Dvups_privilege::create(['dvups_role_id' => $grantedRoleId, 'dvups_action_id' => $actionId]);
        \Dvups_role::clearRightsCache($grantedRoleId);

        $login = 'e2e' . uniqid();
        $adminId = \Dvups_admin::create([
            'name' => 'e2e', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $powerlessRoleId,
        ]);
        $this->loginAdmin($login, 'secret');

        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_BEFORE_ROLE_CHANGE', '_value' => 'x'],
        ]);
        $this->assertSame(403, $res['status'], 'role sans privilege : refuse avant changement');

        // Changement de role fait "a cote" (un autre admin), sans toucher a
        // la session en cours : $_SESSION[ADMIN] du navigateur de cet admin
        // reste le meme cookie, le meme blob serialise.
        \Dvups_admin::find($adminId)->__update(['dvups_role_id' => $grantedRoleId]);

        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_AFTER_ROLE_CHANGE', '_value' => 'x', '_type' => 'string', 'comment' => 'e2e'],
        ]);
        $this->assertTrue(
            $this->json($res)['success'] ?? false,
            'le nouveau role (change sans reconnexion) doit etre pris en compte : ' . $res['body']
        );
        $this->assertSame(1, (int)\Configuration::where('this._key', 'E2E_AFTER_ROLE_CHANGE')->count());
    }
}
