<?php

use Firebase\JWT\JWT;
use route\Admin;
use route\AdminService;
use route\App;
use route\WebService;

/**
 * class Controller 1.0
 *
 * @OA\Info(title="DEVUPS API", version="1.0")
 * @author yuri coco
 */
class Router // extends Request
{

    public $url;
    public static $path;
    public static $class_name;
    public static $uri;
    public static $user;
    protected static $routes = [];
    protected $append_routes = [];
    public static $route_builder = ['request_method' => ''];
    public static $default_path = [
        'GET' => 'index',
        'POST' => 'create',
        'PUT' => 'update',
        'DELETE' => 'delete',
    ];
    public $public_access = [];
    public $entity_array = [];
    protected $namedRoutes = [];
    public static $path_info = '';
    public $default_url = '';
    public static $admin_route = false;
    public static $api_route = false;
    public $request_method = '';
    public $jwt;
    public static $lang = __lang;

    public function __construct($default_url)
    {
        $this->default_url = $default_url;
        self::$uri = $_SERVER['REQUEST_URI'];

        $this->entity_array = array_keys($this->public_access);
        $this->request_method = $_SERVER['REQUEST_METHOD'];
        Request::collectUrlParam($_SERVER['REQUEST_URI']);

        unset($_GET['url']);
        //Request::$uri_get_param = $_GET;
        Request::$uri_get_param['lang'] = self::$lang;
        /* Request::$uri_get_param['path'] = self::$path;*/
        Request::$uri_post_param = $_POST;

        if (file_exists(ROOT . 'cache/routes.php'))
            self::$routes = require_once ROOT . 'cache/routes.php';
        else
            self::$routes = [];

        self::$route_builder['auth'] = false;
    }

    public static function getPathInfo()
    {

        if (isset($_SERVER['PATH_INFO']))
            $path_info = $_SERVER['PATH_INFO'];
        elseif (isset($_GET['url']))
            $path_info = "/" . $_GET['url'];
        elseif (self::$uri == '/admin/')
            $path_info = "/";
        elseif (isset($_SERVER['QUERY_STRING'])) {
            $path_info = "/" . str_replace('url=', '', explode('&', $_SERVER['QUERY_STRING'])[0]);
            //$path_info = "/" . $_GET['url'];

            // dv_dump($path_info, $_GET);
        } else {
            /*$path_info = trim(self::$uri, "/");

            if ($path_info)*/
            $path_info = null;

        }

        self::$path_info = $path_info;
    }

    public static function start()
    {
        self::getPathInfo();


        if (self::$path_info && strpos(self::$path_info, '/api/') === 0) {
            (new WebService('hello'))->runServe();
        } else
            (new \App('hello'))->run();
        die;
    }

    public static function startAdmin()
    {
        self::getPathInfo();
        if (self::$path_info && strpos(self::$path_info, '/api/') === 0) {
            (new AdminService('hello'))->manageServe('');
        } else
            (new Admin('dashboard'))->manage();
        die;
    }

    public function get($path, $callable, $name = null)
    {
        return $this->add($path, $callable, $name, 'GET');
    }

    public function post($path, $callable, $name = null)
    {
        return $this->add($path, $callable, $name, 'POST');
    }

    protected function add($path, $callable, $name, $method)
    {

        $route = new Route($path, $callable);
        $this->routes[$method][] = $route;
        if (is_string($callable) && $name === null) {
            $name = $callable;
        }
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        return $route;
    }

