<?php

class MethodView extends Annotation
{

    public $path;
    public $route;
    public $methods;
    public $name;
    public $Auth;

    public $GET;
    public $POST;
    public $DELETE;
    public $PUT;
    public $UPDATE;

    public function execute( \Router &$route){


        $urlvalues = explode('/',  trim($route->url, '/'));
        // dv_dump($urlvalues);
        if ($route->request_method != 'GET') {
            if (in_array($route->request_method, ['POST', 'PUT', 'DELETE']))
                $path = $this->{$route->request_method};
            else
                throw new Exception("Request method {$route->request_method} unrecognized");

            if ($path == null)
                throw new Exception("Request method {$route->request_method} not allowed for this route");
        }else
            $path = $this->path ?? $this->GET;

        $paths = explode(",", $path);
        $routeParams = [];

        //dv_dump($methodView);

        $matched = false;
        if (count($paths) > 1) {
            foreach ($paths as $path) {
                $urlkeys = explode('/', str_replace(":", "", $path));

                if ($urlkeys && $urlvalues && count($urlvalues) == count($urlkeys)) {
                    $routeParams = array_combine($urlkeys, $urlvalues);
                    unset($routeParams[Router::$path]);
                    $matched = true;
                    break;
                }
            }
        } else {
            $urlkeys = explode('/', str_replace(":", "", $path));
            if ($urlkeys && $urlvalues && count($urlvalues) == count($urlkeys)) {
                $routeParams = array_combine($urlkeys, $urlvalues);
                unset($routeParams[Router::$path]);
                $matched = true;
            }
        }
        if (!$matched)
            throw new Exception(" MethodView or MethodServe not found for this url ");

        if (isset($routeParams['api']))
            unset($routeParams['api']);

        //dv_dump($routeParams);
        Request::$uri_get_param += $routeParams;
        Request::$uri_get_route_param = $routeParams;

        return $path;

    }

}