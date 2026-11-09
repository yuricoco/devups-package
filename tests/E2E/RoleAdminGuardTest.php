<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * Dvups_roleController et Dvups_adminController ne passent pas par
 * Request::Controller() quand ils sont atteints via le point d'entree
 * historique services.php?path=... (switch, appel direct de la methode) :
 * la garde can() posee dans Request::Controller() ne les couvre pas.
 * Chaque methode sensible porte donc sa propre garde can() (meme
 * mecanisme que CrudTrait::forbidIfCannot).
 *
 * Seul le role maitre "admin" peut modifier les privileges d'un role ou
 * le role d'un admin, meme si un autre role a recu 'update' sur ces
 * entites.
 */
final class RoleAdminGuardTest extends HttpTestCase
{
    private function loginWithRole(array $privileges = []): array
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-guard' . uniqid(), 'alias' => 'x']);
        foreach ($privileges as [$entity, $action]) {
            $row = \Dvups_action::where('entity', $entity)->andwhere('action', $action)->firstOrNull();
            $actionId = ($row && $row->getId())
                ? $row->getId()
                : \Dvups_action::create(['entity' => $entity, 'action' => $action, 'label' => ucfirst($action)]);
            \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);
        }
        \Dvups_role::clearRightsCache($roleId);

        $login = 'e2e' . uniqid();
        \Dvups_admin::create([
            'name' => 'e2e', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);
        $this->loginAdmin($login, 'secret');

        return ['roleId' => $roleId];
    }

    public function testServicesDvupsRoleUpdateIsRefusedWithoutPrivilege(): void
    {
        $this->loginWithRole();
        $targetRoleId = \Dvups_role::create(['name' => 'e2e-target' . uniqid(), 'alias' => 'x']);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_role.update&id=' . $targetRoleId,
            ['dvups_role_form' => ['name' => 'e2e-hacked', 'alias' => 'x']]
        );

        $this->assertSame(403, $res['status']);
        $this->assertNotSame('e2e-hacked', \Dvups_role::find($targetRoleId)->getName());
    }

    public function testServicesDvupsAdminUpdateIsRefusedWithoutPrivilege(): void
    {
        $this->loginWithRole();
        $otherRoleId = \Dvups_role::create(['name' => 'e2e-other' . uniqid(), 'alias' => 'x']);
        $targetAdminId = \Dvups_admin::create([
            'name' => 'cible', 'login' => 'e2e-cible' . uniqid(), 'password' => sha1('x'),
            'firstconnexion' => 0, 'dvups_role_id' => $otherRoleId,
        ]);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_admin.update&id=' . $targetAdminId,
            ['dvups_admin_form' => ['name' => 'e2e-hacked']]
        );

        $this->assertSame(403, $res['status']);
        $this->assertNotSame('e2e-hacked', \Dvups_admin::find($targetAdminId)->getName());
    }

    public function testNonMasterRoleWithUpdateCannotChangeRolePrivileges(): void
    {
        $this->loginWithRole([['Dvups_role', 'update']]);

        $targetRoleId = \Dvups_role::create(['name' => 'e2e-privtarget' . uniqid(), 'alias' => 'x']);
        $actionId = \Dvups_action::where('entity', 'Configuration')->andwhere('action', 'delete')->firstOrNull();
        $actionId = ($actionId && $actionId->getId())
            ? $actionId->getId()
            : \Dvups_action::create(['entity' => 'Configuration', 'action' => 'delete', 'label' => 'Delete']);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_role.update&id=' . $targetRoleId,
            [
                'dvups_role_form' => ['name' => 'e2e-privtarget-renamed', 'alias' => 'x'],
                'privileges' => [(string)$actionId],
            ]
        );

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertSame('e2e-privtarget-renamed', \Dvups_role::find($targetRoleId)->getName());
        $this->assertSame(
            0,
            (int)\Dvups_privilege::where('dvups_role_id', $targetRoleId)->andwhere('dvups_action_id', $actionId)->count(),
            'un role non maitre avec juste "update" sur Dvups_role ne doit pas pouvoir accorder de privilege'
        );
    }

    public function testNonMasterRoleWithUpdateCannotChangeAnAdminsRole(): void
    {
        $this->loginWithRole([['Dvups_admin', 'update']]);

        $originalRoleId = \Dvups_role::create(['name' => 'e2e-origrole' . uniqid(), 'alias' => 'x']);
        $otherRoleId = \Dvups_role::create(['name' => 'e2e-newrole' . uniqid(), 'alias' => 'x']);
        $targetAdminId = \Dvups_admin::create([
            'name' => 'cible', 'login' => 'e2e-cible' . uniqid(), 'password' => sha1('x'),
            'firstconnexion' => 0, 'dvups_role_id' => $originalRoleId,
        ]);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_admin.update&id=' . $targetAdminId,
            ['dvups_admin_form' => ['name' => 'cible-renommee', 'dvups_role.id' => $otherRoleId]]
        );

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $reloaded = \Dvups_admin::find($targetAdminId);
        $this->assertSame('cible-renommee', $reloaded->getName());
        $this->assertSame(
            $originalRoleId,
            $reloaded->dvups_role->getId(),
            'un role non maitre avec juste "update" sur Dvups_admin ne doit pas pouvoir changer le role d\'un admin'
        );
    }

    public function testMasterRoleCanChangeRolePrivilegesAndAdminRole(): void
    {
        $this->loginAdmin(); // dv_admin, role maitre "admin"

        $targetRoleId = \Dvups_role::create(['name' => 'e2e-mastertarget' . uniqid(), 'alias' => 'x']);
        $actionRow = \Dvups_action::where('entity', 'Configuration')->andwhere('action', 'export')->firstOrNull();
        $actionId = ($actionRow && $actionRow->getId())
            ? $actionRow->getId()
            : \Dvups_action::create(['entity' => 'Configuration', 'action' => 'export', 'label' => 'Export']);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_role.update&id=' . $targetRoleId,
            [
                'dvups_role_form' => ['name' => 'e2e-mastertarget', 'alias' => 'x'],
                'privileges' => [(string)$actionId],
            ]
        );
        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertSame(
            1,
            (int)\Dvups_privilege::where('dvups_role_id', $targetRoleId)->andwhere('dvups_action_id', $actionId)->count()
        );
    }

    /**
     * ETAPE F.2 (v7.1) : regression - Dvups_adminController::updateAction()
     * construit l'entite a mettre a jour par id seul (new Dvups_admin($id)),
     * jamais hydratee avant application du formulaire. entityKeyUpdate()
     * (ModelTrait) traitait alors CHAQUE propriete actuellement non null
     * comme "modifiee", donc toute colonne NOT NULL absente du formulaire
     * (login, password...) etait reecrite a NULL en base - invisible sous
     * sql_mode tolerant (MariaDB, UPDATE NULL -> defaut implicite + warning),
     * erreur 1048 "Column 'login' cannot be null" sous sql_mode strict
     * (MySQL 8, celui de la CI). Changer UNIQUEMENT le role d'un admin ne
     * doit toucher aucune autre colonne.
     */
    public function testUpdatingOnlyTheRoleLeavesOtherFieldsIntact(): void
    {
        $this->loginAdmin(); // dv_admin, role maitre "admin"

        $originalRoleId = \Dvups_role::create(['name' => 'e2e-onlyrole-orig' . uniqid(), 'alias' => 'x']);
        $newRoleId = \Dvups_role::create(['name' => 'e2e-onlyrole-new' . uniqid(), 'alias' => 'x']);
        $login = 'e2e-onlyrole' . uniqid();
        $password = sha1('secret');
        $email = 'e2e-onlyrole' . uniqid() . '@example.test';
        $targetAdminId = \Dvups_admin::create([
            'name' => 'cible-intacte', 'login' => $login, 'password' => $password, 'email' => $email,
            'firstconnexion' => 0, 'dvups_role_id' => $originalRoleId,
        ]);

        $res = $this->request(
            'POST',
            'src/devups/ModuleAdmin/services.php?path=dvups_admin.update&id=' . $targetAdminId,
            ['dvups_admin_form' => ['dvups_role.id' => $newRoleId]]
        );

        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $reloaded = \Dvups_admin::find($targetAdminId);
        $this->assertSame($newRoleId, $reloaded->dvups_role->getId());
        $this->assertSame('cible-intacte', $reloaded->getName());
        $this->assertSame($login, $reloaded->getLogin());
        $this->assertSame($password, $reloaded->getPassword());
        $this->assertSame($email, $reloaded->getEmail());
    }
}
