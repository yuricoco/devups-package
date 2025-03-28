<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegistrationController
 *
 * @author azankang
 */

use dclass\devups\Controller\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class RegistrationController extends Controller {

    public static function checkmailAction()
    {
        $email = Request::post("email");

        $nbuser = User::select()
            ->where('user.email', "=", $email)
            ->count();

        if($nbuser)
            return ["success" => false, "detail" => t("Cette adresse mail existe déjà!")];

        $user = User::find(Request::get("user_id"));
        $activationcode = RegistrationController::generatecode();
        $user->setActivationcode($activationcode);

        $user->__update();

        $_SESSION[USER] = serialize($user);

        // send mail with activation code $codeactivation
        $data = [
            "activation_link" => route('login').'?vld='.$activationcode.'&u_id='.$user->getId(),
            "activation_code" => $activationcode,
            "username" => $user->getFirstname(),
        ];

        Reportingmodel::init("change_email", Dvups_lang::getByIsoCode($user->lang)->id)
            ->addReceiver($email, $user->getUsername())
            ->sendMail($data);

        return ["success" => true, "detail" => t("code d'activation envoyé")];

    }

    public static function changeemailAction(){
        $user = User::find(Request::get("user_id"));
        $code = sha1(Request::post("activationcode"));

        if($user->getActivationcode() !==  $code )
            return ["success" => false, "detail" => t("Activation code incorrect")];

        if($user->getPassword() !== sha1(Request::post("password")))
            return ["success" => false, "detail" => t("Mot de passe incorrect")];

        $user->setEmail(Request::post("email"));

        $user->__update(["email" => Request::post("email")]);

        $_SESSION[USER] = serialize($user);

        return ["success" => true, "detail" => t("adress mail mise a jour")];

    }

    public static function generatecode() {

        $datetime = new DateTime();

        if (__prod)
            $generate = sha1($datetime->getTimestamp());
        else
            $generate = '12345';

        return substr($generate, 0, 5);
    }

    public static function resendactivationcode() {

        $user = User::find(Request::get("user_id"));
        $activationcode = RegistrationController::generatecode();
        $user->setActivationcode($activationcode);

        $user->__update();

        // $_SESSION[USER] = serialize($user);

        // send mail with activation code $codeactivation
        if ($user->email) {
            $data = [
                "activation_link" => route('login') . '?vld=' . $activationcode . '&u_id=' . $user->id,
                "activation_code" => $activationcode,
                "username" => $user->username,
            ];

            DMail::init("mails.otp", $data, $user->lang)
                ->addReceiver($user->email, $user->username)
                ->setObject("")
                ->sendMail();

        }

//        Notification::$send_sms = true;
//        Notification::on($user, "otp")
//            ->send($user, ["username"=>$user->getFirstname(), "code"=>$activationcode]);

        return [
            "success" => true,
            "activationcode" => $activationcode,
            "detail" => t("un nouveau code d'activation vous a été renvoyé.")
        ];

    }

    public static function activateaccount() {
//        global $appuser;
        $appuser = User::find(Request::get("user_id"));

        return $appuser->activateaccount($_POST["activationcode"]);
    }

}
