<?php

//namespace dclass\lib\annotation;

// ETAPE 3.1 (v8.0) : @GET(...) -> #[GET(...)]. Herite du constructeur
// d'Api (voir Api.php) - rien a redeclarer ici.
#[\Attribute(\Attribute::TARGET_METHOD)]
class GET extends \Api
{

    public function execute(\Router &$route)
    {

        $routeParams = Route::parseRoute($this->name . $this->path, str_replace('api/','',Router::$path_info));
        unset($routeParams['path']);

    //    dv_dump($routeParams,$this->name . $this->path, str_replace('/api','',Request::geturi()));
        if ($routeParams === null) {
            http_response_code(404);
            throw new Exception("Route method {$this->path} not found");
        }
        Request::$uri_get_param += $routeParams ?? [];
        Request::$uri_get_route_param = $routeParams ?? [];

    }

}