    public final function run()
    {

        global $dlangs;

        if (self::$path_info) {
            $this->url = trim(self::$path_info, '/');
            $lang = substr($this->url, 0, 2);

            if (isset($dlangs[$lang])) {
                self::$lang = $lang;
                $this->url = substr($this->url, 3, strlen($this->url));
            }

            $this->runCache($this->url);

            self::$path = $this->url == "" ? $this->default_url : explode('/', $this->url)[0];
        } else {
            $this->url = $this->default_url;
            self::$path = $this->default_url;
        }

        self::$route_builder['path'] = $this->url;
        $function = implode('', array_map('ucfirst', explode('-', str_replace('_', '-', self::$path))));
        $function .= "View";

        if (!method_exists($this, $function)) {
            if (__prod)
                Genesis::render('404');
            else
                var_dump(" You may create method " . " " . $function . " in entity. ");
            die;
        }

//        $lines = file(ROOT."cache/routes.php");
//        array_splice($lines, 1, 0, "new content");

        self::$route_builder['method'] = $function;
        self::$route_builder['ctrl'] = 'App';
        try {
            $this->mapFunction('App', $function);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function runCache($url)
    {
        if (!__route_cache)
            return null;

        $urlvalues = [];
        $match = null;
        if (isset(self::$routes[$url]))
            $metadata = self::$routes[$url];
        else {
            foreach (self::$routes as $path => $metadata) {
                $r = new Route($path, []);
                $match = $r->match($url);
                $urlvalues = $r->matches;
                if ($match)
                    break;
            }
            if (!$match)
                return null;
            $url = $path;
        }

        // dv_dump($metadata);
        if (isset($metadata["@admin"])) {

            $methodView = explode("@", $metadata["@admin"]['method']);
            $ctrl = $methodView[0];
            $method = $methodView[1];
            $metadata = $metadata["@admin"];

            $admin = getadmin();
            if (isset($metadata["dclass"])) {
                global $viewdir, $moduledata;
//                $entity = Dvups_entity::getbyattribut('this.name',
//                    str_replace('-', '_', $metadata["dclass"]));
                $viewdir[] = '';//$entity->dvups_module->hydrate()->moduleRoot() . 'Resource/views';

                $moduledata = '';//$entity->dvups_module;
                $moduledata->dvups_entity = '';//$admin->dvups_role->collectDvups_entityOfModule($moduledata);

                Request::$uri_get_param["dclass"] = $metadata["dclass"];
                self::$class_name = $metadata["dclass"];
            }


        } else if (isset($metadata["@auth"])) {
            $methodView = explode("@", $metadata["@auth"]['method']);
            $ctrl = $methodView[0];
            $method = $methodView[1];
            $Auth = new Auth();
            $result = $Auth->execute($this);
            if (is_array($result) && $result['success'] == false) {
                throw new Exception($result['detail']);
            }
            Request::$uri_get_param['user_id'] = $this->jwt->userId;
            $metadata = $metadata["@auth"];
        } else {
            $methodView = explode('@', $metadata['method']);
            $ctrl = $methodView[0];
            $method = $methodView[1];
        }

        if (isset($metadata["path"])) {
            self::$path = $metadata["path"];
            Request::$uri_get_param["path"] = $metadata["path"];
        }
        if (isset($metadata["dclass"])) {
            Request::$uri_get_param["dclass"] = $metadata["dclass"];
            self::$class_name = $metadata["dclass"];
        }
        /*if (isset($metadata['entity']))
            Request::$uri_get_param['dclass'] = $metadata['entity'];*/

        if (isset($metadata["route_params"])) {
            $routeParams = array_combine($metadata["route_params"], $urlvalues);
            Request::$uri_get_param += $routeParams;
        }

        if (in_array($ctrl, [\App::class, WebService::class, Admin::class, AdminService::class]))
            $app = $this;
        else {
            $app = new $ctrl($this);
        }

        // handle when
        if (isset($metadata['request_method']) && $this->request_method != 'GET' && $metadata['request_method'] != '') {
            if ($this->request_method != $metadata["route_params"])
                throw new Exception("Request method {$this->request_method} not allowed for this route");
        }

        $methoparams = [];
        $reflection = new ReflectionAnnotatedClass($ctrl);
        $method_object = $reflection->getMethod($method);//
        $mparams = $method_object->getParameters();

        if (!($metadata['params'])) {

            foreach ($mparams as $i => $param) {
                if (isset(Request::$uri_get_param[$param->name]))
                    $methoparams[$param->name] = Request::$uri_get_param[$param->name]; // Request::get($param->name)

            }
            if (!$mparams) {
                if (isset($metadata['api']))
                    Genesis::json_encode($app->{$method}());
                else
                    $app->{$method}();
                die;
            }
            // update route cache if there were not method params and then there is
            $metadata['params'] = array_keys($methoparams);
            self::$routes[$url] = $metadata;
            file_put_contents(ROOT . "cache/routes.json", json_encode(self::$routes));

        } else {

            if (!$mparams) {
                // update route cache if there were method params and then there is not
                $metadata['params'] = [];
                self::$routes[$url] = $metadata;
                file_put_contents(ROOT . "cache/routes.json", json_encode(self::$routes));
            } else
                $methoparams = array_intersect_key(Request::$uri_get_param, array_combine($metadata['params'], $metadata['params']));
        }

        if (isset($metadata['api']))
            Genesis::json_encode(call_user_func_array(array($app, $method), $methoparams));
        else
            call_user_func_array(array($app, $method), $methoparams);
        die;
    }

    public function runServe()
    {

        $allowed_origins = explode(',', __csrf_origin);
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowed_origins) || $origin == 'null') {
            header("Access-Control-Allow-Origin: " . ($origin === 'null' ? '*' : $origin));
            header("Access-Control-Allow-Credentials: true"); // Autoriser les credentials (cookies, tokens)
        }

// 🔥 Ajouter les headers autorisés ici
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Auth, Columns, common, Content-Type, X-Requested-With");

        // Gérer les requêtes OPTIONS (préflight)
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        if (isset($_SERVER['HTTP_COLUMNS'])) {

            Request::$uri_header_param = json_decode($_SERVER['HTTP_COLUMNS'], true);

        }

        //we initialize the session_user for the life cycle
        $Auth = new Auth();
        $result = $Auth->execute();
        if (is_object($result)) {
            Router::$route_builder['auth'] = true;
            Request::$uri_get_param['user_id'] = $result->userId;
        }
        // require_once ROOT.'cache/routes.php';
        self::$api_route = true;

        self::$path = Request::getpath();
        $path = explode("/", trim(self::$path, '/'));


        if (count($path) >= 2 && in_array($path[0],
                ['create', 'upload', 'detail', 'read', 'update', 'delete', 'lazyloading'])) {
            self::$path = $path[0];
            Request::$uri_get_param["path"] = $path[0];
            Request::$uri_get_param["dclass"] = $path[1];
            Router::$route_builder['entity'] = str_replace("-", "_", $path[1]);
            $function = 'systemServe';
        } elseif (in_array($path[0], $this->entity_array)) {
            Request::$uri_get_param["dclass"] = $path[0];
            Router::$route_builder['entity'] = str_replace("-", "_", $path[0]);

            if (isset($path[1]))
                Request::$uri_get_param["path"] = $path[1];

            try {
                Genesis::json_encode(\dclass\devups\Controller\FrontController::frontServe($path[0], $this));
            } catch (Exception $e) {
//                    self::$path = $path[0];
                Genesis::json_encode($e->getMessage());

            }
        } else {
            self::$path = $path[0];
            $function = \DClass\lib\Util::toPascalCase(self::$path) . "Serve";
        }

        $named = get_called_class();

        if (!method_exists($this, $function)) {

            if (!isset($this->append_routes[Request::getpath()])) {
                http_response_code(404);
                if (__prod) {
                    http_response_code(404);
                    Genesis::json_encode(["success" => false, "message" => "404 :" . $function . " service note found"]);
                } else
                    Genesis::json_encode(" You may create method " . " " . $function . " in entity. ");
            } else
                $function = $this->append_routes[Request::getpath()];

        }

        try {
            $this->mapFunction($named, $function);
        } catch (Exception $exception) {
            http_response_code(404);
            throw $exception;
            /*echo json_encode([
                'success' => false,
                'detail' => $exception->getMessage(),
            ]);*/
        }

    }

