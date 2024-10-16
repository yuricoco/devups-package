<?php


namespace dclass\devups\Controller;


use ReflectionClass;
use Router;

// abstract
class ModuleController  extends Router
{
    public $moduledata;
    public $ctrl;

    public function __construct($default_url)
    {
        parent::__construct($default_url);

        $reflector = new ReflectionClass(get_called_class());
        self::initRenderer($reflector);
        $this->ctrl = new Controller();
    }

    /*public abstract function web();
    public abstract function services();
    public abstract function webservices();*/

    public static function initRenderer($reflector)
    {
        global $viewdir, $moduledata;

//        $cn = $reflector->getName();
//        $ns = $reflector->getNamespaceName();
        $cn = str_replace($reflector->getNamespaceName()."\\", "", $reflector->getName());
        $fn = str_replace("$cn.php", "", $reflector->getFileName());
        $fdir = str_replace("\\", "/", $fn);
        // dv_dump($fn,$reflector->getName(), $reflector->getNamespaceName(), str_replace($ns, "", $cn));
        $viewdir[] = $fdir . '/Resource/views';
        $moduledata = \Dvups_module::init($cn);

    }
    public static function renderView($view, $data = [], $redirect = false)
    {

        $reflector = new ReflectionClass(get_called_class());
        self::initRenderer($reflector);
        \Genesis::renderView($view, $data, $redirect);

    }
}