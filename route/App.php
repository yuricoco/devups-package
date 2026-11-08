<?php

// namespace route;

use DClass\lib\Util;
use Genesis as g;

/**
 * @OA\Info(
 *   title="Devups API",
 *   version="1.0.0",
 *   description="Documentation des WebServices de Devups."
 * )
 *
 * @OA\Server(
 *   url="https://monsite.com/api",
 *   description="Serveur principal"
 * )
 */
class App extends Router
{

    // use WebService;

    protected $layout = "layout.app";
    public static $lang;
    public static $analytic = "";
    public static $ads = "";
    public static $adstop = "";
    //public static $path = "home";
    public static $country = null;

    public static $cssfiles = [];
    public static $jsscript = "";
    public static $jsfiles = [];

    public static $meta_seo_local = "fr_FR";

    public static $meta_seo_title = PROJECT_NAME;
    public static $meta_seo_image = __env . "logo.png";
    public static $meta_seo_description = "";

    public $append_routes = [];

    public function __construct($route)
    {

        parent::__construct($route);
        /**
         * if there is need of user session
         *
         * LoginController::restartsessionAction();
         *
         * self::$user = User::find(User::userapp()->getId());
         * self::$lang = Dvups_lang::getbyattribut("iso_code", local());
         *
         * if (self::$user->getId()) {
         * self::$country = self::$user->country;
         * Request::$uri_get_param['user_id'] = self::$user->getId();
         * } else
         * self::$country = Country::currentCountry();
         */

        self::$jsfiles[] = CLASSJS . "devups.js";
        self::$jsfiles[] = CLASSJS . "model.js";
        self::$jsfiles[] = CLASSJS . "ddatatable.js";
        self::$jsfiles[] = CLASSJS . "dform.js";
        self::$cssfiles[] = assets . "css/dv_style.css";
        self::$cssfiles[] = assets . "css/stylesheet.css";
    }

    public static function isGuest()
    {
        return is_null(self::$user->getId());
    }

    public static function needAuth($message = "")
    {
        if (!self::$user->getId()) {
            redirect(route("login?message=" . $message));
            die;
        }

        return true;
    }

    public static function needUnAuth()
    {
        if (self::$user->getId()) {
            redirect(route("home"));
            die;
        }
    }
 
    /*
     *
     */
    public function helloView()
    {
 
        g::render("hello", []);
    }


}