    public final function manage()
    {

        self::$admin_route = true;
//        self::getPathInfo();
// move comment scope to enable authentication

        $this->url = self::$path_info;
        if (!strpos(self::$path_info, '/admin/'))
            $routmap = explode("/", (self::$path_info));
        else
            $routmap = explode("/", trim(self::$path_info, "/"));
        $nbmap = count($routmap);
        self::$path = $routmap[$nbmap - 1];

//        dv_dump(self::$path_info, self::$path);
        if (!isset($_SESSION[ADMIN]) && ( self::$path != 'connexion' || self::$path != 'connexion-action')) {
            //$token = sha1(\DClass\lib\Util::randomcode());
            header("location: " . __env . 'admin/login.php');

        }

        if (self::$path_info == '/' || !self::$path_info) {
            $this->url = $this->default_url;
            self::$path = $this->default_url;
        } else if (self::$path_info) {

            $this->runCache(self::$path_info ?? $this->default_url);

            /*dv_dump($routmap);
            if (count($routmap) == 3) {

            }else*/
            if ($nbmap >= 5) {

                Request::$uri_get_param['path'] = self::$path;
                Request::$uri_get_param['dclass'] = str_replace("-", '_', ucfirst($routmap[3]));
                Request::$uri_get_param['dmod'] = $routmap[2];
                Request::$uri_get_param['dcomp'] = $routmap[1];

                $global_config = require ROOT . 'config/dvups_configurations.php';
                $entity = $global_config['' . Request::$uri_get_param['dclass']];
//                dv_dump($entity, Request::$uri_get_param['dclass']);
//                $entity = Dvups_entity::where("this.url", Request::get("dclass"))->firstOrNull();

                if ($entity) {

                    \dclass\devups\Controller\Controller::views($this, $entity);
                    die();
                }
            }
            else if ($nbmap >= 3) {
                if ($nbmap === 3 )
                    self::$path = 'dashbaord';
                else
                    self::$path = $routmap[$nbmap - 1];

                Request::$uri_get_param['path'] = self::$path;
                Request::$uri_get_param['dmod'] = $routmap[2];
                Request::$uri_get_param['dcomp'] = $routmap[1];

                $global_config = require ROOT . 'config/module_configurations.php';
                if (isset($global_config[$routmap[2]])) {
                    $module = $global_config[$routmap[2]];
                    $module['path'] = $routmap[1] . '/' . $routmap[2]."/";

                    \dclass\devups\Controller\Controller::viewsModule($this, $module);
                    die();

                }
            }
            else {
                self::$path = $routmap[$nbmap - 1];
                Request::$uri_get_param['path'] = self::$path;
            }

            /*header('HTTP/1.0 400 Bad Request path not correct');
            die;*/

        }/**/

        $function = implode('', array_map('ucfirst', explode('-', str_replace('_', '-', self::$path))));
        $function .= "View";


        if (!method_exists($this, $function)) {
            if (__prod)
                Genesis::render('404');
            else
                var_dump(" You may create method " . " " . $function . " in entity. ");
            die;
        }

        $named = get_called_class();

        //$reflection = new ReflectionAnnotatedClass($named);
        $this->mapFunction($named, $function);

    }

