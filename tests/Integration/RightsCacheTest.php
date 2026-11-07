<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

final class RightsCacheTest extends DatabaseTestCase
{
    private ?int $roleId = null;

    protected function tearDown(): void
    {
        if ($this->roleId !== null) {
            \Dvups_role::clearRightsCache($this->roleId);
            $this->roleId = null;
        }
        unset($_SESSION[\ADMIN]);
        parent::tearDown();
    }

    private function makeRoleWithPrivilege(string $entity, string $action): int
    {
        $roleId = \Dvups_role::create(['name' => 'phpunit' . uniqid(), 'alias' => 'phpunit']);
        $this->roleId = $roleId;

        $actionRow = \Dvups_action::where('entity', $entity)->andwhere('action', $action)->firstOrNull();
        if (!$actionRow || !$actionRow->getId())
            $actionId = \Dvups_action::create(['entity' => $entity, 'action' => $action, 'label' => ucfirst($action)]);
        else
            $actionId = $actionRow->getId();

        \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);

        return $roleId;
    }

    public function testCompiledRightsReflectsGrantedPrivileges(): void
    {
        $roleId = $this->makeRoleWithPrivilege('Dvups_role', 'update');

        $rights = \Dvups_role::compiledRights($roleId);

        $this->assertTrue($rights['Dvups_role']['update'] ?? false);
        $this->assertArrayNotHasKey('delete', $rights['Dvups_role'] ?? []);
    }

    public function testCompiledRightsIsCachedUntilCleared(): void
    {
        $roleId = $this->makeRoleWithPrivilege('Dvups_role', 'update');
        \Dvups_role::compiledRights($roleId); // ecrit le cache

        // ajoute un privilege APRES compilation : non visible tant que le cache n'est pas invalide
        $actionId = \Dvups_action::create(['entity' => 'Dvups_role', 'action' => 'delete', 'label' => 'Delete']);
        \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);

        $stale = \Dvups_role::compiledRights($roleId);
        $this->assertArrayNotHasKey('delete', $stale['Dvups_role'] ?? []);

        \Dvups_role::clearRightsCache($roleId);

        $fresh = \Dvups_role::compiledRights($roleId);
        $this->assertTrue($fresh['Dvups_role']['delete'] ?? false);
    }

    public function testCanDeniesAnonymousVisitor(): void
    {
        unset($_SESSION[\ADMIN]);
        $this->assertFalse(can('update', \Dvups_role::class));
    }

    public function testCanGrantsOnlyTheDeclaredAction(): void
    {
        $roleId = $this->makeRoleWithPrivilege('Dvups_role', 'update');
        $adminId = \Dvups_admin::create([
            'name' => 'phpunit', 'login' => 'phpunit' . uniqid(), 'password' => sha1('x'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);
        $_SESSION[\ADMIN] = serialize(\Dvups_admin::find($adminId));

        $this->assertTrue(can('update', 'Dvups_role'));
        $this->assertFalse(can('delete', 'Dvups_role'));
        $this->assertFalse(can('update', 'Dvups_privilege'));
    }

    public function testCanAlwaysGrantsTheAdminRole(): void
    {
        $adminRole = \Dvups_role::where('name', 'admin')->firstOrNull();
        $this->assertNotNull($adminRole, 'seed config_data.sql attendu (role admin id=1)');

        $adminId = \Dvups_admin::create([
            'name' => 'phpunit-master', 'login' => 'phpunit' . uniqid(), 'password' => sha1('x'),
            'firstconnexion' => 0, 'dvups_role_id' => $adminRole->getId(),
        ]);
        $_SESSION[\ADMIN] = serialize(\Dvups_admin::find($adminId));

        $this->assertTrue(can('delete', 'Dvups_role'));
        $this->assertTrue(can('anything_undeclared', 'Dvups_role'));
    }
}
