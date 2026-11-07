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

        /*$calledclass = get_called_class();
        $reflector = new ReflectionClass($calledclass);

        self::initRenderer($reflector);*/
        $this->ctrl = new Controller();
    }

    // sidebar.blade.php:63 genere admin/<projet>/<module>/dashboard
    // pour TOUS les modules, mais seuls ModuleTree et ModuleNotification exposent
    // reellement dashboardView() - les 16 autres Module<X>.php (sur 19 avec fichier)
    // exposent layoutView(). Request::Module() cherche "<segment>View" et meurt
    // proprement (var_dump+die) si la methode manque - mais comme dashboardView()
    // est desormais heritee par les 29 modules, ce garde-fou ne se declenche plus
    // pour ce cas precis : le repli doit donc vivre ici. Les deux modules qui
    // definissent leur propre dashboardView() l'emportent naturellement (regle de
    // resolution de methode PHP usuelle, rien a faire de special).
    public function dashboardView()
    {
        if (method_exists($this, 'layoutView')) {
            $this->layoutView();
        } else {
            var_dump(" You may create method " . " layoutView in " . get_class($this) . ". ");
            die;
        }
    }

    /*public abstract function web();
    public abstract function services();
    public abstract function webservices();*/

    public static function initRenderer($reflector)
    {
        global $viewdir, $moduledata;

//        $cn = $reflector->getName();
//        $ns = $reflector->getNamespaceName();
//        $cn = str_replace($reflector->getNamespaceName()."\\", "", $reflector->getName());
//        $fn = str_replace("$cn.php", "", $reflector->getFileName());
//        $fdir = str_replace("\\", "/", $fn);
        // dv_dump($fn,$reflector->getName(), $reflector->getNamespaceName(), str_replace($ns, "", $cn));
        /*$viewdir[] = $fdir . '/Resource/views';
        $ms = require ROOT.'config/module_configurations.php';
        $moduledata = $ms[$entity['module']];
        $moduledata = \Dvups_module::init($cn);*/

    }
    public static function renderView($view, $data = [], $redirect = false)
    {

//        $reflector = new ReflectionClass(get_called_class());
//        self::initRenderer($reflector);
        \Genesis::renderView($view, $data, $redirect);

    }
}