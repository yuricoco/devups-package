<?php

// namespace route;

use BarcodeBakery\Common\BCGColor;
use BarcodeBakery\Common\BCGDrawing;
use DClass\lib\Util;
use Genesis as g;
//use LoginController;
//use Request;
//use Router;
//use User;

/**
 * this class refer to the front pages. each method represent a page. where we can add js css and some other parameter
 * such as meta titel, title and so on
 *
 * Class App
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

    public static $meta_seo_title = "3ag edition";
    public static $meta_seo_image = "https://3ag-edition.com/web/img/3ag.png";
    public static $meta_seo_description = "3ag édition Maison d'édition  de bandes dessinées camerounaise, publier sa bande dessinée au cameroun devient facile et accessible, bande dessinée camerounaise, maison d'édition camerounaise, un éditeur de bande dessinée (manga, comics, ...) au cameroun ouvert au monde. Site de lecture en ligne de bandes dessinnées.";

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

    public function connectView()
    {

        if (getadmin()->getId()) {

            $user = User::find(Request::get("user_id"));
            LoginController::initSession($user);

            redirect(route("home"));
        } else
            redirect(route("404"));

    }

    public function helloView()
    {

        g::render("hello", []);
    }


    /**
     * @MethodView(path="about-us")
     */
    public function aboutUsView()
    {
        //Genesis::render("_setting", []);
    }

}
