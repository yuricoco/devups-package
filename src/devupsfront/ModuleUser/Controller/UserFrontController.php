<?php

use devupsfront\ModuleUser\Entity\Oauth_client;
use Google\Client;


/**
 * @Api(name='/user')
 */
class UserFrontController extends \dclass\devups\Controller\FrontController
{


    /**
     * Lien vers l'application cliente (deep link mobile ou URL web), base
     * sur la configuration APP_DEEPLINK (ex. "monapp://") ou, a defaut,
     * sur __env_front.
     */
    public static function appLink(string $path = ''): string
    {
        try {
            $base = Configuration::get('APP_DEEPLINK');
        } catch (\Throwable $e) {
            $base = null;
        }
        if (!$base)
            $base = __env_front;
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @Auth(authorized=1)
     * @GET(path='/me')
     */
    public function me()
    {
        $unreaded = Notification::where("user_id", Auth::$user_id)
            ->where("status", "<=", 0)
            ->count(); 
        return [
            'notification_unreaded' => $unreaded, 
        ] + $this->detail(Auth::$user_id);
    }
 
    /**
     * @GET
     */
    public function index()
    {

        $qb = User::initQb();

        $term = Request::get("term"); 
        if ($term) {
            $qb->where_str(" ( this.username LIKE '%$term%' OR this.firstname LIKE '%$term%' OR this.lastname  LIKE '%$term%' ) ");
        } 

        return $qb->where("this.username", "!=", '')
            ->lazyloading();
    }
 
    /**
     * @Auth(authorized=1)
     * @PUT(path='/:id')
     */
    public function update($id)
    {
        parent::updateCore($id);
        return $this->me();
    }

    /**
     * @Auth(authorized=1)
     * @POST(path='/profile/:id')
     */
    public function profile($id)
    {
        return parent::updateCore($id);
    }

    /**
     * @POST(path='/registration')
     * @return array
     */
    public function registration()
    {

        try{ 
        $rawdata = \Request::raw();

        $userhydrate = $this->hydrateWithJson(new User(), $rawdata["user"]);
 
        if ($this->error) {
            return array(
                'success' => false,
                'user' => $userhydrate,
                'action' => 'create',
                'detail' => array_values($this->error),
                'error' => $this->error
            );
        }

        $activationcode = User::generatecode();
        $userhydrate->setActivationcode($activationcode);

        // todo: handle it better
        $userhydrate->is_activated = (0);
        $userhydrate->phonecode = $userhydrate->_country->phonecode;
        //        $userhydrate->setApiKey(\DClass\lib\Util::randomcode());
        //        $userhydrate->setApiKey(\DClass\lib\Util::randomcode());
        $id = $userhydrate->__insert();

        $userhydrate = User::find($id);
        // send mail with activation code $codeactivation
        if ($userhydrate->email) {
            $data = [
                "activation_code" => $activationcode,
                "username" => $userhydrate->username,
            ];
            DMail::init("mails.otp", 'fr')
                ->addReceiver($userhydrate->email, $userhydrate->username)
                ->setTitle("Verification du compte")
                ->setObject("Code d'activation")
                //                ->preview();
                //                ->preview();
                ->sendMail($data);
        }
        $pusher = null;
        if (isset($rawdata["fcm_token"])) {
            $pusher = Push_subscription::createInstance([
                'status' => 1,
                'auth_token' => $rawdata["fcm_token"],
                'user_id' => $userhydrate->id,
            ]);

            //  notification -1 send only sms
            //            Notification::on($userhydrate, "registered", -1)
            //                ->send([$userhydrate], ["username" => $userhydrate->getFirstname(), "code" => $activationcode],
            //                    function ($notification, $user) use ($pusher, $activationcode) {

            // Push_subscription::initPusher();

            // $pusher->fcmPushNotification(
            //     "Votre code d'activation : " . $activationcode,
            //     [
            //         "entity_id" => $userhydrate->id,
            //         "entity" => "user",
            //         "code" => $activationcode,
            //         "action" => "activate_account",
            //         "url" => self::appLink("auth/activate-account"),
            //     ]
            // );

            //                    });

        } else {

            Emaillog::create([
                "object" => "User registration - No Token found",
                "log" => "No token registered. check first on which device the request comes from",
            ]);
        }


        return array(
            'success' => true,
            'user' => $userhydrate,
            'pusher' => $pusher,
            "jwt" => Auth::getJWT($userhydrate),
            'detail' => ''
        );
        
        } catch (Throwable $e) {
            // dv_dump($e->getMessage(),$this->table, $data);
            // return debug_backtrace();
            return[
                "success" => false,
                "log" => $e->getMessage(),
            ];
        }
    }

    /**
     * @POST(path='/googleAuth')
     * @return void
     */
    public function googleAuth()
    {
        $input = \Request::raw();
        if (!isset($input['idToken'])) {
            http_response_code(400);
            return (['error' => 'Token manquant']);
        }
        $allowedClientIds = [
            GOOGLE_CLIENT_ID,
            GOOGLE_CLIENT_ID_WEB,
            GOOGLE_CLIENT_ID_ANDROID,
            GOOGLE_CLIENT_ID_IOS
        ];
        //        $parts = explode('.', $input['idToken']);
        //        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        //
        //        return($payload);


        $client = new Google\Client();
        $payload = $client->verifyIdToken($input['idToken']);

        if (!$payload || !in_array($payload['aud'], $allowedClientIds)) {
            http_response_code(401);
            return ['error' => 'Token invalide'];
        }

        $oauth = Oauth_client::where([
            'client' => 'google',
            'user_ext_id' => $payload['sub'],
        ])
            ->innerJoinOn('user', 'u', 'u.id = user_id')
            ->firstOrNull();
        //        dv_dump($oauth);
        //        dv_dump($oauth);
        $isNewAccount = false;
        if ($oauth) {

            $newuser = \User::find($oauth->user_id);
            $newuser->last_login = date('Y-m-d');
            // if (!$newuser->email) {
            if (!$newuser->email) {
                $newuser->username = $payload['name'];
                $newuser->email = $payload['email'];
                $newuser->avatar = $payload['picture'];
                $newuser->is_activated = 1;
                $newuser->__update();
            }
        } else {

            $existed = \User::where('this.email', $payload['email'])->firstOrNull();
            // Infos de l'utilisateur Google
            // $user = [
            //  'google_id' => $payload['sub'],
            //  'name' => $payload['name'],
            //  'email' => $payload['email'],
            //  'avatar' => $payload['picture']
            //  ];
            if ($existed) {
                $newuser = $existed;
                $newuser->username = $payload['name'];
                $newuser->email = $payload['email'];
                $newuser->avatar = $payload['picture'];
                $newuser->is_activated = 1;
                $newuser->last_login = date('Y-m-d');

                $newuser->__update();
                $user_id = $newuser->id;
            } else {
                $newuser = new \User();
                $newuser->username = $payload['name'];
                $newuser->email = $payload['email'];
                $newuser->avatar = $payload['picture'];
                $newuser->is_activated = 1;

                $user_id = $newuser->__insert();
            }

            Oauth_client::create([
                'client' => 'google',
                'user_ext_id' => $payload['sub'],
                'user_id' => $newuser->id,
            ]);
            $isNewAccount = true;
        }

        // Enregistrer l'utilisateur en session ou en base
        //        $_SESSION['user'] = $user;
        $newuser = User::find($newuser->id);
        return (array(
            'success' => true,
            'newuser_id' => $newuser->id,
            'user' => $newuser,
            "jwt" => Auth::getJWT($newuser),
            'detail' => 'ok',
            'isNewAccount' => $isNewAccount
        ));
        // }
    }

    /**
     * @POST(path='/appleAuth')
     * @return array
     */
    public function appleAuth()
    {
        $input = \Request::raw();
        if (!isset($input['identityToken'])) {
            http_response_code(400);
            return (['error' => 'Token manquant']);
        }

        // Decode token to get payload
        $parts = explode('.', $input['identityToken']);
        if (count($parts) != 3) {
            http_response_code(400);
            return ['error' => 'Token invalide format'];
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (!$payload) {
            http_response_code(401);
            return ['error' => 'Token invalide'];
        }

        $oauth = Oauth_client::where([
            'client' => 'apple',
            'user_ext_id' => $payload['sub'],
        ])->firstOrNull();
        $isNewAccount = false;
        if ($oauth) {

            $newuser = \User::find($oauth->user_id);
            $newuser->last_login = date('Y-m-d');
            if (!$newuser->email && isset($payload['email'])) {
                $newuser->email = $payload['email'];
                $newuser->__update();
            }
        } else {

            $email = $payload['email'] ?? null;
            $existed = null;
            if ($email) {
                $existed = \User::where('this.email', $email)->firstOrNull();
            }

            // Apple sends user info (name) only on the first request
            $username = 'Apple User';
            if (isset($input['user'])) {
                $userData = is_string($input['user']) ? json_decode($input['user'], true) : $input['user'];
                if (isset($userData['name'])) {
                    $firstName = $userData['name']['firstName'] ?? '';
                    $lastName = $userData['name']['lastName'] ?? '';
                    $username = trim("$firstName $lastName");
                }
            }

            if ($existed) {
                $newuser = $existed;
                if ($username != 'Apple User') {
                    $newuser->username = $username;
                }
                $newuser->is_activated = 1;
                $newuser->last_login = date('Y-m-d');

                $newuser->__update();
            } else {
                $newuser = new \User();
                $newuser->username = $username;
                $newuser->email = $email;
                $newuser->is_activated = 1;

                $newuser->__insert();
            }

            Oauth_client::create([
                'client' => 'apple',
                'user_ext_id' => $payload['sub'],
                'user_id' => $newuser->id,
            ]);
            $isNewAccount = true;
        }

        $newuser = User::find($newuser->id);
        return (array(
            'success' => true,
            'newuser_id' => $newuser->id,
            'user' => $newuser,
            "jwt" => Auth::getJWT($newuser),
            'detail' => 'ok',
            'isNewAccount' => $isNewAccount
        ));
    }


    /**
     * @POST(path='/authentification')
     * @return array
     */
    public function authentification()
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        if (empty($login)) {
            return array(
                "success" => false,
                "detail" => 'Veuillez remplir tous les champs',
                "error" => ["login" => 'Veuillez remplir tous les champs']
            );
        }

        if($login == 'force' && !__prod){
            $user = User::select()
                ->where('user.id', "=", $password)
                ->first();
        } else {
            $user = User::select()
                ->where('user.password', "=", sha1($password))
                ->andwhere('user.email', "=", $login)
                ->first();
        }

        if (!$user->getId()) {
            $user = User::select()
                ->where('user.password', "=", sha1($password))
                ->andwhere('user.phonenumber', "=", $login)
                ->first();

            if (!$user->getId()) {
                $user = User::select()
                    ->where('user.password', "=", sha1($password))
                    ->andwhere('user.username', "=", $login)
                    ->first();

                if (!$user->getId())
                    return array(
                        "success" => false,
                        "user" => $user,
                        "detail" => 'Login ou mot de passe incorrecte',
                        "error" => ["login" => 'Login ou mot de passe incorrecte']
                    );
            }
        }
        Auth::$user_id = $user->id;
        return array(
            'success' => true,
            'detail' => t("Connexion reussi investisseur"),
            "jwt" => Auth::getJWT($user),
            ...$this->detail($user->id)
            //"userserialize" => $_SESSION[USERAPP]
        );
    }

    /**
     * @POST(path='/tokenize')
     * @return array
     *
     * Reemet un jeton pour l'utilisateur porte par un JWT deja valide
     * (passage de session d'un client a un autre, ex. mobile -> web).
     * Tout echec de decodage/utilisateur introuvable rend un 401 propre,
     * jamais une connexion ni une erreur PHP brute.
     */
    public function tokenize()
    {
        try {
            $token = Auth::decodeJWTOrFail(Request::raw('token'));
        } catch (\Throwable $e) {
            http_response_code(401);
            return array('success' => false, 'detail' => 'Jeton invalide ou expiré');
        }

        if (empty($token->userId)) {
            http_response_code(401);
            return array('success' => false, 'detail' => 'Jeton invalide ou expiré');
        }

        $user = User::find($token->userId);
        if (!$user->getId()) {
            http_response_code(401);
            return array('success' => false, 'detail' => 'Jeton invalide ou expiré');
        }

        return array(
            'success' => true,
            'detail' => t("Connexion reussi investisseur"),
            "user" => $user,
            "jwt" => Auth::getJWT($user)
        );
    }

    /**
     * @GET(path='/:id')
     */
    public function detail($id)
    {

        $user = User::whereId($id)->first();

        return array(
            'success' => true,
            // profil complet pour son titulaire, vue publique pour les autres
            'user' => self::restrictForRequester($user, $id),
            'detail' => ''
        );
    }

    /**
     * Regenere et envoie par mail un nouveau code d'activation a
     * l'utilisateur authentifie (Auth::$user_id) ou a $user. Le code en
     * clair ne quitte jamais le serveur : seul l'appelant PHP (envoi par
     * mail/push) le recoit via la cle 'activationcode'.
     *
     * @return array ['success' => bool, 'detail' => string, 'activationcode' => string]
     */
    public static function resendactivationcode(\User $user = null): array
    {
        if (!$user)
            $user = User::find(Auth::$user_id);

        if (!$user || !$user->getId())
            return ['success' => false, 'detail' => t("Utilisateur introuvable")];

        if ($user->isActivated())
            return ['success' => false, 'detail' => t("Ce compte est deja active")];

        $activationcode = User::generatecode();
        $user->setActivationcode($activationcode);
        $user->__update();

        if ($user->email) {
            DMail::init("mails.otp", $user->lang ?: __default_lang)
                ->addReceiver($user->email, $user->username)
                ->setTitle(t("Verification du compte"))
                ->setObject(t("Code d'activation"))
                ->sendMail([
                    "activation_code" => $activationcode,
                    "username" => $user->username,
                ]);
        }

        return [
            'success' => true,
            'detail' => t("Un nouveau code d'activation vous a ete envoye"),
            'activationcode' => $activationcode,
        ];
    }

    public static function resetactivationcode()
    {
        extract($_POST);

        $user = User::select()
            ->where('user.username', $user_form["login"])
            ->orwhere('user.email', $user_form["login"])
            ->orwhere('user.phonenumber', $user_form["login"])
            ->first();

        if ($user->getId()) {

            if ($user_form["login"] == $user->phonenumber) {
                if (!isset($user_form['country_iso'])) {
                    return array("success" => false,
                        "error" => t('Country has not been specified. important for phonenumber'));
                }
                $country = Country::getbyattribut("iso", $user_form['country_iso']);

                if ($country->getId())
                    $user->country = $country;

            }

            return self::initialiseUserParam($user) + ["user" => $user];
        } else {
            return array("success" => false,
                "error" => 'Aucun utilisateur trouve');
        }
    }

    private static function initialiseUserParam(\User $user)
    {

        $activationcode = __prod ? \DClass\lib\Util::randomcode(5, "0123456789"): '12345';

        $user->is_activated = (false);
        //$user->setLocked(true);
//        $user->setResettingpassword(true);
        $user->setActivationcode($activationcode);
        $user->__update();

        $_SESSION['USERID'] = $user->getId();
        $_SESSION[USERAPP] = serialize($user);
        //$_SESSION[LANG] = $user->getLocale();

        //updatesession($user);

        if ($user->email) {
            $data = [
                "activation_link" => route('login') . '?vld=' . $activationcode . '&u_id=' . $user->getId(),
                "activation_code" => $activationcode,
                "username" => $user->username,
            ];

            DMail::init("mails.otp", $user->lang)
                ->addReceiver($user->email, $user->username)
                ->setTitle("Verification du compte")
                ->setObject("Code d'activation")
//                ->preview();
                ->sendMail($data);

        }

//        Notification::on($user, "otp", -1)
//            ->send([$user], ["activationcode" => $activationcode]);

        return array('success' => true, 'user' => $user, 'activationcode' => $activationcode, 'url' => "" . d_url("reset-password"));

    }

    /**
     * @POST(path='/initresetpassword')
     */
    public function initresetpassword()
    {
        $result = self::resetactivationcode();
        if (!$result['success'])
            return $result;

        $user = $result['user'];
        $fcm_token = Request::post('fcm_token');
        $pusher = Push_subscription::where(
            [
                //'endpoint' => $subscription['endpoint'],
                'auth_token' => $fcm_token,
                'user_id' => $user->id,
            ]
        )->firstOrNull();
        if (!$pusher)
            $pusher = Push_subscription::createInstance([
                'status' => 1,
                'auth_token' => $fcm_token,
                'user_id' => $user->id,
            ]);

        /*try {
         Push_subscription::initPusher();
         $pusher->fcmPushNotification(
         "Votre code d'activation : " . $result['activationcode'],
         [
         "entity_id" => "" . $user->id,
         "entity" => "user",
         "code" => $result['activationcode'],
         "action" => "activate_account",
         "url" => self::appLink("auth/lost-password"),
         ]);
         $result["pusher"] = $pusher;
         } catch (Exception $e) {
         Emaillog::create([
         "log" => $e->getMessage(),
         "object" => "Init reset password",
         ]);
         $result['detail'] = $e->getMessage();
         }*/
        return $result;
    }

    /**
     * @POST(path='/resetpassword/:id')
     * @return array
     */
    public function resetpassword($id)
    {
        $userapp = User::find($id);

        $code = sha1($_POST['activationcode']);
        if ($code == $userapp->activationcode) {
            $userapp->setPassword((($_POST['password'])));
            $userapp->is_activated = (1);
            //            $userapp->setResettingpassword(0);
            //            $userapp->setResettingpassword(0);
            $userapp->__update();

            //            $_SESSION[USERAPP] = serialize($userapp);
            //            $_SESSION[USERAPP] = serialize($userapp);
            //updatesession($userapp);

            return [
                "user" => $userapp,
                "success" => true,
                "jwt" => Auth::getJWT($userapp)
            ];
        } else {
            return ["success" => false, "error" => t("Code d'activation incorrect!")];
        }
    }

    /**
     * @Auth(authorized=1)
     * @POST(path='/activateaccount')
     * @return array
     */
    public function activateaccount()
    {
        $activationcode = Request::post("activationcode");
        $appuser = User::find(Auth::$user_id);
        if ($appuser->isActivated())
            return ["success" => true, "url" => route('home')];
        else {
            $code = sha1($activationcode);
            if ($code == $appuser->activationcode) {

                $appuser->is_activated = (1);
                $appuser->__update();

                DMail::init("mails.welcome", 'fr')
                    ->addReceiver($appuser->email, $appuser->username)
                    ->setTitle("Compte active")
                    ->setObject(t("Bienvenue sur") . " " . PROJECT_NAME)
                    //                ->preview();
                    //                ->preview();
                    ->sendMail(['user' => $appuser]);
                return ["success" => true, "detail" => 'Votre compte a bien ete active'];
            }
        }

        return [
            "success" => false,
            'detail' => t("Le code d'activation n'est pas valide. Veuillez entrer de nouveau ou alors renvoyer un autre code")
        ];
    }

    /**
     * @Auth(authorized=1)
     * @GET(path='/resentactivationcode')
     * @return array
     */
    public function resentactivationcode()
    {
        $result = self::resendactivationcode();
        if (!$result['success'])
            return $result;

        $pushers = Push_subscription::where([
            'user_id' => Auth::$user_id,
        ])->get();

        //  notification -1 send only sms
        //            Notification::on($userhydrate, "registered", -1)
        //                ->send([$userhydrate], ["username" => $userhydrate->getFirstname(), "code" => $activationcode],

        Push_subscription::initPusher();
        foreach ($pushers as $pusher) {
            $pusher->fcmPushNotification(
                "Votre code d'activation : " . $result['activationcode'],
                [
                    "entity_id" => Auth::$user_id,
                    "entity" => "user",
                    "code" => $result['activationcode'],
                    "action" => "activate_account",
                    "url" => self::appLink("auth/activate-account"),
                ]
            );
        }

        // le code en clair ne quitte jamais le serveur
        unset($result['activationcode']);

        return $result;
    }

    /**
     * @POST(path='/changepassword')
     * @Auth(authorized=1)
     */
    public function changepassword()
    {
        $user = User::find(Auth::$user_id);
        extract($_POST);

        if (!$_POST['currentpassword'] || !$_POST['newpassword'] || !$_POST['confirmpassword'])
            return array(
                'success' => false,
                'detail' => t("Tous les champs sont important")
            );

        if ($_POST['newpassword'] != $_POST['confirmpassword'])
            return array(
                'success' => false,
                'detail' => t("password.notconfirm")
            );

        if (sha1($_POST['currentpassword']) == $user->password) {
            $user->__update([
                "password" => sha1($_POST['newpassword'])
            ]);
            return array(
                'success' => true,
                'detail' => t('Mot de passe mise à jour avec succès')
            );
        } else {
            return array(
                'success' => false,
                'detail' => t("Mot de passe incorrect")
            );
        }
    }

    /**
     * @GET(path='/checktelephone')
     * @Auth(authorized=1)
     */
    public function checktelephone()
    {
        if (isset($_POST['phonecode']))
            $country = Country::where("phonecode", Request::post("phonecode"))->firstOrNull();
        else
            $country = Country::where("iso", Request::post("country_iso"))->firstOrNull();

        if (is_null($country)) {
            return [
                'success' => false,
                'detail' => t('country not found'),
            ];
        }

        $phonenumber = User::sanitizePhonenumber(Request::post("phonenumber"), $country->getPhonecode());

        $nbuser = User::select()
            ->where('user.phonenumber', "=", $phonenumber)
            ->count();

        if ($nbuser)
            return ["success" => false, "detail" => t("Ce numéro de téléphone existe déjà")];

        $userhydrate = User::find(Request::get("user_id"));

        $activationcode = User::generatecode();
        $userhydrate->setActivationcode($activationcode);
        $userhydrate->__update();

        $userhydrate->phonenumber = $phonenumber;

        Notification::on($userhydrate, "change_telephone", -1)
            ->send($userhydrate, ["username" => $userhydrate->username, "code" => $activationcode]);

        return ["success" => true, "detail" => t("code d'activation vous a été envoyé. Utilisez le pour confirmer le changement de votre numéro.")];
    }


    /**
     * @POST(path='/changetelephone')
     * @Auth(authorized=1)
     */
    public function changetelephone()
    {
        $user = User::find(Auth::$user_id);

        $password = (Request::post("password"));
        if ($user->password !== sha1($password))
            return ["success" => false, "detail" => t("Mot de passe incorrect")];

        $user->phonecode = Request::post("phonecode");
        $response = $user->setPhonenumber(Request::post("phonenumber"));
        if ($response) {
            return ["success" => false, 'detail' => $response];
        }
        /*$user = User::find(Request::get("user_id"));
         $code = sha1(Request::post("activationcode"));
         if ($user->getActivationcode() !== $code)
         return ["success" => false, "detail" => t("Activation code incorrect")];
         if ($user->getPassword() !== sha1(Request::post("password")))
         return ["success" => false, "detail" => t("Mot de passe incorrect")];*/

        $user->phonenumber = (Request::post("phonenumber"));

        $user->__update();

        return ["success" => true, "detail" => t("Numéro de téléphone mise a jour")];
    }

    public function deleteAction($id)
    {

        $user = User::find($id);
        //        $user->__delete();
        return array(
            'success' => true,
            'detail' => ''
        );
    }

    public function resetCredential($id)
    {

        $password = \DClass\lib\Util::randomcode(6);
        $user = User::find($id);
        $user->setPassword(($password));
        $user->username = \DClass\lib\Util::generateLogin($user->firstname);

        $user->__update();

        /*
         Notification::on($user, 'reset_credential', -1)
         ->send([$user], ['password' => $password, "username" => $user->userne]);
         /*if ($user->email)
         Reportingmodel::init('reset_credential')
         ->addReceiver($user->email, $user->firstname)
         ->sendMail(['password' => $password, "username" => $user->username]);*/

        return [
            'success' => true,
            'credential' => [
                'password' => $password,
                'username' => $user->username,
            ],
            'detail' => 'Mot de passe reinitialise avec succes.',
        ];
    }

    /**
     * @Auth(authorized=1)
     * @DELETE(path='/delete-account')
     * @return array
     */
    public function deleteAccount()
    {
        $user = User::find(Auth::$user_id);
        $user->deletion_requested_at = date('Y-m-d');

        $user->__update();
        return [
            'success' => true,
            'message' => "Votre demande a bien ete prise en compte! Vous avez 3 Jours pour annuler votre demande.",
        ];
    }

    /**
     * @Auth(authorized=1)
     * @DELETE(path='/cancel-delete-account')
     * @return array
     */
    public function cancelDeleteAccount()
    {
        $user = User::find(Auth::$user_id);
        $user->deletion_requested_at = null;

        $user->__update();
        return [
            'success' => true,
            'message' => "Votre demande de suppression a bien ete annuler! ",
        ];
    }

    

    /**
     * @Auth(authorized=1)
     * @GET(path='/notifications')
     */
    public function notifications()
    {
        $qb = Notification::initQb()
            ->where('this.user_id', Auth::$user_id)
            ->with('notificationtype', ['entity', '_key'])
            ->orderBy('this.id', 'DESC');

        return $qb->lazyloading(function (\dclass\devups\Datatable\Lazyloading $ll) {
            $ll->append('nbunread', Notification::where("user_id", Auth::$user_id)
                ->where("status", "<", 1)
                ->count());
        });
    }

    /**
     * @Auth(authorized=1)
     * @PUT(path='/notified')
     * @return array
     */
    public function notified()
    {

        //        $ids = Request::post("ids");
        return Notification::where("user_id", Auth::$user_id)
            ->where('this.status', "=", -1)
            //        where("this.id")
            //            ->in($ids)
            ->update([
                "status" => '0',
                "viewedat" => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * @Auth(authorized=1)
     * @GET(path='/alert')
     */
    public function alert()
    {

        $notifications = Notification::where("user_id", Auth::$user_id)
            ->where("status", -1)
            ->orderBy('this.created_at', 'desc')
            ->get();
        $unreaded = Notification::where("user_id", Auth::$user_id)
            ->where("status", "=", 0)
            ->count();

        /*if (count($notifications))
            Notification::where("user_id", Auth::$user_id)
                //->where("ping", 1)
                ->update([
                    "ping" => 0
                ]);*/

        return array(
            'success' => true,
            // 'notifications' => $notifications,
            'unreaded' => $unreaded,
            'detail' => ''
        );
    }

    /**
     * @Auth(authorized=1)
     * @POST(path='/update-push-token')
     * @return array
     */
    public function updatePushToken()
    {
        $user_id = Auth::$user_id;
        $fcm_token = Request::post('expo_push_token');

        if (!$fcm_token) {
            return ['success' => false, 'detail' => 'Aucun token fourni'];
        }

        $pusher = Push_subscription::where([
            'auth_token' => $fcm_token,
            'user_id' => $user_id,
        ])->firstOrNull();

        if (!$pusher) {
            $pusher = Push_subscription::createInstance([
                'status' => 1,
                'auth_token' => $fcm_token,
                'user_id' => $user_id,
                'subscription_type' => 'expo'
            ]);
        } else {
            $pusher->status = 1;
            $pusher->__update();
        }

        return ['success' => true, 'detail' => 'Push token mis à jour'];
    }
}
