<?php

//namespace dclass\lib\annotation;

class GET extends \Api
{

    public function execute(\Router &$route)
    {

        $routeParams = Route::parseRoute($this->name . $this->path, Request::getpath());
        unset($routeParams['path']);

//        dv_dump($routeParams,$this->name . $this->path, str_replace('/api','',Request::geturi()));
        if ($routeParams === null) {
            http_response_code(404);
            throw new Exception("Route method {$this->path} not found");
        }
        Request::$uri_get_param += $routeParams;
        Request::$uri_get_route_param = $routeParams;

    }

}