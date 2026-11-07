<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Request
 *
 * @author Aurelien Atemkeng
 */
class Request
{


    public static $uri_get_param = [];
    public static $uri_header_param = [
        'dcolumns' => [],
        'dlang' => '',
    ];
    public static $uri_get_route_param = [];
    public static $uri_post_param = [];
    public static $uri_raw_param = [];
    public static $uri = "";
    public static $system = "customer";

    static function strReplaceAssoc(array $replace, $subject)
    {
        return str_replace(array_keys($replace), array_values($replace), $subject);
    }

    public static function collectUrlParam($url)
    {
        $query = explode("?", $url);
        if (!isset($query[1]))
            return 1;

        // Parse bracket-notation array params (e.g. month_year[month]=7&month_year[year]=2026)
        // before the custom string replacements that would mangle the brackets.
        parse_str($query[1], $parsedArrayParams);
        foreach ($parsedArrayParams as $key => $value) {
            if (is_array($value)) {
                Request::$uri_get_param[$key] = $value;
            }
        }

        $query[1] = self::strReplaceAssoc([
            "%3C" => "<",
            "%20" => " ",
            "%3A" => ":",
            "%5B" => "['",
            "%5D" => "']",
            "+d" => " d",
            "+a" => " a",
            "+D" => " D",
            "+A" => " A",
        ], $query[1]);

        $param = explode('&', $query[1]);
        foreach ($param as $el) {
            $kv = explode("=", $el);
            if (isset($kv[1])) {
                $defaulthttpgetkey = str_replace(".", "_", $kv[0]);
                if (isset($_GET[$defaulthttpgetkey])) {
                    Request::$uri_get_param[$kv[0]] = $_GET[$defaulthttpgetkey];
                    unset($_GET[$defaulthttpgetkey]);
                } else
                    Request::$uri_get_param[$kv[0]] = $kv[1];
            }
        }
    }

