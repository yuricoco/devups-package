<?php
// user \dclass\devups\model\Model;
//use Minishlink\WebPush\SubscriptionInterface;
use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * @Entity @Table(name="push_subscription")
 * */
class Push_subscription extends Model implements JsonSerializable //, SubscriptionInterface
{

    public static $access_token;
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

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'subscription_type' => $this->subscription_type,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'auth_token' => $this->auth_token,
        ];
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
        return 'aesgcm';
    }

    public function fcmPushNotification($message, $payload = [], $icon = null)
    {

        if (!$icon)
            $icon = __env . '/logo.png';
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

        Emaillog::create([
            "object" => "push notification id:" . $this->id,
            "log" => "Message [ " . $message . " ] => " . $result." - ".json_encode($payload),
        ]);

        try {
            $json = json_decode($result);

            if (isset($json->error)) {
                if ($json->error->code == 404)
                    Push_subscription::where("auth_token", $this->auth_token)
                        ->update([
                            "status"=>0
                        ]);

                Emaillog::create([
                    "object" => "push notification UNSENT",
                    "log" => $result,
                ]);
                return false;
            }
        }catch (Exception $exception){
            Emaillog::create([
                "object" => "push notification Exception id:" . $this->id,
                "log" => "Message => " . $exception->getMessage(),
            ]);
            return false;
        }


        return true;
        // return $result;

    }

    public static function initPusher(){

        if (file_exists(ROOT .'config/'. fcm_jwt_auth_file)) {

// define the scopes for your API call
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

// Créer des credentials à partir du fichier de compte de service
            $credentials = new ServiceAccountCredentials($scopes, ROOT .'config/'. fcm_jwt_auth_file);

// Générer le token d'accès
            /// global $fcm_access_token;
            Push_subscription::$access_token = $credentials->fetchAuthToken()['access_token'];

            return Push_subscription::$access_token;
        }
        return  null;
    }

}
