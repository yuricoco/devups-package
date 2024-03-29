<?php

use Genesis as g;

/**
 * this class refer to the front pages. each method represent a page. where we can add js css and some other parameter
 * such as meta titel, title and so on
 *
 * Class App
 */
class App extends \dclass\devups\Controller\FrontController
{

    protected $layout = "layout.app";
    public static $user;

    public function __construct()
    {

        // self::$user = User::find(userapp()->getId());

        self::$jsfiles[] = CLASSJS . "devups.js";
        self::$jsfiles[] = CLASSJS . "model.js";
        self::$jsfiles[] = CLASSJS . "ddatatable.js";
        self::$jsfiles[] = CLASSJS . "dform.js";
        self::$cssfiles[] = assets . "css/dv_style.css";
        self::$cssfiles[] = assets . "css/stylesheet.css";

        (new Request('hello'));
        Request::Route($this, Request::get('path'));


    }

    public static function isGuest(){
        return is_null(self::$user->getId());
    }

    public static function needsession($message = "")
    {
        if (!self::$user->getId()) {
            redirect(route("login?message=" . $message));
            die;
        }

        return true;

    }

    public static function noneedsession()
    {
        if (self::$user->getId()) {
            redirect(route("home"));
            die;
        }

    }

    public function connectView(){

        if (getadmin()->getId()) {

            $user = User::find(Request::get("user_id"));
            LoginController::initSession($user);

            redirect(route("home"));
        }else
            redirect(route("404"));

    }

    public function helloView(){

        Genesis::render("hello", []);
    }

}
