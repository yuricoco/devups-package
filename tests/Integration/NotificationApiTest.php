<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE M.2 : Notification::toUser()/toAdmin()/toRole() - API simple
 * ajoutee par-dessus Notification::on()/braodcast() (voir TODO.md pour
 * le choix de conserver l'API existante pour SMS/push groupes). Le
 * gabarit du message vient de t($type) (lang/xx.php).
 */
final class NotificationApiTest extends DatabaseTestCase
{
    private function makeUser(): \User
    {
        $id = \User::create(['username' => 'phpunit' . uniqid()]);
        return \User::find($id);
    }

    public function testToUserCreatesAnUnreadNotificationWithTheTranslatedMessage(): void
    {
        $user = $this->makeUser();

        $notification = \Notification::toUser($user, 'test.m2.welcome', ['name' => 'Ada']);

        $this->assertNotNull($notification->getId());
        $this->assertSame($user->getId(), $notification->user_id);
        $this->assertNull($notification->admin_id);
        $this->assertSame(t('test.m2.welcome', ['name' => 'Ada']), $notification->getContent());

        $reloaded = \Notification::find($notification->getId());
        $this->assertSame($user->getId(), $reloaded->user_id);
    }

    public function testToUserAcceptsAnIdInPlaceOfAUserInstance(): void
    {
        $user = $this->makeUser();

        $notification = \Notification::toUser($user->getId(), 'test.m2.byid');

        $this->assertSame($user->getId(), $notification->user_id);
    }

    public function testToAdminCreatesAnUnreadNotificationForTheAdmin(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();

        $notification = \Notification::toAdmin($admin, 'test.m2.admin');

        $this->assertSame($admin->getId(), $notification->admin_id);
        $this->assertNull($notification->user_id);
    }

    public function testToRoleNotifiesEveryAdminOfThatRole(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();
        $roleId = $admin->getDvups_role()->getId();
        $this->assertNotNull($roleId, 'fixture dv_admin doit avoir un role pour ce test');

        $sent = \Notification::toRole($roleId, 'test.m2.role');

        $this->assertNotEmpty($sent);
        foreach ($sent as $notification)
            $this->assertNotNull($notification->admin_id);
    }

    public function testReusesTheSameNotificationtypeAcrossCalls(): void
    {
        $user = $this->makeUser();
        \Notification::toUser($user, 'test.m2.reuse');
        \Notification::toUser($user, 'test.m2.reuse');

        $count = \Notificationtype::where(['_key' => 'test.m2.reuse', 'session' => 'user'])->count();
        $this->assertSame(1, $count, 'un seul Notificationtype doit etre cree, reutilise ensuite par sa cle');
    }
}
