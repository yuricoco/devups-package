<?php

//namespace dclass\lib\annotation;

class POST extends \Api
{

    public function execute(\Router &$route)
    {

//        dv_dump($this->name .$this->path, str_replace('/api','',Request::geturi()));
        $routeParams = Route::parseRoute($this->name . $this->path, str_replace('/api','',Request::geturi()));
        unset($routeParams['path']);

        if ($routeParams === null) {
            http_response_code(404);
            throw new Exception("Route method {$this->path} not found. Verify your request. either set /{$this->name} or add the path parameter explicitly within the annotation.");
        }
//        dv_dump($routeParams,$this->name . $this->path, str_replace('/api','',Request::geturi()));
        Request::$uri_get_param += $routeParams;
        Request::$uri_get_route_param = $routeParams;

    }

}