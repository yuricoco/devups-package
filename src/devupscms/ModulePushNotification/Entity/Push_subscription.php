<?php
// user \dclass\devups\model\Model;
use Minishlink\WebPush\SubscriptionInterface;

/**
 * @Entity @Table(name="push_subscription")
 * */
class Push_subscription extends Model implements JsonSerializable, SubscriptionInterface
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
     * @Column(name="subscription_id", type="integer"  )
     * @var integer
     **/
    protected $subscription_id;
    /**
     * @Column(name="endpoint", type="text" , length=255 )
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

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'subscription_type' => $this->subscription_type,
            'subscription_id' => $this->subscription_id,
            'endpoint' => $this->endpoint,
            'public_key' => $this->public_key,
            'auth_token' => $this->auth_token,
            'content_type' => $this->content_type,
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
                    //"icon" => $icon, // Replace https://example.com/icon.png with your PUSH ICON URL
                    //"click_action" => "$postlink"
                ),
                "data" => $payload,
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

        Emaillog::create([
            "object" => "push notification to #" . $this->subscription_id,
            "log" => "Message [ " . $message . " ] => " . $result,
        ]);
        // dv_dump($result, $data);
        $json = json_decode($result);
//        if (isset($json->error))
//            return null;

        if (isset($json->error)) {
            if ($json->error->code == 404)
                Push_subscription::where("auth_token", $this->auth_token)->delete();
        }

        curl_close($ch);

        // return $result;

    }

}
