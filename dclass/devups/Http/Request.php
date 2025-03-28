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

        $query[1] = self::strReplaceAssoc([
            "%3C" => "<",
            "%20" => " ",
            "%3A" => ":",
            "+d" => " d",
            "+a" => " a",
            "+D" => " D",
            "+A" => " A",
        ], $query[1]);

        $param = explode('&', $query[1]);
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
        return str_replace('/api', '', Request::geturi());
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
    public static function Controller(\Router $route, $path, $ctrl = null)
    {

        $function = \DClass\lib\Util::toPascalCase($path);
        //todo handle this for admin query to avoid overwrite in the frontController
        if (in_array($function, ["create", "update", "delete", "_delete", "deletegroup", "deleteGroup",]))
            $function .= "Action";
        else if (in_array($function, ["form", "detail", "list",]))
            $function .= "View";

        $app = new $ctrl();

        if (!method_exists($app, $function)) {
            var_dump(" You may create method " . " " . $function . " in $ctrl. ");
            die;
        }

        $paramvalues = self::getMethodParamValues(get_class($app), $function);

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

        if (isset(Auth::$restrictions[Request::$uri_get_param["dclass"]])) {
            if (!in_array($function, Auth::$restrictions[Request::$uri_get_param["dclass"]])) {
//                $Auth = new Auth();
//                $result = $Auth->authorize();
                if (!Auth::$user_id) {
//                if (is_array($result) && $result['success'] == false) {
                    header('HTTP/1.0 400 Bad Request AUTHORIZATION not found');
                    $result = ([
                        'success' => false,
                        'detail' => "Bad Request AUTHORIZATION or AUTH not found",
                    ]);
                    throw new Exception($result);
//                    throw new Exception($result['detail']);
                }
//                Router::$route_builder['auth'] = true;
//                Request::$uri_get_param['user_id'] = $result->userId;

            } /*else if ($ctrl) {
                $Auth = new Auth();
                $result = $Auth->execute();
                if (is_object($result)) {
                    Router::$route_builder['auth'] = true;
                    Request::$uri_get_param['user_id'] = $result->userId;
                }
            }*/
        } /*else
            dv_dump($function, Auth::$restrictions[Request::$uri_get_param["dclass"]]);*/

        $reflection = new ReflectionAnnotatedClass($ctrl);
        $ctrlApi = $reflection->getAnnotation('Api');//

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

                /*Router::$path = $path[1];
                Request::$uri_get_param["path"] = $path[1];*/
            }
            $function = Router::$path;
        }
//        dv_dump($path, $function);
        $method = $reflection->getMethod($function);//
        $mparams = $method->getParameters();

        $methoparams = [];

        if ($method->hasAnnotation('Auth')) {
            $Auth = $method->getAnnotation('Auth');
            $result = $Auth->execute();
            if (is_array($result) && $result['success'] == false) {
                throw new Exception($result['detail']);
            }

            Router::$route_builder['auth'] = true;
            Request::$uri_get_param['user_id'] = $result->userId;
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
