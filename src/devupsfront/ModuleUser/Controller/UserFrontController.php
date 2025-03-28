<?php


/**
 * @Api(name='/user')
 */
class UserFrontController extends \dclass\devups\Controller\FrontController
{

    /**
     * @GET
     */
    public function index()
    {

        $qb = User::addColumns(' (select count(*) from follow where user_id = this.id) as nbfollowers ')
            ->addColumns(' (select count(*) from follow where follower_id = this.id) as nbfollowing ')
            ->addColumns(" (select count(*) from `post` where user_id = this.id) as nbpost ");

        $term = Request::get("term");
        if ($term) {
            $qb->where_str(" ( this.username LIKE '%$term%' OR this.firstname LIKE '%$term%' OR this.lastname  LIKE '%$term%' ) ");
        }
        /*if ($user_id = Auth::$user_id)
            $qb->leftJoinOn("relation", 'r',
                " $user_id IN ( r.follower_id, r.following_id) ")
                ->addColumn("r.follow_back", "follow_back")
                ->addColumn(" select count(*) from relation where follower_id = $user_id ", "is_following_by_this_user")
                ->addColumn(" select count(*) from relation where followed_id = $user_id ", "is_followed_by_this_user")
            ;*/

        return $qb->lazyloading();

    }

    /**
     * @Auth(authorized=1)
     * @PUT(path='/:id')
     */
    public function update($id)
    {
        return parent::updateCore($id);
    }

    /**
     * @POST(path='/registration')
     * @return array
     */
    public function registration()
    {

        $rawdata = \Request::raw();

        $userhydrate = $this->hydrateWithJson(new User(), $rawdata["user"]);

        if ($this->error) {
            return array('success' => false,
                'user' => $userhydrate,
                'action' => 'create',
                'detail' => array_values($this->error),
                'error' => $this->error);
        }

        $activationcode = RegistrationController::generatecode();
        $userhydrate->setActivationcode($activationcode);

        // todo: handle it better
        $userhydrate->is_activated = (0);
        $userhydrate->__insert();

        // send mail with activation code $codeactivation
        if ($userhydrate->email) {
            $data = [
                "activation_code" => $activationcode,
                "username" => $userhydrate->username,
            ];
            DMail::init("mails.otp", $data, 'fr')
                ->addReceiver($userhydrate->email, $userhydrate->username)
                ->setTitle("Inscription a la plateforme Events")
                ->setObject("Code de validation")
//                ->preview();
                ->sendMail();
        }
        $pusher = null;
        if (isset($rawdata["fcm_token"])) {
            $pusher = Push_subscription::createInstance([
                'status' => 1,
                'auth_token' => $rawdata["fcm_token"],
                'user_id' => $userhydrate->id,
            ]);
            Push_subscription::initPusher();

            $pusher->fcmPushNotification(
                "Votre code d'activation : " . $activationcode,
                [
                    "entity_id" => $userhydrate->id."",
                    "entity" => "user",
                    "code" => $activationcode,
                    "action" => "activate_account",
                    "url" => "reader3ag://auth/activate-account",
                ]);

        }


        return array('success' => true,
            'user' => $userhydrate,
            'pusher' => $pusher,
            "jwt" => Auth::getJWT($userhydrate),
            'detail' => '');

    }


    /**
     * @POST(path='/authenticate')
     * @return array
     */
    public function authenticate()
    {
        $result = LoginController::connexionAction($_POST['login'], $_POST['password']);
        if (!$result['success'])
            return $result;

        return array(
            'success' => true,
            'detail' => t("Connexion reussi investisseur"),
            "user" => $result['user'],
            "jwt" => Auth::getJWT($result['user'])
            //"userserialize" => $_SESSION[USERAPP]
        );
    }

    /**
     * @GET(path='/:id')
     */
    public function detail($id)
    {

        $user = User::find($id);

        return array('success' => true,
            'user' => $user,
            'detail' => '');

    }

    /**
     * @POST(path='/initresetpassword')
     */
    public function initresetpassword()
    {
        $result = LoginController::resetactivationcode();
        if (!$result['success'])
            return $result;

        $user = $result['user'];
        $fcm_token = Request::post('fcm_token');
        $pusher = Push_subscription::where(
            [
                //'endpoint' => $subscription['endpoint'],
                'auth_token' => $fcm_token,
                'user_id' => $user->id,
            ])->firstOrNull();
        if (!$pusher)
            $pusher = Push_subscription::createInstance([
                'status' => 1,
                'auth_token' => $fcm_token,
                'user_id' => $user->id,
            ]);
        /*$pushers = Push_subscription::where([
            'user_id' => $user->id,
        ])->get();*/
        try {
            Push_subscription::initPusher();
//            foreach ($pushers as $pusher) {
            $pusher->fcmPushNotification(
                "Votre code d'activation : " . $result['activationcode'],
                [
                    "entity_id" => "".$user->id,
                    "entity" => "user",
                    "code" => $result['activationcode'],
                    "action" => "activate_account",
                    "url" => "reader3ag://auth/lost-password",
                ]);

            $result["pusher"] = $pusher;
        } catch (Exception $e) {
            Emaillog::create([
                "log"=> $e->getMessage(),
                "object"=> "Init reset password",
            ]);
            $result['detail'] = $e->getMessage();
            // $e
        }
        return $result;
    }

    /**
     * @POST(path='/resetpassword')
     * @return array
     */
    public function resetpassword()
    {
        return LoginController::resetpassword();
    }

    /**
     * @POST(path='/activateaccount')
     * @return array
     */
    public function activateaccount()
    {
        return RegistrationController::activateaccount();
    }

    /**
     * @Auth(authorized=1)
     * @GET(path='/resentactivationcode')
     * @return array
     */
    public function resentactivationcode()
    {
        $result = RegistrationController::resendactivationcode();
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
                    "url" => "reader3ag://auth/activate-account",
                ]);
        }

        return $result;

    }

    /**
     * @Auth(authorized=1)
     */
    public function changepassword()
    {
        return LoginController::changepwAction();
    }

    public function checktelephoneAction()
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

        $activationcode = RegistrationController::generatecode();
        $userhydrate->setActivationcode($activationcode);
        $userhydrate->__update();

        $userhydrate->phonenumber = $phonenumber;

        Notification::on($userhydrate, "change_telephone", -1)
            ->send($userhydrate, ["username" => $userhydrate->username, "code" => $activationcode]);

        return ["success" => true, "detail" => t("code d'activation vous a été envoyé. Utilisez le pour confirmer le changement de votre numéro.")];

    }

    public function changetelephoneAction()
    {
        $user = User::find(Request::get("user_id"));
        $code = sha1(Request::post("activationcode"));

        if ($user->getActivationcode() !== $code)
            return ["success" => false, "detail" => t("Activation code incorrect")];

        if ($user->getPassword() !== sha1(Request::post("password")))
            return ["success" => false, "detail" => t("Mot de passe incorrect")];

        $user->phonenumber = (Request::post("phonenumber"));

        $user->__update();

        return ["success" => true, "detail" => t("Numéro de téléphone mise a jour")];

    }

    public function deleteAction($id)
    {

        $user = User::find($id);
        $user->__delete();
        return array('success' => true,
            'detail' => '');

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
     * @GET(path='/delete-account')
     * @return array
     */
    public function deleteAccount()
    {
        return [
            'success' => true,
            'message' => "Votre demande a bien ete prise en compte! Vous avez 3 Jours pour annuler votre demande.",
        ];
    }

}
