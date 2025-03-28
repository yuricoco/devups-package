<?php


class UserCore extends \Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="firstname", type="string" , length=55 , nullable=true )
     * @var string
     **/
    protected $firstname;
    /**
     * @Column(name="lastname", type="string" , length=55 , nullable=true)
     * @var string
     **/
    protected $lastname;
    /**
     * @Column(name="username", type="string" , length=55 , nullable=true)
     * @var string
     **/
    public $username;

    /**
     * @Column(name="email", type="string" , length=55 , nullable=true)
     * @var string
     **/
    public $email;

    /**
     * @Column(name="phonenumber", type="string" , length=25 , nullable=true)
     * @var string
     **/
    public $phonenumber;
    /**
     * @Column(name="password", type="string" , length=255 , nullable=true)
     * @var string
     **/
    public $password;
    /**
     * @Column(name="resettingpassword", type="integer"  , nullable=true)
     * @var integer
     **/
    public $resettingpassword;
    /**
     * @Column(name="is_activated", type="integer"  , nullable=true)
     * @var integer
     **/
    public $is_activated = 1;

    /**
     * @Column(name="activationcode", type="string" , length=255 , nullable=true)
     * @var string
     **/
    public $activationcode;
    /**
     * @Column(name="activationcode_expired_at", type="date", nullable=true)
     * @var string
     **/
    protected $activationcode_expired_at;

    /**
     * @Column(name="last_login", type="date"  , nullable=true)
     * @var date
     **/
    protected $last_login;
    /**
     * @Column(name="lang", type="string" , length=2 , nullable=true)
     * @var string
     **/
    protected $lang = "en";
    /**
     * @Column(name="api_key", type="string" , length=255 , nullable=true )
     * @var string
     **/
    protected $api_key;
    /**
     * @Column(name="session_token", type="string" , length=255 , nullable=true)
     * @var string
     **/
    protected $session_token;

    public function setTelephoneOrEmail($value)
    {
        //return $this->setEmail($value);
        // code de validation
        if (is_numeric($value)) {
            return $this->setPhonenumber($value);
        }
        return $this->setEmail($value);

    }

    public function setEmail($email)
    {
        if (!$email)
            return null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return t("Fomat de l'adresse email invalide");
        }

        $nb = User::where("email", $email);
        if ($nb->count()) {
            if ($nb->first()->getId() != $this->id)
                return t("a user with this :attribute already exist", ["attribute" => "email"]);
        }
        $this->email = $email;
    }

    public function setPhonenumber($phonenumber)
    {
        if (!$phonenumber)
            return null;

        $phonenumber = \DClass\lib\Util::sanitizePhonenumber($phonenumber, $this->country->phonecode);

        $nb = User::where("phonenumber", $phonenumber);
        if ($nb->count()) {
            if ($nb->first()->getId() != $this->id)
                return t("a user with this :attribute already exist", ["attribute" => "phonenumber"]);
        }
        $this->phonenumber = $phonenumber;
    }

    public function setPassword($password)
    {
        if(!$password)
            return null;

        $this->password = sha1($password);
    }
    public function setResetpassword ($password)
    {
        if(!$password)
            return null;

        $this->password = sha1($password);
    }

    public function setTelephone($telephone)
    {
        $this->setPhonenumber($telephone);
        /*if (!$telephone)
            return null;

        $telephone = self::sanitizePhonenumber($telephone, $this->country->getPhonecode());

        $nb = User::where("telephone", $telephone);
        if ($nb->count()) {
            if ($nb->first()->getId() != $this->id)
                return t("a user with this :attribute already exist", ["attribute" => "telephone"]);
        }
        $this->telephone = $telephone;*/
    }

    /**
     * @return \User
     */
    public static function userapp()
    {

        if (isset($_SESSION[USER]))
            return unserialize($_SESSION[USER]);

        return new \User();
    }

    public function isActivated()
    {
        return boolval($this->is_activated);
    }

    public static function generateActivationCode()
    {
        return password_hash(\DClass\lib\Util::randomcode(), PASSWORD_DEFAULT);
    }

    public static function availableToken($token, $route)
    {

        global $user;
        if(__prod)
            $user = User::select("*")->where_str(" '$token' = CONCAT(api_key, '.', session_token) ")
                ->firstOrNull();
        else
            $user = User::find($token);

        if ($user) {
            return $route($user);
        }
        return [
            "success" => false,
            "detail" => t("session token unavailable"),
        ];

    }

    /**
     * @param $token
     * @return User|null
     */
    public static function getUserByToken($token){

        return User::select("*")->where_str(" '$token' = CONCAT(api_key, '.', session_token) ")
            ->firstOrNull();

    }

    /**
     * @return string
     */
    public function getTelephone(){
        return $this->country->phonecode.$this->phonenumber;
    }

    public function mapAvailableData($userbaseinfo)
    {

        $synchronized = [];
        foreach ($userbaseinfo as $key => $value) {
            if (in_array($key, self::$paramsynch))
                $synchronized[$key] = $value;
        }
        return $synchronized;
    }

    public static $paramsynch;

    public function jsonSerialize()
    {

//        if (Request::get("synchro", null)) {
            return $this->mapAvailableData($this);
//        }// TODO: Change the autogenerated stub
    }



}