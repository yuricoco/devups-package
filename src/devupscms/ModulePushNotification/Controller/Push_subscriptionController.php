<?php


use dclass\devups\Controller\Controller;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;

class Push_subscriptionController extends Controller
{

    public $token;

    public function listView()
    {

        $this->datatable = Push_subscriptionTable::init(new Push_subscription())->buildindextable();

        self::$jsfiles[] = Push_subscription::classpath('Resource/js/push_subscriptionCtrl.js');

        $this->entitytarget = 'Push_subscription';
        $this->title = "Manage Push_subscription";

        $this->renderListView();

    }

    public function datatable()
    {

        return ['success' => true,
            'datatable' => Push_subscriptionTable::init(new Push_subscription())->router()->getTableRest(),
        ];

    }

    public function createAction($push_subscription_form = null)
    {
        extract($_POST);

        $push_subscription = $this->form_fillingentity(new Push_subscription(), $push_subscription_form);
        if ($this->error) {
            return array('success' => false,
                'push_subscription' => $push_subscription,
                'action' => 'create',
                'error' => $this->error);
        }


        $id = $push_subscription->__insert();
        return array('success' => true,
            'push_subscription' => $push_subscription,
            'tablerow' => Push_subscriptionTable::init()->router()->getSingleRowRest($push_subscription),
            'detail' => '');

    }

    public function updateAction($id, $push_subscription_form = null)
    {
        extract($_POST);

        $push_subscription = $this->form_fillingentity(new Push_subscription($id), $push_subscription_form);

        if ($this->error) {
            return array('success' => false,
                'push_subscription' => $push_subscription,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $push_subscription->__update();
        return array('success' => true,
            'push_subscription' => $push_subscription,
            'tablerow' => Push_subscriptionTable::init()->router()->getSingleRowRest($push_subscription),
            'detail' => '');

    }


    public function deleteAction($id)
    {

        Push_subscription::where('this.id', $id)->delete(true);

        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Push_subscription::where("id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

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
        else
            $exist->__update([
                'public_key' => $subscription['public_key'],
                'auth_token' => $subscription['auth_token'],
            ]);

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
                'subject' => 'mailto:editorial.3ag@gmail.com',
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
                $data = ("[x] Impossible d'envoyer le message {$endpoint}: {$report->getReason()}");
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

        $config = ROOT . 'config/' . fcm_jwt_auth_file;
        if (!file_exists($config))
            return ['message' => 'config file not exist'];

        if (!$user)
            return null;

        $subscriptions = Push_subscription::where(
            [
                'user_id' => $user->id,
            ])->get();
        /*else
            $subscriptions = Push_subscription::where(
                [
                    'subscription_type' => 'user',
                ])->get();*/

// define the scopes for your API call
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

// Créer des credentials à partir du fichier de compte de service
        $credentials = new ServiceAccountCredentials($scopes, $config);

// Générer le token d'accès
        Push_subscription::$access_token = $credentials->fetchAuthToken()['access_token'];

        foreach ($subscriptions as $subscription) {
            $subscription->fcmPushNotification($message,
                [
                    'id' => '22',
                    'entity' => '2',
                    'action' => 'New chapter',
                    'parent_id' => 'New chapter',
                    'url' => 'reader3ag://home',
                ], $icon);
            /*$this->fcmPushNotification(
                $subscription->auth_token, $message, $link, $icon);*/
            //$message->addRecipient(new sngrl\PhpFirebaseCloudMessaging\Recipient\Device($subscription->auth_token));
        }


        /*$response = $client->send($message);
        $responseData = $response->json();*/

        return [
            'subscriptions' => $subscriptions,
        ];

    }

    public function fcmPushNotification($token, $message, $postlink, $icon = null)
    {

        if (!$icon)
            $icon = __env . '/logo.png';
// Push Data's
        $data = array(
            // "to" => "$token",
            "message" => [
                "token" => $token,
                "notification" => array(
                    "title" => PROJECT_NAME,
                    "body" => "$message",
                    //"icon" => $icon, // Replace https://example.com/icon.png with your PUSH ICON URL
                    //"click_action" => "$postlink"
                ),
                "data" => [
                    'id' => "15",
                    'hour_to' => '10',
                    'cost' => '53',
                    'type' => 'username',
                    'hour_from' => '18'
                ],
                "android" => [
                    "notification" => [
                        "click_action" => "TOP_STORY_ACTIVITY"
                    ]
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "category" => "NEW_MESSAGE_CATEGORY"
                        ]
                    ]
                ]
            ],
        );

// Print Output in JSON Format
        $data_string = json_encode($data);

// FCM API Token URL
        $url = "https://fcm.googleapis.com/v1/projects/" . fcm_project_id . "/messages:send";

//Curl Headers
        $headers = array
        (
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

// Variable for Print the Result
        $result = curl_exec($ch);

        Emaillog::create([
            "object" => "push notification",
            "log" => $result,
        ]);
        // dv_dump($result, $data);
        $json = json_decode($result);
//        if (isset($json->error))
//            return null;

        if (isset($json->error)) {
            if ($json->error->code == 404)
                Push_subscription::where("auth_token", $token)->delete();
        }

        curl_close($ch);

        // return $result;

    }

    public function testPushNotif($id)
    {
        $sub = Push_subscription::find($id);
        return $this->cloudMessagingPush(User::find($sub->user_id), 'test push notification');
    }

}
