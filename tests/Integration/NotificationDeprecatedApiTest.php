<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE P.5 : une seule API publique de notification. toUser()/toAdmin()/
 * toRole() (couvert par NotificationApiTest) restent l'API recommandee ;
 * on() et braodcast() (faute de frappe historique, renommee broadcast())
 * restent fonctionnels - conserves plutot que migres, car on() couvre un
 * cas (entite quelconque, contenu multilingue, SMS/push) que l'API simple
 * ne couvre pas - mais sont desormais @deprecated et journalisent un
 * avertissement (Log::info) au premier appel.
 */
final class NotificationDeprecatedApiTest extends DatabaseTestCase
{
    /**
     * Le garde-fou "une seule entree de log par methode depreciee, pour
     * toute la duree du PROCESSUS" (Notification::$loggedDeprecations)
     * survivrait sinon au premier test de cette classe qui appelle
     * on()/braodcast() dans la suite - chaque test le reinitialise pour
     * rester independant des autres.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $prop = new \ReflectionProperty(\Notification::class, 'loggedDeprecations');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    private function makeUser(): \User
    {
        $id = \User::create(['username' => 'phpunit' . uniqid()]);
        return \User::find($id);
    }

    public function testBraodcastAliasStillBroadcastsLikeBroadcast(): void
    {
        $user = $this->makeUser();
        \Notificationtype::create([
            'entity' => 'user', '_key' => 'p5.alias', 'session' => 'user',
            'content' => ['fr' => 'Bonjour :name'],
        ]);
        $notification = new \Notification();
        $notification->notificationtype = \Notificationtype::where('_key', 'p5.alias')->firstOrNull();
        $notification->setEntity('user');
        $notification->setEntityid($user->getId());

        \Notification::braodcast($notification, [$user], ['name' => 'Ada']);

        $sent = \Notification::where('user_id', $user->getId())->firstOrNull();
        $this->assertNotNull($sent->getId());
        $this->assertSame('Bonjour Ada', $sent->getContent());
    }

    public function testBraodcastLogsADeprecationWarningOnFirstCall(): void
    {
        $user = $this->makeUser();
        \Notificationtype::create([
            'entity' => 'user', '_key' => 'p5.alias.log', 'session' => 'user',
            'content' => ['fr' => 'x'],
        ]);
        $notification = new \Notification();
        $notification->notificationtype = \Notificationtype::where('_key', 'p5.alias.log')->firstOrNull();
        $notification->setEntity('user');
        $notification->setEntityid($user->getId());

        $before = (int)$this->countRows('dvups_log', "message LIKE 'Notification::braodcast%'");
        \Notification::braodcast($notification, [$user], []);
        $after = (int)$this->countRows('dvups_log', "message LIKE 'Notification::braodcast%'");

        $this->assertGreaterThan($before, $after);
    }

    public function testOnBuilderStillSendsAndLogsADeprecationWarning(): void
    {
        $user = $this->makeUser();

        $before = (int)$this->countRows('dvups_log', "message LIKE 'Notification::on%'");
        \Notification::on($user, 'p5.on.event')->send([$user], []);
        $after = (int)$this->countRows('dvups_log', "message LIKE 'Notification::on%'");

        $this->assertGreaterThan($before, $after);
        $sent = \Notification::where('user_id', $user->getId())->firstOrNull();
        $this->assertNotNull($sent->getId(), 'on()->send() doit toujours fonctionner (API conservee, pas supprimee)');
    }
}
