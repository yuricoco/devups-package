<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Minishlink\WebPush\WebPush;

/**
 * ETAPE M.3 : Push_subscription::sendWebPush() (Web Push / VAPID, via
 * minishlink/web-push). Aucun appel reseau reel - Push_subscription::$webPushClient
 * est un WebPush construit sur un GuzzleHttp\Handler\MockHandler, qui
 * simule la reponse du service de push sans jamais sortir du process.
 */
final class PushSubscriptionWebPushTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        \Push_subscription::$webPushClient = null;
        parent::tearDown();
    }

    /**
     * Paire de cles VAPID (P-256) FIGEE, generee une fois hors suite de
     * tests - VAPID::createVapidKeys() appelle openssl_pkey_new(), qui
     * echoue sur certains environnements Windows sans OPENSSL_CONF valide
     * (hors de notre controle ici) ; aucun besoin de generer une paire a
     * chaque run, ces cles ne servent jamais a un envoi reel.
     */
    private const VAPID_PUBLIC_KEY = 'BEyaP2mlqTENO20SN81WFNVn8M1N69zUo2QUmB1IAHRIFQspK21tlrF6UVucsj04M7HQVgRwPdnVJsgJy33QbbY';
    private const VAPID_PRIVATE_KEY = 'hvTqHb1ZbRrcygJVp-wey12Pcuyt_ofdArtF_1i6QH8';

    private function fakeWebPushClient(Response ...$responses): WebPush
    {
        $mock = new MockHandler($responses);

        return new WebPush([
            'VAPID' => [
                'subject' => 'mailto:test@devups.test',
                'publicKey' => self::VAPID_PUBLIC_KEY,
                'privateKey' => self::VAPID_PRIVATE_KEY,
            ],
        ], [], 30, ['handler' => HandlerStack::create($mock)]);
    }

    private function makeSubscription(): \Push_subscription
    {
        $id = \Push_subscription::create([
            'subscription_type' => 'webpush',
            'user_id' => 1,
            'status' => 1,
            'endpoint' => 'https://push.example.test/endpoint/' . uniqid(),
            'public_key' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
            'auth_token' => 'tBHItJI5svbpez7KI4CCXg',
        ]);
        return \Push_subscription::find($id);
    }

    public function testSuccessfulSendReturnsTrue(): void
    {
        \Push_subscription::$webPushClient = $this->fakeWebPushClient(new Response(201));
        $sub = $this->makeSubscription();

        $result = $sub->sendWebPush('bonjour', ['foo' => 'bar']);

        $this->assertTrue($result);
        $this->assertNotNull(\Push_subscription::find($sub->getId())->getId(), 'un envoi reussi ne doit jamais supprimer l\'abonnement');
    }

    public function testExpiredSubscriptionIsAutomaticallyRemoved(): void
    {
        \Push_subscription::$webPushClient = $this->fakeWebPushClient(new Response(410));
        $sub = $this->makeSubscription();

        $result = $sub->sendWebPush('bonjour');

        $this->assertFalse($result);
        $this->assertNull(\Push_subscription::find($sub->getId())->getId(), 'une reponse 410 doit supprimer automatiquement l\'abonnement');
    }

    public function testNonWebpushSubscriptionTypeIsIgnored(): void
    {
        \Push_subscription::$webPushClient = $this->fakeWebPushClient(new Response(201));
        $id = \Push_subscription::create([
            'subscription_type' => 'fcm',
            'user_id' => 1,
            'status' => 1,
            'auth_token' => 'un-token-fcm',
        ]);
        $sub = \Push_subscription::find($id);

        $this->assertFalse($sub->sendWebPush('bonjour'), 'sendWebPush() ne doit rien faire pour un abonnement non-webpush');
    }
}
