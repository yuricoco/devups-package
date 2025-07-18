<?php

namespace route;

use Auth;
use dclass;
use Exception;
use Genesis as g;
use Push_emailController;
use Request;
use Router;

/**
 * this class refer to the front pages. each method represent a page. where we can add js css and some other parameter
 * such as meta titel, title and so on
 *
 * Class App
 */
class WebService extends Router
{

    /*
     * collection of the entity available for the webservice
     */
    public $public_access =
        [
            'user' => \User::class,
            'notification' => \Notification::class,
        ];

    public $append_routes = [
        '/auth/google' => 'googleAuth'
    ];
    /*
     * PoC
     * can it be possible to set an instance of the front controller then while instanciate it, making control of restrication,
     * either public access of global auth restrictions.
     */
    private $frontController;

    public function __construct($route)
    {

        // this syntax allows restriction on all the methods in the userFrontController
        // if there is some method non affectec by the auth restriction, we can add them through the second parameter
        Auth::addRestriction('user', ['registration','lazyloading','detail', 'deleteAccount',
            'authenticate', 'logout', 'changepassword', 'resentactivationcode',
            'initresetpassword', 'activateaccount', 'resetpassword']);


        $this->frontController = new dclass\devups\Controller\FrontController();
        parent::__construct($route);

    }

    /*
     * the service method should always follow the nomenclatura nameserviceServe
     * adding the suffix Serve to the name of the method allow the method to be accessible via webservice.
     */
    public function helloServe()
    {
        g::json_encode(["success" => true, "message" => "hello devups you made your first apicall"]);
    }

    public function systemServe()
    {
        $classname = dclass\devups\Controller\FrontController::getclassname($this->public_access);

        try {
            $this->frontController->authorization($classname, self::$path);
        } catch (Exception $e) {
            g::json_encode([
                'success' => false,
                'detail' => $e->getMessage()]);
        }

        try {

            switch (self::$path) {
                case 'create':
                    g::json_encode($this->frontController->createCore());
                    break;
                case 'upload':
                    g::json_encode($this->frontController->uploadCore(Request::get("id")));
                    break;
                case 'update':
                    g::json_encode($this->frontController->updateCore(Request::get("id")));
                    break;
                case 'delete':
                    g::json_encode($this->frontController->deleteCore(Request::get("id")));
                    break;
                case 'detail':
                    g::json_encode($this->frontController->detailCore(Request::get("id")));
                    break;
                case 'lazyloading':
                    g::json_encode($this->frontController->ll());
                    break;
                case 'dcollection':
                    g::json_encode($this->frontController->dcollection());
                    break;
                case 'dcronjob':
                    g::json_encode((new Push_emailController())->cronjobAction());
                    break;

                default :
                    g::json_encode(["success" => false, "message" => "404 :" . Request::get('path') . " page note found"]);
                    break;
            }

        } catch (\Exception $e) {
            g::json_encode([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }
    }


    public function cacheServe()
    {
        $cache = Request::get('v');
        if (__cache_version == $cache)
            g::json_encode([
                'success' => false,
                'detail' => 'You are up to date',
            ]);


        g::json_encode([
            'success' => true,
        ]);
    }


}
