<?php

namespace route;

use Auth;
use dclass;
use Exception;
use Genesis as g;
use Google\Client;
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
            'push_subscription' => \Push_subscription::class,
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

        Auth::addRestriction('push_subscription', []);

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


    /**
     * @POST(path='/auth/google')
     * @return void
     */
    public function googleAuth()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['idToken'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Token manquant']);
            return;
        }

        $client = new Client(['client_id' => GOOGLE_CLIENT_ID]);
        $payload = $client->verifyIdToken($input['idToken']);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            return;
        }

        $oauth = Oauth_client::where([
            'client' => 'google',
            'user_ext_id' => $payload['sub'],
        ])->firstOrNull();
        if ($oauth) {

            $newuser = \User::find($oauth->user_id);
            $newuser->last_login = date('Y-m-d');

        } else {

            $existed = \User::where('this.email', $payload['email'])->firstOrNull();
            // Infos de l'utilisateur Google
            if ($existed){
                $newuser = $existed;
                $newuser->username = $payload['name'];
                $newuser->email = $payload['email'];
                $newuser->profile = $payload['picture'];
                $newuser->is_activated = 1;
                $newuser->last_login = date('Y-m-d');

                $newuser->__update();
            }else{
                $newuser = new \User();
                $newuser->username = $payload['name'];
                $newuser->email = $payload['email'];
                $newuser->profile = $payload['picture'];
                $newuser->is_activated = 1;

                $newuser->__insert();
            }

        }

        // Enregistrer l'utilisateur en session ou en base

        g::json_encode(array('success' => true,
            'user' => $newuser,
            "jwt" => Auth::getJWT($newuser),
            'detail' => ''));

    }

    public function shareServe($path, $id){

        switch ($path){

            default:
                return null;
        }

    }


}
