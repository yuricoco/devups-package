<?php

namespace route;

use Auth;
use Exception;
use Genesis as g;
use Request;
use Router; 
use devupsfront\ModuleUser\Entity\Oauth_client; 
use Google\Client; 
use Push_emailController; 

class WebService extends Router
{

    /*
     * Entites joignables via l'API generique (/api/<action>/<entite>) et via
     * les FrontController dedies. Toute entite listee ici est protegee par
     * defaut sur TOUTES ses methodes, sauf celles nommees dans $publicMethods.
     */
    public $public_access =
    [
        'push_subscription' => \Push_subscription::class,
        'notification' => \Notification::class,
        'user' => \User::class,
    ];

    /**
     * Methodes autorisees SANS jeton, par entite. Le nom est celui RESOLU par
     * le routeur : `index` pour GET /api/<entite>, `detail` pour
     * GET /api/<entite>/<id>, et le nom de l'action pour une route annotee
     * (@GET(path='/ranking') -> 'ranking').
     *
     * Garder cette liste courte : rien de ce qui touche au compte, a
     * l'ecriture ou a l'argent ne devrait y figurer.
     */
    public static $publicMethods = [
        'user' => [
            'registration',
            'authentification',
            'tokenize',
            'activateaccount',
            'resentactivationcode',
            'initresetpassword',
            'resetpassword',
            'googleAuth',
            'appleAuth',
            'detail',
        ],
    ];

    /**
     * Entites que le chemin GENERIQUE (/api/{create|upload|update|delete|
     * detail|lazyloading}/<entite>, servi par systemServe()) ne doit PAS
     * servir du tout, lectures comprises. A utiliser pour les entites
     * sensibles (paiements, soldes...) qui ont leur FrontController dedie
     * avec ses propres controles : le chemin generique les contournerait.
     */
    private static $genericPathMoneyEntities = [];

    /**
     * Entites dont le chemin generique ne sert que les LECTURES : leurs
     * ecritures passent obligatoirement par leur FrontController dedie.
     */
    private static $genericPathWriteBlockedEntities = [];

    public $append_routes = [];

    /*
     * PoC
     * can it be possible to set an instance of the front controller then while
     * instanciate it, making control of restrication, either public access of
     * global auth restrictions.
     */
    private $frontController;

    public function __construct($route)
    {

        // Protection par defaut : toute entite exposee dans $public_access est
        // restreinte sur toutes ses methodes, sauf celles de $publicMethods.
        //
        // Controle de propriete (optionnel) : declarer ici les colonnes qui
        // designent le proprietaire d'une ligne, ex.
        //     \dclass\devups\Controller\FrontController::setOwnerColumns([
        //         'post' => 'user_id',
        //     ]);
        foreach (array_keys($this->public_access) as $entity) {
            Auth::addRestriction($entity, self::$publicMethods[$entity] ?? []);
        }
        $this->frontController = new \dclass\devups\Controller\FrontController();

        parent::__construct($route);
    }

    /*
     * the service method should always follow the nomenclatura nameserviceServe
     * adding the suffix Serve to the name of the method allow the method to be accessible via webservice.
     */
    /**
     * @OA\Get(
     *     path="/hello",
     *     summary="Récupère la liste des livres",
     *     tags={"Books"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des livres",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Book"))
     *     )
     * )
     */
    public function helloServe()
    {
        g::json_encode(["success" => true, "message" => "hello devups you made your first apicall"]);
    }

    public function systemServe()
    {
        $classname = \dclass\devups\Controller\FrontController::getclassname($this->public_access);

        try {
            $this->frontController->authorization($classname, self::$path);
        } catch (Exception $e) {
            // Le `die` est indispensable : sans lui, l'execution continuerait
            // vers le switch ci-dessous malgre le refus.
            http_response_code(401);
            g::json_encode([
                'success' => false,
                'detail' => $e->getMessage()
            ]);
            die;
        }

        // Garde-fou du chemin generique ($genericPathMoneyEntities /
        // $genericPathWriteBlockedEntities). Meme remarque : le `die` est
        // indispensable.
        if (
            in_array($classname, self::$genericPathMoneyEntities, true)
            || (
                in_array($classname, self::$genericPathWriteBlockedEntities, true)
                && in_array(self::$path, self::$genericPathWriteActions, true)
            )
        ) {
            http_response_code(403);
            g::json_encode([
                'success' => false,
                // Ne dit pas si la ligne existe : ce chemin ne sert ni
                // lecture ni ecriture pour cette entite, quel que soit l'id.
                'detail' => 'this path does not serve this entity',
            ]);
            die;
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

                default:
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
        $statues = [];
        g::json_encode([
            'success' => true, 
            '__cache_version' => __cache_version,
            'countries' => \Country::allrows(),
        ]  );
    }

 
}
