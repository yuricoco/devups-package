<?php


class UserFrontController extends UserController
{

    public static function renderAccount()
    {
        $user = new User();
        if (isset($_SESSION[USERID]))
            $user = User::find($_SESSION[USERID]);

        Genesis::render('_account', ["user" => $user]);

    }

    public function dashboard()
    {
        //$qb = \Favorite::select()->where('user_id', $_SESSION[USERID]);
        return [
            "user" => \User::userapp(),
            "nborder" => 0,
        ];
    }

    public function account()
    {
        $user = \User::find($_SESSION[USERID]);
        return [
            "user" => $user
        ];
    }


    public function lostpasswordView()
    {
        //self::$jsfiles[]= CLASSJS . "model.js";
        self::$jsfiles[] = CLASSJS . "dform.js";
        self::$jsfiles[] = d_assets("js/userCtrl.js");
        return \Response::$data;
    }

    public function confirmaccountView()
    {
        //self::$jsfiles[]= CLASSJS . "model.js";
        self::$jsfiles[] = CLASSJS . "dform.js";
        self::$jsfiles[] = d_assets("js/userCtrl.js");
        return \Response::$data;
    }

    public function resetpasswordView()
    {
        self::$jsfiles[] = CLASSJS . "dform.js";
        self::$jsfiles[] = d_assets("js/userCtrl.js");
        return \Response::$data;
    }

    public function registration()
    {

        $rawdata = \Request::raw();

        $userhydrate = $this->hydrateWithJson(new User(), $rawdata["user"]);

        if ( $this->error ) {
            return  array(  'success' => false,
                'user' => $userhydrate,
                'action' => 'create',
                'error' => $this->error);
        }

        $activationcode = RegistrationController::generatecode();
        $userhydrate->setActivationcode($activationcode);

        // todo: handle it better
        $userhydrate->is_activated = (0);
        $userhydrate->setApiKey(\DClass\lib\Util::randomcode());
        $userhydrate->__insert();

        // send mail with activation code $codeactivation
        if ($userhydrate->getEmail()) {
            $data = [
                "activation_code" => $activationcode,
                "username" => $userhydrate->getFirstname(),
            ];
            Reportingmodel::init("otp", Dvups_lang::getByIsoCode($userhydrate->lang)->id)
                ->addReceiver($userhydrate->getEmail(), $userhydrate->getUsername())
                ->sendMail($data);
        }

        //  notification -1 send only sms
//        Notification::on($userhydrate, "registered", -1)
//            ->send([$userhydrate], ["username" => $userhydrate->getFirstname(), "code" => $activationcode]);


        return array('success' => true,
            'user' => $userhydrate,
            "jwt" => Auth::getJWT($userhydrate),
            'detail' => '');

    }

    public function updateAction($id, $user_form = null)
    {

        $rawdata = \Request::raw();

        $user = $this->hydrateWithJson(new User($id), $rawdata["user"]);

        $user->__update();
        return array('success' => true,
            'user' => $user,
            'detail' => '');

    }

    public function authentification()
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

    public function updateApiAction($id, $user_form = null)
    {

        $rawdata = \Request::raw();

        $user = $this->hydrateWithJson(new User($id), $rawdata["user"]);

        if ($this->error) {
            return array('success' => false,
                'user' => $user,
                'error' => $this->error);
        }

        $user->__update();
        return array('success' => true,
            'user' => $user,
            'detail' => '');

    }


    /**
     * @Auth(authorized=1)
     * @param $id
     * @return array
     */
    public function detailView($id)
    {

        $user = User::find($id);

        return array('success' => true,
            'user' => $user,
            'detail' => '');

    }

    public function initresetpassword()
    {
        return LoginController::resetactivationcode();
    }

    public function resetpassword()
    {
        return LoginController::resetpassword();
    }

    public function activateaccount()
    {
        return RegistrationController::activateaccount();
    }
    public function resentactivationcode()
    {
        return RegistrationController::resendactivationcode();
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
        if(isset($_POST['phonecode']))
            $country = Country::where("phonecode", Request::post("phonecode"))->firstOrNull();
        else
            $country = Country::where("iso", Request::post("country_iso"))->firstOrNull();

        if (is_null($country)){
            return [
                'success'=> false,
                'detail'=> t('country not found'),
            ];
        }

        $phonenumber = User::sanitizePhonenumber(Request::post("phonenumber"), $country->getPhonecode());

        $nbuser = User::select()
            ->where('user.phonenumber', "=", $phonenumber)
            ->count();

        if($nbuser)
            return ["success" => false, "detail" => t("Ce numéro de téléphone existe déjà")];

        $userhydrate = User::find(Request::get("user_id"));

        $activationcode = RegistrationController::generatecode();
        $userhydrate->setActivationcode($activationcode);
        $userhydrate->__update();

        $userhydrate->phonenumber = $phonenumber;

        Notification::on($userhydrate, "change_telephone", -1)
            ->send($userhydrate, ["username"=>$userhydrate->username, "code"=>$activationcode]);

        return ["success" => true, "detail" => t("code d'activation vous a été envoyé. Utilisez le pour confirmer le changement de votre numéro.")];

    }

    public function  changetelephoneAction()
    {
        $user = User::find(Request::get("user_id"));
        $code = sha1(Request::post("activationcode"));

        if($user->getActivationcode() !==  $code )
            return ["success" => false, "detail" => t("Activation code incorrect")];

        if($user->getPassword() !== sha1(Request::post("password")))
            return ["success" => false, "detail" => t("Mot de passe incorrect")];

        $user->phonenumber = (Request::post("phonenumber"));

        $user->__update();

        $_SESSION[USER] = serialize($user);

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

}