    public function manageServe($default_route)
    {

        self::$admin_route = true;
        self::$api_route = true;
        if (!isset($_SESSION[ADMIN])) {

            // Genesis::json_encode(["success" => false, "message" => "admin session expired!!"]);

        }

        $this->runCache(self::$path_info ?? $this->default_url);

        $this->url = self::$path_info;
        self::$path = str_replace('/api/', '', self::$path_info);
        $path = explode("/", self::$path);

        /*if (self::$path_info) {
            $routemap = explode("/", self::$path_info);
            if (isset($routemap[2]))
                self::$path = $routemap[2];
            else
                self::$path = $default_route ?? $this->default_url;
        } else {
            self::$path = $_GET['path'];
        }
        $path = explode(".", self::$path);*/

        if (count($path) == 2) {
            if (in_array($path[0], ['create', 'upload', 'detail', 'read', 'update', 'delete', 'lazyloading'])) {
                self::$path = $path[0];
                self::$class_name = str_replace('-', '_', $path[1]);
                Request::$uri_get_param["path"] = $path[0];
                Request::$uri_get_param["dclass"] = ucfirst(self::$class_name);
                $function = 'systemServe';
            } else {
                Request::$uri_get_param["path"] = $path[1];
                self::$path = $path[1];
                self::$class_name = str_replace('-', '_', $path[0]);
                Request::$uri_get_param["dclass"] = ucfirst(self::$class_name);

                $global_config = require ROOT . 'config/dvups_configurations.php';
                $entity = $global_config["" . Request::$uri_get_param["dclass"]];
//                $entity = Dvups_entity::where("this.url", $path[0])
//                    ->orwhere("this.name", $path[0])->firstOrNull();

                if ($entity)
                    Genesis::json_encode(\dclass\devups\Controller\Controller::serve($this, $entity));
            }
        } else {
            $function = implode('', array_map('ucfirst', explode('-', str_replace('_', '-', self::$path))));
            $function .= "Service";
        }


        if (!method_exists($this, $function)) {
            if (__prod)
                Genesis::json_encode(["success" => false, "message" => "404 :" . $function . " service note found"]);
            else
                dv_dump(" You may create method " . " " . $function . " in entity. ");
            die;
        }

        $named = get_called_class();
        try {
            $this->mapFunction($named, $function);
        } catch (Exception $exception) {

        }

        /*if( !isset($this->routes[$_SERVER['REQUEST_METHOD']])){
            throw new Exception('REQUEST_METHOD does not exist');
        }
        foreach($this->routes[$_SERVER['REQUEST_METHOD']] as $route){
            if($route->match($this->url)){
                return $route->call();
            }
        }

            throw new Exception('No matching routes');*/
    }