    function __construct($default_path = 'hello')
    {

        Request::$uri_get_param["path"] = $default_path;

        self::$uri = str_replace("api//", "api/", $_SERVER['REQUEST_URI']);

        $uri = [];
        if (isset($_SERVER['REDIRECT_URL'])) {
            $REDIRECT_URL = str_replace("api//", "api/", $_SERVER['REDIRECT_URL']);
            $uristr = str_replace($REDIRECT_URL . '?', "", self::$uri);
            // dv_dump($_SERVER);
            if ($uristr != $REDIRECT_URL) {
                $uri = [$REDIRECT_URL, $uristr];
            }
        } else {
            $uri = explode('?', self::$uri);
        }

        if (isset($uri[1])) {

            $uri[1] = self::strReplaceAssoc([
                "%3C" => "<",
                "%20" => " ",
                "%3A" => ":",
            ], $uri[1]);
            //$uri[1] = str_replace("%3C", "<",$uri[1]);
            $param = explode('&', $uri[1]);;
            //$param = explode('&', $uri[1]);
            foreach ($param as $el) {
                $kv = explode("=", $el);
                if (isset($kv[1])) {
                    $defaulthttpgetkey = str_replace(".", "_", $kv[0]);
                    if (isset($_GET[$defaulthttpgetkey])) {
                        Request::$uri_get_param[$kv[0]] = $_GET[$defaulthttpgetkey];
                        unset($_GET[$defaulthttpgetkey]);
                    } else
                        Request::$uri_get_param[$kv[0]] = $kv[1];
                }
            }
        }

        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                Request::$uri_get_param[$key] = $value;
            }
        }

        if (isset($_POST)) {
            foreach ($_POST as $key => $value) {
                Request::$uri_post_param[$key] = $value;
            }
        }
    }

    public static function classroot($key)
    {
        if (isset(Request::$uri_get_param[$key]))
            return explode("/", Request::$uri_get_param[$key])[ENTITY];
        else
            return false;
    }

    public static function get($key, $default = false)
    {
        if (isset(Request::$uri_get_param[$key]))
            return Request::$uri_get_param[$key];
        else
            return $default;
    }

    public static $dv_entity = "";
    public static $dv_class = "";

    public static function post($key)
    {
        if (isset(Request::$uri_post_param[$key]))
            return Request::$uri_post_param[$key];
        else
            return false;
    }

    public static function header($key, $default = null)
    {

        if (isset(Request::$uri_header_param[$key]))
            return Request::$uri_header_param[$key];
        else
            return $default;
    }

    public static function raw($key = null, $default = null, $format = "json")
    {
        $rawdata = file_get_contents("php://input");

        if ($format == "json") {
            self::$uri_raw_param = json_decode($rawdata, true);
            if ($key) {
                if (isset(Request::$uri_raw_param[$key]))
                    return Request::$uri_raw_param[$key];
                else
                    return $default;
            }
            return self::$uri_raw_param;
        } else
            return $rawdata;
    }

    public static function set($key, $value)
    {
        Request::$uri_get_param[$key] = $value;
    }

    public static function geturi()
    {
        //        $uri = $_SERVER["REQUEST_URI"];
        //        if (__prod)
        //            return substr($uri, -strlen($uri) + 1);

        //        return $_SERVER;
        $uri = explode("?", $_SERVER['REQUEST_URI'])[0];
        return str_replace("api//", "api/", $uri);
        //        return str_replace(__base_dir, "", $uri);
    }

    public static function getpath()
    {
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('\\', '/', $baseDir); // for Windows

        $requestUri = self::geturi();
        $requestUri = str_replace("api//", "api/", $requestUri);

        // Remove baseDir from requestUri
        if ($baseDir && strpos($requestUri, $baseDir) === 0) {
            $requestUri = substr($requestUri, strlen($baseDir));
        }

        // Remove '/api' prefix if present
        if (strpos($requestUri, '/api') === 0 || strpos($requestUri, 'api') === 0) {
            return substr($requestUri, strlen('api'));
        }

        return $requestUri;
    }

    public static function niceFunction($name, $separator = '_')
    {
        $array = explode($separator, $name);
        $function = "";
        foreach ($array as $i => $item) {
            if ($i >= 1)
                $function .= ucfirst($item);
            else
                $function .= ($item);
        }
        return $function;
    }

    public static function Route($app, $path)
    {

        if (!Dvups_lang::where("iso_code", Request::get("lang"))->count()) {
            //die(var_dump(__env.local().'/'.(Request::get("lang"))));
            $env = str_replace(__server, "", __env);
            $url = str_replace($env, __env . __lang . '/', self::$uri);
            //die(var_dump(self::$uri, $url));
            redirect($url);
        }

        //$array = explode("-", $path);
        $function = self::niceFunction($path, '-');
        $function .= "View";

        if (!method_exists($app, $function)) {
            var_dump(" You may create method " . " " . $function . " in entity. ");
            die;
        }
        $paramvalues = self::getMethodParamValues("App", $function);
        if ($paramvalues)
            //$app->{$function}(...$paramvalues);
            call_user_func_array(array($app, $function), $paramvalues);
        //Genesis::json_encode(call_user_func_array(array($app, $function), $paramvalues));
        else
            $app->{$function}();
        //call_user_func(array($app, $function));
    }

    /**
     * @throws Exception
     */
    public static function Module(\Router $route, $path, $ctrl = null)
    {

        $function = ($path) . "View";
        //        $function = \DClass\lib\Util::toPascalCase($path)."View";

        // Module<X> extends ModuleController extends Router,
        // dont le constructeur attend un $default_url (string) - jamais le $route
        // (objet Router/Admin) de cette fonction, malgre le nom de parametre
        // identique dans Module<X>::__construct($route). 'layout' est la valeur
        // attendue par les modules. Sans danger pour les constructeurs a 0
        // argument : PHP ignore un argument positionnel surnumeraire.
        $app = new $ctrl('layout');

        if (!method_exists($app, $function)) {
            var_dump(" You may create method " . " " . $function . " in $ctrl. ");
            die;
        }

        $paramvalues = self::getMethodParamValues(get_class($app), $function);

        // un ecran dont le code n'a jamais ete
        // genere (classe absente, referencee plus loin dans la methode ou
        // dans un template rendu) ne doit pas fuir de fatale : voir
        // Controller() plus bas, meme mecanisme, meme message. Seule une
        // erreur "Class ... not found" est degradee - un vrai bug (methode
        // existante mais cassee, mauvais type d'argument...) continue de
        // planter bruyamment, le message serait un mensonge sinon.
        try {
            if ($paramvalues) {

                Router::cacheRoute($route->url, $ctrl . '@' . $function, array_keys($paramvalues), Router::$route_builder['auth']);

                //$app->{$function}(...$paramvalues);
                return call_user_func_array(array($app, $function), $paramvalues);
                //Genesis::json_encode(call_user_func_array(array($app, $function), $paramvalues));
            } else {

                //            Router::cacheRoute($route->url, $ctrl . '@' . $function, [], Router::$route_builder['auth']);

                return $app->{$function}();
            }
            //call_user_func(array($app, $function));
        } catch (\Throwable $e) {
            if (self::isClassNotFoundError($e)) {
                return self::renderNotMonitored('', $ctrl);
            }
            throw $e;
        }
    }

    /**
     * une seule fois, appelee par Module() et
     * Controller() - voir leurs commentaires. true seulement pour "Class
     * "X" not found" ET quand rien de tres proche ne couvre deja le meme
     * nom. Jamais pour un TypeError (mauvais type d'argument), une methode
     * manquante sur une classe existante, ou tout autre defaut reel :
     * ceux-la restent des bugs a reparer, pas des ecrans "non generes".
     *
     * Attrape \Throwable, pas seulement \Error : certaines fatales (pendant
     * le rendu d'un template Blade compile) passent par le handler legacy de
     * Symfony\Component\Debug et arrivent en FatalThrowableError, qui
     * etend \Exception, pas \Error.
     *
     * Le filtre "classe tres proche" (class_exists($nom . 's')) ecarte les
     * erreurs de nommage singulier/pluriel ("Article" cite alors que l'entite
     * s'appelle "Articles") : c'est un bug a corriger, pas un ecran non genere.
     */
    private static function isClassNotFoundError(\Throwable $e)
    {
        if (!preg_match('/^Class "([^"]+)" not found/', $e->getMessage(), $m)) {
            return false;
        }
        if (class_exists($m[1] . 's')) {
            return false;
        }
        return true;
    }

    /**
     * l'ecran degrade reste un ecran du
     * back-office (layout + menu + fil d'Ariane, via admin.layout_module,
     * deja utilise partout ailleurs) - jamais une page blanche, un
     * var_dump ou une trace PHP. Aucun chemin serveur, aucune trace, aucun
     * nom de classe interne dans le texte affiche : $entityName/$moduleName
     * seulement, un administrateur non developpeur peut voir cet ecran.
     */
    private static function renderNotMonitored($entityName, $moduleClassOrName)
    {
        global $viewdir, $moduledata;

        $moduleLabel = '';
        if (is_array($moduledata) && isset($moduledata['label'])) {
            $moduleLabel = $moduledata['label'];
        } elseif (is_array($moduledata) && isset($moduledata['name'])) {
            $moduleLabel = $moduledata['name'];
        } elseif ($moduleClassOrName) {
            $moduleLabel = preg_replace('/^Module/', '', $moduleClassOrName);
        }

        if (!$entityName && $moduleClassOrName && preg_match('/Controller$/', $moduleClassOrName)) {
            $entityName = preg_replace('/Controller$/', '', $moduleClassOrName);
        }

        $blade = new \BladeD($viewdir, ROOT . "cache/views");
        echo $blade->view()->file(__DIR__ . '/../Resource/views/not_monitored.blade.php', [
            'moduledata' => $moduledata,
            'entityName' => $entityName,
            'moduleName' => $moduleLabel,
        ])->render();
        return 1;
    }

    /**
     * @throws Exception
     */
    public static function Controller(\Router $route, $path, $ctrl = null)
    {

        $function = \DClass\lib\Util::toPascalCase($path);
        //todo handle this for admin query to avoid overwrite in the frontController
        if (in_array($function, ["create", "update", "delete", "_delete", "deletegroup", "deleteGroup",]))
            $function .= "Action";
        else if (in_array($function, ["form", "detail", "list",]))
            $function .= "View";

        // le controleur lui-meme peut manquer
        // (Book_statsController, RelationController - jamais generes), pas
        // seulement une classe qu'il reference plus loin (KolaTable,
        // FollowTable, l'entite Notificationbroadcasted) : les deux sont le
        // meme cas "non genere" et recoivent le meme ecran degrade. Couvre
        // les trois chemins d'arrivee sur un ecran d'entite (liste, detail,
        // formulaire), qui passent tous par cette fonction unique.
        if (!class_exists($ctrl)) {
            return self::renderNotMonitored('', $ctrl);
        }

        $app = new $ctrl();

        if (!method_exists($app, $function)) {
            var_dump(" You may create method " . " " . $function . " in $ctrl. ");
            die;
        }

        $paramvalues = self::getMethodParamValues(get_class($app), $function);

        try {
            if ($paramvalues) {

                Router::cacheRoute($route->url, $ctrl . '@' . $function, array_keys($paramvalues), Router::$route_builder['auth']);

                //$app->{$function}(...$paramvalues);
                return call_user_func_array(array($app, $function), $paramvalues);
                //Genesis::json_encode(call_user_func_array(array($app, $function), $paramvalues));
            } else {

                Router::cacheRoute($route->url, $ctrl . '@' . $function, [], Router::$route_builder['auth']);

                return $app->{$function}();
            }
            //call_user_func(array($app, $function));
        } catch (\Throwable $e) {
            if (self::isClassNotFoundError($e)) {
                return self::renderNotMonitored('', $ctrl);
            }
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public static function FrontController(\Router $route, $path, $ctrl = null)
    {
        global $global_config;
        //$array = explode("-", $path);
        $function = self::niceFunction($path, '-');

        $app = new $ctrl();
        /**
         * restriction are used in the webservice controller to handle @Auth in system crud
         */

        $reflection = new ReflectionAnnotatedClass($ctrl);
        $ctrlApi = $reflection->getAnnotation('Api'); //

        if ("/" . Request::$uri_get_param["dclass"] !== $ctrlApi->name) {
            /*echo json_encode([
                "success" => false,
                "message" => "entity " . $classname . " not found",
                "available" => $public_access ?
                    \Dvups_entity::whereIn("this.name", $public_access)->get()
                    : \Dvups_entity::all(),
            ]);
            die;*/
            header('HTTP/1.0 500 Server error');
            throw new Exception("Controller name " . $ctrlApi->name . " no match with entity name in \$global_config", 22);
        }

        if (!method_exists($app, $function)) {

            // Try to resolve the method by matching HTTP-method annotations (e.g. @GET(path='/:id/comments'))
            // against the full request URI. This handles sub-resource paths beyond /:id.
            $matched = null;
            $requestUri = str_replace('api/', '', Router::$path_info);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflMethod) {
                if (!$reflMethod->hasAnnotation($route->request_method)) continue;
                $ann = $reflMethod->getAnnotation($route->request_method);
                if (empty($ann->path)) continue;
                /*
                 * La barre oblique initiale est OBLIGATOIRE : `$ctrlApi->name`
                 * vaut `/share` et le chemin de l'annotation `/og`, donc la
                 * concatenation directe donnait `\share/og` (barre oblique
                 * INVERSEE) la ou l'URI vaut `/share/og`. Aucune annotation de
                 * sous-ressource ne pouvait correspondre, d'ou des 404 sans
                 * explication. Constate le 2026-10-06 sur `GET /api/share/og`.
                 */
                $pattern = rtrim($ctrlApi->name, '/') . '/' . ltrim($ann->path, '/');
                $parsed = Route::parseRoute($pattern, $requestUri);
                if ($parsed !== null) {
                    $matched = $reflMethod->name;
                    break;
                }
            }

            if ($matched !== null) {
                $function = $matched;
            } else {
                $path = explode("/", trim(Router::$path, '/'));

                if ((count($path) === 2)) {
                    if (in_array($route->request_method, ['PUT', 'DELETE']))
                        Router::$path = Router::$default_path[$route->request_method];
                    elseif ($route->request_method == 'GET')
                        Router::$path = 'detail';
                    else {

                        http_response_code(404);
                        throw new Exception("Request method {$route->request_method} not found with 2 paths");
                    }
                    Request::$uri_get_param["path"] = Router::$path;
                } else if (count($path) === 1) {
                    Router::$path = Router::$default_path[$route->request_method];
                    Request::$uri_get_param["path"] = Router::$path;
                } else {

                    http_response_code(404);
                    throw new Exception("Request method {$route->request_method} not allowed for this route");
                }
                $function = Router::$path;
            }
        }

        /**
         * Controle d'habilitation : verifie Auth::$restrictions contre le nom
         * de methode REELLEMENT resolu ci-dessus.
         *
         * Corrige le 2026-10-01 : ce controle etait place plus haut dans la
         * methode, alors que le bloc de resolution par annotation (juste
         * au-dessus) reaffecte $function APRES lui. Sur une route REST dediee
         * resolue par annotation, il comparait donc l'ANCIENNE valeur de
         * $function - le chemin par defaut de la methode HTTP (`index` par
         * defaut pour un GET) - et non la methode qui allait etre appelee.
         *
         * Note d'honnetete : sur l'etat actuel du code, cette branche de
         * resolution par annotation n'a pas pu etre declenchee par un appel
         * reel lors des tests du 2026-10-01 - les routes essayees tombent
         * toutes sur le chemin par defaut, et `POST /api/follow` echoue plus
         * loin en "class FollowFrontController doesn't exist". Le defaut
         * corrige est donc reel dans la logique mais non demontre exploitable
         * en l'etat : la correction est preventive. Le defaut REELLEMENT
         * exploitable, lui, etait ailleurs et il est corrige aussi : le style
         * generique (/api/create|update|delete|upload/<entite>) executait
         * l'ecriture malgre le refus, faute de `die` dans
         * WebService::systemServe() - verifie en direct.
         *
         * $function est ici garantie non vide : utilisee juste apres par
         * $reflection->getMethod(). Le `header` a ete remplace par
         * http_response_code() : un header('HTTP/1.0 401 ...') pose par
         * Auth::authorize() ecrasait le code de l'appelant - meme correction
         * que dans Auth.php.
         */
        if (isset(Auth::$restrictions[Request::$uri_get_param["dclass"]])
            && !in_array($function, Auth::$restrictions[Request::$uri_get_param["dclass"]])
            && !Auth::$user_id) {
            http_response_code(401);
            throw new Exception("Bad Request AUTHORIZATION or AUTH not found");
        }

        $method = $reflection->getMethod($function); //
        $mparams = $method->getParameters();

        $methoparams = [];

        if ($method->hasAnnotation('Auth')) {
            $Auth = $method->getAnnotation('Auth');
            $result = $Auth->execute();
            if (is_array($result) && $result['success'] == false) {
                throw new Exception($result['detail']);
            }

            Router::$route_builder['auth'] = true;
            Request::$uri_get_param['session_user_id'] = $result->userId;
        }
        if ($method->hasAnnotation($route->request_method)) {
            $methodView = $method->getAnnotation($route->request_method);
        } elseif ($method->hasAnnotation('Api')) {
            $methodView = $method->getAnnotation('Api');
        } else {

            http_response_code(405);
            throw new Exception("Request method {$route->request_method} not allowed for this route");
        }

        if ($methodView) {
            $methodView->name = $ctrlApi->name;

            try {
                $url = $methodView->execute($route);
            } catch (Exception $e) {
                throw $e;
            }

            foreach ($mparams as $i => $param) {
                if (isset(Request::$uri_get_route_param[$param->name]))
                    $methoparams[$param->name] = Request::$uri_get_route_param[$param->name]; // Request::get($param->name)
            }
        }

        if (!method_exists($app, $function)) {
            var_dump(" You may create method " . " " . $function . " in $ctrl. ");
            die;
        }

        $paramvalues = self::getMethodParamValues(get_class($app), $function);

        if ($paramvalues) {

            Router::cacheRoute($methodView->name ?? $route->url, $ctrl . '@' . $function, array_keys($paramvalues), Router::$route_builder['auth']);

            return call_user_func_array(array($app, $function), $paramvalues);
        } else {

            Router::cacheRoute($route->url, $ctrl . '@' . $function, [], Router::$route_builder['auth']);

            return $app->{$function}();
        }
    }

    public static function getMethodParamValues($class, $method)
    {

        $reflexion = new ReflectionMethod($class, $method);
        $funparams = $reflexion->getParameters();
        if (!$funparams)
            return [];

        $paramvalues = [];
        foreach ($funparams as $param) {
            $paramvalues[$param->name] = Request::get($param->name);
        }
        return $paramvalues;
    }

    public static function service($path)
    {
        $params = explode(".", $path);
        self::$dv_entity = str_replace("-", "_", $params[0]);
        self::$dv_class = ucfirst(self::$dv_entity);
        $ctrl = self::$dv_class . "Controller";
        $app = new $ctrl;

        $array = explode("-", $params[1]);
        $function = "";
        foreach ($array as $i => $item) {
            if ($i >= 1)
                $function .= ucfirst($item);
            else
                $function .= ($item);
        }
        $function .= "Action";

        if (!method_exists($app, $function)) {
            Genesis::json_encode(['success' => false, 'error' => ['message' => "404 : action note found", 'route' => $path]]);
        }
        $paramvalues = self::getMethodParamValues($ctrl, $function);
        if ($paramvalues)
            //$app->{$function}($paramvalues);
            Genesis::json_encode(call_user_func_array(array($app, $function), $paramvalues));
        else
            $app->{$function}();
        //Genesis::json_encode(call_user_func(array($app, $function)));

    }

    public $_data = [];
    public $_response = [];
    public $_header = [];
    public $url = "";
    public $_method = "GET";
    public $_log = false;

    public static function initCurl($url, $_data = [])
    {
        $request = new Request(NULL);
        $request->url = $url;
        $request->_data = $_data;
        if ($_data)
            $request->_method = "POST";
        return $request;
    }

    public function data($post)
    {
        $this->_data = $post;
        $this->_method = "POST";
        return $this;
    }

    public function addHeader($key, $value)
    {
        $this->_header[] = "$key: $value";
        return $this;
    }

    public function raw_data($post)
    {
        $this->_data = json_encode($post);
        $this->_method = "POST";
        $this->_header[] = "Content-Type: application/json";
        return $this;
    }

    public function parameters($data)
    {
        $this->_data = http_build_query($data);
        $this->_method = "GET";
        return $this;
    }

    public function send()
    {
        $curl = curl_init();

        $data = array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_FAILONERROR => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $this->_method
        );
        if ($this->_data && $this->_method == 'POST')
            $data[CURLOPT_POSTFIELDS] = $this->_data;

        if ($this->_header)
            $data[CURLOPT_HTTPHEADER] = $this->_header;

        curl_setopt_array($curl, $data);

        $this->_response = curl_exec($curl);

        /*if (curl_errno($curl)) {
            $this->_response = curl_error($curl);
            die(var_dump($this->_data, $this->url, $this->_header, $this->_response));
        }*/

        curl_close($curl);

        //if($this->_log)
        \DClass\lib\Util::log($this->_response, "curl_log.text");

        //dv_dump($this->url, $this->_method, $this->_data, $this->_response);
        return $this;
    }

    /**
     * @return \stdClass
     */
    public function json()
    {
        return json_decode($this->_response);
    }
}
