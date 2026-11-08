<?php
// user \dclass\devups\model\Model;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * @Entity @Table(name="push_subscription")
 * */
class Push_subscription extends Model implements JsonSerializable, SubscriptionInterface

{

    public static $access_token;

    /**
     * ETAPE M.3 : client Web Push injectable pour les tests (jamais de
     * veritable appel reseau pendant la suite) - voir sendWebPush().
     * @var WebPush|null
     */
    public static $webPushClient = null;

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="subscription_type", type="string" , length=25 )
     * @var string
     **/
    protected $subscription_type;
    /**
     * @Column(name="user_id", type="integer"  )
     * @var integer
     **/
    protected $user_id;
    /**
     * @Column(name="status", type="integer" , options={"default": "1"} )
     * @var string
     **/
    protected $status;
    /**
     * ETAPE M.3 : endpoint du service de push du navigateur
     * (PushSubscription.endpoint cote client) - indispensable pour un
     * VRAI envoi Web Push, manquait jusqu'ici (getEndpoint() existait
     * deja mais lisait une propriete jamais declaree).
     * @Column(name="endpoint", type="text", nullable=true)
     * @var string
     **/
    protected $endpoint;
    /**
     * @Column(name="public_key",  type="text" , nullable=true )
     * @var string
     **/
    protected $public_key;
    /**
     * @Column(name="auth_token", type="text" , nullable=true)
     * @var string
     **/
    protected $auth_token;
    /**
     * @Column(name="content_type", type="string" , length=25 , nullable=true)
     * @var string
     **/
    protected $content_type;


    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

    }

    public function getId()
    {
        return $this->id;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return parent::jsonSerialize();
    }

    public function getPublicKey(): ?string
    {
        return $this->public_key;
    }

    public function getAuthToken(): ?string
    {
        return $this->auth_token;
    }

    public function getContentEncoding(): ?string
    {
        // aes128gcm (rfc8291) : encodage standard actuel, contrairement a
        // aesgcm (obsolete, utilise par d'anciens navigateurs uniquement).
        return 'aes128gcm';
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * ETAPE M.3 : client Web Push (VAPID), cles dans .env uniquement
     * (jamais versionnees, jamais en base - independant de
     * Push_subscriptionFrontController::generateKey()/key(), qui les
     * stocke dans Configuration pour l'ancien flux FCM/mobile, laisse
     * tel quel). Retourne null si les cles ne sont pas configurees -
     * l'appelant doit alors renoncer silencieusement a l'envoi.
     */
    public static function webPushClient(): ?WebPush
    {
        if (self::$webPushClient)
            return self::$webPushClient;

        if (!VAPID_PUBLIC_KEY || !VAPID_PRIVATE_KEY)
            return null;

        return new WebPush([
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ]);
    }

    /**
     * Envoie une notification Web Push a CET abonnement. Supprime
     * automatiquement l'abonnement si le navigateur repond qu'il n'existe
     * plus (410 Gone - MessageSentReport::isSubscriptionExpired()).
     * Ne fait rien (retourne false) si subscription_type n'est pas
     * 'webpush', ou si aucune cle VAPID n'est configuree.
     */
    public function sendWebPush(string $message, array $payload = []): bool
    {
        if ($this->subscription_type !== 'webpush')
            return false;

        $client = self::webPushClient();
        if (!$client)
            return false;

        $report = $client->sendOneNotification($this, json_encode([
            'message' => $message,
            'data' => $payload,
        ]));

        if ($report->isSubscriptionExpired()) {
            \Log::info('push_subscription #' . $this->id . ' expiree (410), suppression automatique');
            $this->__delete();
            return false;
        }

        if (!$report->isSuccess())
            \Log::warning('push_subscription #' . $this->id . ' : envoi web push echoue - ' . $report->getReason());

        return $report->isSuccess();
    }

    public function expoPushNotification($message, $payload = [], $icon = null)
    {
        $data = [
            "to" => $this->auth_token,
            "sound" => "default",
            "title" => PROJECT_NAME,
            "body" => $message,
            "data" => $payload,
            "priority" => "high",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Accept-encoding: gzip, deflate",
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        curl_close($ch);

        \Log::info("expo push notification id:" . $this->id . " - Message [ " . $message . " ] => " . $result . " - " . json_encode($payload));

        try {
            $json = json_decode($result);
            if (isset($json->data) && is_array($json->data) && isset($json->data[0]->status) && $json->data[0]->status === 'error') {
                $errorDetail = $json->data[0];
                if (isset($errorDetail->details) && $errorDetail->details->error === 'DeviceNotRegistered') {
                    Push_subscription::where("auth_token", $this->auth_token)->update(["status" => 0]);
                }
                \Log::warning("expo push UNSENT : " . $result);
                return false;
            } elseif (isset($json->errors) && is_array($json->errors)) {
                \Log::warning("expo push API_ERROR : " . $result);
                return false;
            }
        }
        catch (Exception $exception) {
            \Log::error($exception);
            return false;
        }

        return true;
    }

    public function fcmPushNotification($message, $payload = [], $icon = null)
    {
        // Auto-route to Expo if token matches format
        if (strpos($this->auth_token, 'ExponentPushToken') === 0 || strpos($this->auth_token, 'ExpoJsPushToken') === 0) {
            return $this->expoPushNotification($message, $payload, $icon);
        }

        if (!$icon)
            $icon = __env . 'logo.png';
        // Push Data's
        $data = array(
            // "to" => "$token",
            "message" => [
                "token" => $this->auth_token,
                "notification" => array(
                    "title" => PROJECT_NAME,
                    "body" => "$message",
                    //                    "icon" => $icon, // Replace https://example.com/icon.png with your PUSH ICON URL
                    //"click_action" => "$postlink"
                ),
                "data" => $payload,
                "android" => [
                    "priority" => "high",
                    "notification" => [
                        "click_action" => "ST_ACTIVATE"
                    ]
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10"
                    ],
                    "payload" => [
                        "aps" => [
                            "category" => "NEW_MESSAGE_CATEGORY",
                            "content_available" => true
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
            'Authorization: Bearer ' . self::$access_token,
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

        curl_close($ch);

        \Log::info("push notification id:" . $this->id . " - Message [ " . $message . " ] => " . $result . " - " . json_encode($payload));

        try {
            $json = json_decode($result);

            if (isset($json->error)) {
                if ($json->error->code == 404)
                    Push_subscription::where("auth_token", $this->auth_token)
                        ->update([
                        "status" => 0
                    ]);

                \Log::warning("push notification UNSENT : " . $result);
                return false;
            }
        }
        catch (Exception $exception) {
            \Log::error($exception);
            return false;
        }


        return true;
    // return $result;

    }

    public static function initPusher()
    {

        if (file_exists(ROOT . 'config/' . fcm_jwt_auth_file)) {

            // define the scopes for your API call
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            // Créer des credentials à partir du fichier de compte de service
            $credentials = new ServiceAccountCredentials($scopes, ROOT . 'config/' . fcm_jwt_auth_file);

            // Générer le token d'accès
            /// global $fcm_access_token;
            Push_subscription::$access_token = $credentials->fetchAuthToken()['access_token'];

            return Push_subscription::$access_token;
        }
        return null;
    }

}