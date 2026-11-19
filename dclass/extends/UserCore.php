<?php

namespace dclass\devups\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Colonnes heritees par les entites qui etendent cette classe.
 */
#[ORM\MappedSuperclass]
class UserCore extends \Model implements \JsonSerializable
{
    // NB (v8.0, LOT 1.2) : voir Dvups_config_item.php - meme correctif
    // (redeclaration explicite requise, pas un simple "use" du trait).
    /** @var string */
    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected $created_at;
    /** @var string */
    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    protected $updated_at;
    /** @var string */
    #[ORM\Column(name: "deleted_at", type: "datetime", nullable: true)]
    protected $deleted_at;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var string
     */
    #[ORM\Column(name: "firstname", type: "string", length: 55, nullable: true)]
    protected $firstname;
    /**
     * @var string
     */
    #[ORM\Column(name: "lastname", type: "string", length: 55, nullable: true)]
    protected $lastname;
    /**
     * @var string
     */
    #[ORM\Column(name: "username", type: "string", length: 55, nullable: true)]
    public $username;

    /**
     * @var string
     */
    #[ORM\Column(name: "email", type: "string", length: 55, nullable: true)]
    public $email;

    /**
     * @var string
     */
    #[ORM\Column(name: "phonenumber", type: "string", length: 25, nullable: true)]
    public $phonenumber;
    /**
     * @var string
     */
    #[ORM\Column(name: "password", type: "string", length: 255, nullable: true)]
    public $password;
    /**
     * @var integer
     */
    #[ORM\Column(name: "resettingpassword", type: "integer", nullable: true)]
    public $resettingpassword;
    /**
     * @var integer
     */
    #[ORM\Column(name: "is_activated", type: "integer", nullable: true)]
    public $is_activated = 1;

    /**
     * @var string
     */
    #[ORM\Column(name: "activationcode", type: "string", length: 255, nullable: true)]
    public $activationcode;
    /**
     * ETAPE L.1 : type="datetime", pas "date" - l'expiration (defaut 15
     * min, ACTIVATION_CODE_TTL_MINUTES) a besoin d'une precision a la
     * minute, qu'une colonne DATE (sans heure) ne peut pas porter.
     * @var string
     */
    #[ORM\Column(name: "activationcode_expired_at", type: "datetime", nullable: true)]
    protected $activationcode_expired_at;

    public function getActivationcodeExpiredAt()
    {
        return $this->activationcode_expired_at;
    }

    /**
     * ETAPE P.1 : jeton dedie a la reinitialisation de mot de passe -
     * distinct du code court d'activation (activationcode, garde pour
     * l'activation de compte et le SMS). Seul le hash (sha256) du jeton
     * est stocke, jamais le jeton en clair.
     * @var string
     */
    #[ORM\Column(name: "reset_token_hash", type: "string", length: 255, nullable: true)]
    protected $reset_token_hash;

    /**
     * @var string
     */
    #[ORM\Column(name: "reset_token_expired_at", type: "datetime", nullable: true)]
    protected $reset_token_expired_at;

    /**
     * ETAPE P.1 : genere un jeton aleatoire cryptographiquement sur
     * (random_bytes(32), encode en base64url), stocke seulement son hash
     * sha256 avec une expiration courte (defaut 30 min,
     * RESET_PASSWORD_TOKEN_TTL_MINUTES) et usage unique (voir
     * clearResetPasswordToken()). Un nouvel appel ecrase le hash
     * precedent, invalidant implicitement tout jeton emis plus tot pour ce
     * compte. Retourne le jeton EN CLAIR - a transmettre une seule fois
     * (lien email), jamais journalise ni renvoye par l'API.
     */
    public function setResetPasswordToken(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->reset_token_hash = hash('sha256', $token);
        $this->reset_token_expired_at = date("Y-m-d H:i:s", strtotime("+" . RESET_PASSWORD_TOKEN_TTL_MINUTES . " minutes"));
        return $token;
    }

    /**
     * Verifie le jeton EN CLAIR recu (lien email) contre le hash stocke,
     * sans le consommer : true si le hash correspond et que le jeton n'a
     * pas expire.
     */
    public function verifyResetPasswordToken(string $token): bool
    {
        if (!$this->reset_token_hash || $token === '')
            return false;
        if (!hash_equals($this->reset_token_hash, hash('sha256', $token)))
            return false;
        if (!$this->reset_token_expired_at || strtotime($this->reset_token_expired_at) < time())
            return false;
        return true;
    }

    /**
     * Invalide le jeton de reinitialisation en attente (usage unique apres
     * un reset reussi, ou mot de passe change par un autre moyen -
     * changepassword(), resetCredential() - pendant qu'un jeton etait
     * actif).
     */
    public function clearResetPasswordToken(): void
    {
        $this->reset_token_hash = null;
        $this->reset_token_expired_at = null;
    }

    /**
     * @var date
     */
    #[ORM\Column(name: "last_login", type: "datetime", nullable: true)]
    protected $last_login;
    /**
     * @var string
     */
    #[ORM\Column(name: "lang", type: "string", length: 2, nullable: true)]
    protected $lang = "en";
    /**
     * @var string
     */
    #[ORM\Column(name: "api_key", type: "string", length: 255, nullable: true)]
    protected $api_key;
    /**
     * @var string
     */
    #[ORM\Column(name: "session_token", type: "string", length: 255, nullable: true)]
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

        $exist = \User::where("email", $email)->firstOrNull();
        if ($exist) {
            if ($exist->getId() != $this->id)
                return t("Cette adresse mail existe déjà",);
        }

        $this->email = $email;
    }

    public function setPhonenumber($phonenumber)
    {
        if (!$phonenumber)
            return null;

        $phonenumber = \DClass\lib\Util::sanitizePhonenumber($phonenumber, $this->country->phonecode);

        $exist = \User::where("phonenumber", $phonenumber)->firstOrNull();
        if ($exist) {
            if ($exist->getId() != $this->id)
                return t("Ce numéro de téléphone existe déjà");
        }
        $this->phonenumber = $phonenumber;
    }

    public function setPassword($password)
    {
        if(!$password)
            return null;

        $this->password = dv_hash_password($password);
    }
    public function setResetpassword ($password)
    {
        if(!$password)
            return null;

        $this->password = dv_hash_password($password);
    }

    /**
     * ETAPE K.1 : verifie un mot de passe EN CLAIR contre le hash stocke.
     * Accepte encore l'ancien format sha1() et, dans ce cas uniquement,
     * remplace transparentement le hash stocke par password_hash() (puis
     * sauvegarde) - aucune reinitialisation forcee pour l'utilisateur.
     */
    public function verifyPassword(string $password): bool
    {
        $upgraded = false;
        $ok = dv_verify_password($password, $this->password, $upgraded);
        if ($ok && $upgraded) {
            $this->password = dv_hash_password($password);
            $this->__update();
        }
        return $ok;
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


    public function setUpdatePassword($pwd)
    {
        if ($pwd)
            $this->password = dv_hash_password($pwd);
    }

    public function setCountry_iso($iso_code)
    { 
        $this->country = \Country::where("iso", $iso_code)->first();
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
            $user = \User::select("*")->where_str(" '$token' = CONCAT(api_key, '.', session_token) ")
                ->firstOrNull();
        else
            $user = \User::find($token);

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

        return \User::select("*")->where_str(" '$token' = CONCAT(api_key, '.', session_token) ")
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




}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(UserCore::class, 'UserCore');
