<?php

//namespace dclass\lib\annotation;

class PUT extends \Api
{
    public $path = '/:id';


    public function execute(\Router &$route)
    {

        $routeParams = Route::parseRoute($this->name . $this->path, str_replace('/api','',Request::geturi()));
        unset($routeParams['path']);

        if ($routeParams === null) {
            http_response_code(404);
            throw new Exception("Route method {$this->path} not found");
        }
//        dv_dump($routeParams,$this->name . $this->path, str_replace('/api','',Request::geturi()));
        Request::$uri_get_param += $routeParams;
        Request::$uri_get_route_param = $routeParams;

    }

}