    /**
     * @throws Exception
     */
    private function mapFunction($classname, $function)
    {

        $reflection = new ReflectionAnnotatedClass($classname);
        $method = $reflection->getMethod($function);//
        $mparams = $method->getParameters();

        $methoparams = [];

        foreach ($mparams as $i => $param) {
            if (isset(Request::$uri_get_param[$param->name]))
                $methoparams[$param->name] = Request::$uri_get_param[$param->name]; // Request::get($param->name)

        }

        if ($method->hasAnnotation('Auth')) {

            self::$route_builder['auth'] = true;
            if (!Auth::$user_id) {
                header('HTTP/1.0 400 Bad Request AUTHORIZATION not found');
                /*$result = ([
                    'success' => false,
                    'detail' => "Bad Request AUTHORIZATION or AUTH not found",
                ]);*/
                throw new Exception("Bad Request AUTHORIZATION or AUTH not found");
            }
            /*$Auth = $method->getAnnotation('Auth');
            $result = $Auth->execute($this);
            if (is_array($result) && $result['success'] == false) {
                throw new Exception($result['detail']);
            }

            Request::$uri_get_param['user_id'] = $this->jwt->userId;*/
        }

        if ($method->hasAnnotation($this->request_method)) {
            $methodView = $method->getAnnotation($this->request_method);
        } elseif ($method->hasAnnotation('Api')) {
            $methodView = $method->getAnnotation('Api');
        } else {

            $methodView = false;
//            http_response_code(405);
//            return array('details' => "Request method {$this->request_method} not allowed for this route");

        }

        if ($methodView)
            try {
                $this->url = $methodView->execute($this);
            } catch (Exception $e) {
                return $e;
            }

        foreach ($mparams as $i => $param) {
            if (isset(Request::$uri_get_route_param[$param->name]))
                $methoparams[$param->name] = Request::$uri_get_route_param[$param->name]; // Request::get($param->name)

        }
        self::$route_builder['request_method'] = $this->request_method;

//        self::cacheRoute($this->url, $classname . '@' . $function, array_keys($methoparams), self::$route_builder['auth'], self::$route_builder['request_method']);

        if (!$mparams) {

            $this->{$function}();
            die;

        }

        call_user_func_array(array($this, $function), $methoparams);

    }

    public static function cacheRoute($path, $methodview, $params = [], $auth = false, $request_method = "GET")
    {

//

//        if (__prod)
            return null;

        $route = [
            'method' => $methodview, 'params' => $params,
        ];
        if (Request::$uri_get_route_param)// ["route"]
            $route["route_params"] = array_keys(Request::$uri_get_route_param);
        if (isset(Request::$uri_get_param["path"]))
            $route["path"] = Request::$uri_get_param["path"];

        if (isset(Request::$uri_get_param["dclass"]))
            $route["dclass"] = Request::$uri_get_param["dclass"];

        if ($request_method != 'GET')
            $route['request_method'] = $request_method;

        if (self::$api_route)
            $route["api"] = true;

        if (self::$admin_route)
            $route = ['@admin' => $route];
        else if ($auth)
            $route = ['@auth' => $route];

        self::$routes['/' . $path] = $route;
        // Convertir le tableau en une chaîne de PHP valide
        $phpContent = "<?php\n\nreturn " . var_export(self::$routes, true) . ";\n";
//        file_put_contents(ROOT . "cache/routes.json", json_encode(self::$routes));
//        if (!file_exists(ROOT . "cache/routes.php"))
        file_put_contents(ROOT . "cache/routes.php", $phpContent);
        //file_put_contents(ROOT."cache/routes.php", "<?php \n".print_r(self::$routes, true));

    }

    public function url($name, $params = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception('No route matches this name');
        }
        return $this->namedRoutes[$name]->getUrl($params);
    }

    public function authorization()
    {

        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            header('HTTP/1.0 400 Bad Request AUTHORIZATION not found');
            exit;
        }

        if (!preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Token not found in request';
            exit;
        }
        $this->jwt = $matches[1];
        if (!$this->jwt) {
            // No token was able to be extracted from the authorization header
            header('HTTP/1.0 400 Bad Request');
            exit;
        }
        $hash = ['HS512'];
        $token = JWT::decode($this->jwt, jwt_secret_Key, $hash);
        $now = new DateTimeImmutable();
        $serverName = __server;

        if ($token->iss !== $serverName ||
            $token->nbf > $now->getTimestamp() ||
            $token->exp < $now->getTimestamp()) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }
    }

    public static function needAuth($message = "")
    {

    }

    public static function needUnAuth()
    {
    }

}