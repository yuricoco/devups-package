<?php


/*namespace devupscms\ModulePushNotification\Controller;

use Configuration;
use Emaillog;
use Push_subscription;
use Push_subscriptionTable;
use Request;
use User;*/

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

// use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use dclass\devups\Controller\Controller;

class Push_subscriptionFrontController extends \Push_subscriptionController
{

    /**
     * @Auth(authorized=1)
     * @return array
     */
    public function register()
    {
        $subscription = Request::raw()['push_subscription'];
        // $user = User::find(Request::get('id'));
        $exist = Push_subscription::where(
            [
                //'endpoint' => $subscription['endpoint'],
                'auth_token' => $subscription['auth_token'],
                'subscription_id' => $subscription['subscription_id'],
            ])->firstOrNull();
        if (!$exist)
            $exist = Push_subscription::create((array)$subscription);
        /*else
            $exist->__update([
                'public_key' => $subscription['public_key'],
                'auth_token' => $subscription['auth_token'],
            ]);*/

        return [
            'success' => true,
            'exist' => $exist,
        ];
    }

    /**
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function generateKey()
    {
        $data = VAPID::createVapidKeys();

        Configuration::set('VAPID_PUBLIC_KEY', $data['publicKey']);
        Configuration::set('VAPID_PRIVATE_KEY', $data['privateKey']);
        return [
            'key' => $data['publicKey'],
        ];
    }

    /**
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function key()
    {
        return [
            'key' => Configuration::get('VAPID_PUBLIC_KEY'),
            //'key'=>'BIbPxqOqlFtjC4vHDXWWMRDtXEAg2_llqsYj2uprShJBbbIXsPksaKjAojNJzTyezeMeP3xaFDu6o_G5QVZAmvw'
        ];
    }

    public function pushNotificaiton(\User $user)
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:' . sm_from,
                'publicKey' => Configuration::get('VAPID_PUBLIC_KEY'),
                'privateKey' => Configuration::get('VAPID_PRIVATE_KEY'),
            ],
        ]);
        //$user = User::find(Request::get('id'));
        $subscriptions = Push_subscription::where(
            [
                'subscription_type' => 'user',
                'subscription_id' => $user->id,
            ])->get();

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification($subscription,
                json_encode([
                    'message' => 'Bonjour les gens',
                    'title' => 'Mon titre'
                ])
            );
        }
        $data = null;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $data = ("[v] Le message bien été envoyé {$endpoint}.");
            } else {
                $data = ("[x] Impossible d'envoyer le message {$endpoint}=> [$report->getReason()}");
            }
            Emaillog::create([
                "object" => "push notification",
                "log" => $data,
            ]);
        }
        return $data;
    }

    /**
     * @param User|null $user the receiver of the push notification
     * @param $message
     * @param $link
     * @param $icon
     * @return array
     */
    public function cloudMessagingPush(\User $user = null, $message, $link = "", $icon = null)
    {

//        if (!__prod)
//            return [];

        if ($user)
            $subscriptions = Push_subscription::where(
                [
                    'subscription_type' => 'user',
                    'subscription_id' => $user->id,
                ])->get();
        else
            $subscriptions = Push_subscription::where(
                [
                    'subscription_type' => 'user',
                ])->get();

        if (file_exists(ROOT . fcm_jwt_auth_file)) {

// define the scopes for your API call
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

// Créer des credentials à partir du fichier de compte de service
            $credentials = new ServiceAccountCredentials($scopes, ROOT . fcm_jwt_auth_file);

// Générer le token d'accès
            $this->token = $credentials->fetchAuthToken()['access_token'];
        }
        foreach ($subscriptions as $subscription) {
            $this->fcmPushNotification(
                $subscription->auth_token, $message, $link, $icon);
            //$message->addRecipient(new sngrl\PhpFirebaseCloudMessaging\Recipient\Device($subscription->auth_token));
        }


        /*$response = $client->send($message);
        $responseData = $response->json();*/

        return [
            'subscriptions' => $subscriptions,
        ];

    }

    public function testPushNotif($id)
    {
        $sub = Push_subscription::find($id);
        return $this->cloudMessagingPush(User::find($sub->subscription_id), 'test push notification');
    }
}
