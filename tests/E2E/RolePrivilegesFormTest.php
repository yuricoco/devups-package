<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * Ecran back-office qui remplace dvups-right : le formulaire de
 * Dvups_role porte desormais une case a cocher par (entite, action)
 * de dvups_action. La soumettre doit aligner dvups_privilege sur les
 * cases cochees, et can() doit refleter le changement immediatement
 * (cache invalide par Dvups_role::clearRightsCache()).
 */
final class RolePrivilegesFormTest extends HttpTestCase
{
    private function actionId(string $entity, string $action): int
    {
        $row = \Dvups_action::where('entity', $entity)->andwhere('action', $action)->firstOrNull();
        if ($row && $row->getId())
            return $row->getId();
        return \Dvups_action::create(['entity' => $entity, 'action' => $action, 'label' => ucfirst($action)]);
    }

    public function testUpdatingTheRoleFormGrantsTheCheckedPrivilege(): void
    {
        $this->loginAdmin();

        $roleId = \Dvups_role::create(['name' => 'e2e-form-grant' . uniqid(), 'alias' => 'x']);
        $actionId = $this->actionId('Configuration', 'create');

        $res = $this->request('POST', 'admin/api/dvups_role/update?id=' . $roleId, [
            'dvups_role_form' => ['name' => 'e2e-form-grant-updated', 'alias' => 'x'],
            'privileges' => [(string)$actionId],
        ]);

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertSame(
            1,
            (int)\Dvups_privilege::where('dvups_role_id', $roleId)->andwhere('dvups_action_id', $actionId)->count()
        );

        $adminId = \Dvups_admin::create([
            'name' => 'e2e', 'login' => 'e2e' . uniqid(), 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);
        $_SESSION[\ADMIN] = serialize(\Dvups_admin::find($adminId));
        $this->assertTrue(can('create', 'Configuration'));
    }

    public function testUpdatingWithoutTheCheckboxRevokesThePrivilege(): void
    {
        $this->loginAdmin();

        $roleId = \Dvups_role::create(['name' => 'e2e-form-revoke' . uniqid(), 'alias' => 'x']);
        $actionId = $this->actionId('Configuration', 'delete');
        \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);

        $res = $this->request('POST', 'admin/api/dvups_role/update?id=' . $roleId, [
            'dvups_role_form' => ['name' => 'e2e-form-revoke-updated', 'alias' => 'x'],
        ]);

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertSame(
            0,
            (int)\Dvups_privilege::where('dvups_role_id', $roleId)->andwhere('dvups_action_id', $actionId)->count()
        );
    }
}
