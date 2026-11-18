<?php

//namespace dclass\lib\annotation;

// ETAPE 3.1 (v8.0) : @PUT(...) -> #[PUT(...)]. L'ancienne classe
// n'avait de constructeur que via Annotation::__construct() (ne touchait
// que les proprietes presentes dans le docblock) - "$path = '/:id'" etait
// donc le defaut effectif pour un #[PUT] sans path explicite (aucun usage
// reel de ce defaut dans le depot - verifie - mais preserve ici a
// l'identique).
#[\Attribute(\Attribute::TARGET_METHOD)]
class PUT extends \Api
{
    public function __construct(
        $name = null,
        $path = '/:id',
        $GET = null,
        $POST = null,
        $PUT = null,
        $DELETE = null,
        $UPDATE = null,
        $route = null,
        $methods = null,
        $description = null,
        $Auth = null
    ) {
        parent::__construct($name, $path, $GET, $POST, $PUT, $DELETE, $UPDATE, $route, $methods, $description, $Auth);
    }

    public function execute(\Router &$route)
    {

        $routeParams = Route::parseRoute($this->name . $this->path, str_replace('/api','',Router::$path_info));
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