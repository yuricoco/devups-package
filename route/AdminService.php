<?php

namespace route;
use AdminTemplateGenerator;
use dclass\devups\Controller\FrontController;
use Dvups_entityController;
use Genesis;
use Notificationtype;
use Request;
use Genesis as g;

class AdminService extends \dclass\devups\Controller\ModuleController
{

    /*
     * PoC
     * can it be possible to set an instance of the front controller then while instanciate it, making control of restrication,
     * either public access of global auth restrictions.
     */
    private $frontController;
    public function __construct($route)
    {
        parent::__construct($route);
        $this->frontController = new FrontController();
    }

    /**
     * @MethodView(GET="hello", Auth=1)
     */
    public function helloService()
    {
        Genesis::json_encode(["success" => true, "message" => "hello devups you made your first apicall"]);
    }

    public function systemServe()
    {

        FrontController::$entityname= self::$class_name;
        FrontController::$classMagic= self::$class_name;
        try {

            switch (self::$path) {
                case 'create':
                    g::json_encode($this->frontController->createCore());
                    break;
                case 'upload':
                    g::json_encode($this->frontController->uploadCore(Request::get("id")));
                    break;
                case 'update':
                    g::json_encode($this->frontController->updateCore(Request::get("id")));
                    break;
                case 'delete':
                    g::json_encode($this->frontController->deleteCore(Request::get("id")));
                    break;
                case 'detail':
                    g::json_encode($this->frontController->detailCore(Request::get("id")));
                    break;
                case 'lazyloading':
                    g::json_encode($this->frontController->ll());
                    break;
                case 'dcollection':
                    g::json_encode($this->frontController->dcollection());
                    break;

                default :
                    g::json_encode(["success" => false, "message" => "404 :" . Request::get('path') . " page note found"]);
                    break;
            }

        } catch (\Exception $e) {
            g::json_encode([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function clonerowService()
    {
        $classname = ucfirst(Request::get("dclass"));
        //$entity = new $classname;
        //$result = $entity->__dclone( ); //,
        $entity = $classname::find(Request::get("id"));
        $entity->id = null; //,
        $result = $entity->__insert(); //,
        $message = $classname . ": Cloned with success";
//            Genesis::json_encode([
//                "message"=>$message
//            ]);
        Genesis::json_encode(compact("message", "entity", "result"));
    }

    public function exportService()
    {
        $classname = ucfirst(Request::get("classname"));
        $entity = new $classname;
        $result = $entity->exportCsv($classname); //,
        $message = $classname . ": CSV generated with success";
//            Genesis::json_encode([
//                "message"=>$message
//            ]);
        Genesis::json_encode(compact("message", "entity", "result"));
    }

    public function importService()
    {
        $classname = ucfirst(Request::get("classname"));
        $entity = new $classname;
        $response = $entity->importCsv($classname); //,
        $message = $classname . ": Core generated with success";
        Genesis::json_encode(compact("response", "message"));
    }

    public function export()
    {
        (new Dvups_entityController())->exportCsv();
    }

    /**
     * @MethodView(path="dvups-admin/complete-registration")
     */
    public function completeRegistrationView($id)
    {
        \devups\ModuleAdmin\ModuleAdmin::renderView('admin.dvups_admin.complete_registration', (new \Dvups_adminController())->completeRegistration($id));
    }

    public function connexionView($id)
    {
        \devups\ModuleAdmin\ModuleAdmin::renderView('admin.dvups_admin.complete_registration', (new \Dvups_adminController())->completeRegistration($id));
    }

    public function generateConstantesService($entity_name)
    {
        $classname = ucfirst($entity_name);
        $rows = [];
        if ($entity_name == 'notification'){

            Notificationtype::select()->cursor(function (Notificationtype $item) use (&$rows){
                $rows[] = " const {$item->dvups_entity->name}_{$item->_key} = \"{$item->_key}\"; ";
            });

        }
        elseif ($entity_name == 'status'){

            \Status::select()->cursor(function (\Status $item) use (&$rows){
                $rows[] = " const {$item->entity->name}_{$item->_key} = \"{$item->_key}\"; ";
            });

        }
        elseif ($entity_name == 'role'){

            \Dvups_role::select()->cursor(function (\Dvups_role $item) use (&$rows){
                $rows[] = " const {$item->name} = \"{$item->name}\"; ";
            });

        }
        elseif ($entity_name == 'tree_item'){
            $tree = \Tree::find(Request::get('treename'));
            $treename = (Request::get('treename'));
            $classname = "Tree".ucfirst($treename);
            \Tree_item::mainmenu($treename)->cursor(function (\Tree_item $item) use (&$rows, $treename){
                $rows[] = " const {$item->slug} = \"{$item->slug}\"; ";
            });

        }

        if (!$rows)
            Genesis::json_encode( [
                "success" => false,
                "detail" => "",
            ]);

        $content = "<?php
            class {$classname}Const {
                ".implode("\n", $rows)."
            }
        ";
        \DClass\lib\Util::writein($content, $classname.'Const.php', 'cache/constants/', 'w');

        if (!file_exists(ROOT.'cache/constants/'))
            Genesis::json_encode( [
                "success" => false,
                "detail" => "",
            ]);

        if (file_exists(ROOT.'cache/constants/require_constants.php'))
            unlink(ROOT.'cache/constants/require_constants.php');

        $files = scandir(ROOT.'cache/constants/');
        unset($files[0]);
        unset($files[1]);

        $requires = "<?php\n ";
        foreach ($files as $file)
            $requires .= "require __DIR__ . '/$file';\n";

        \DClass\lib\Util::writein($requires, 'require_constants.php', 'cache/constants/', 'w');

        Genesis::json_encode([
            "success" => true,
            "detail" => "Constants generated with success",
        ]);

    }

    public function web($path = "")
    {
        // TODO: Implement web() method.
    }

    public function services($path = "")
    {
        // TODO: Implement services() method.
    }

    public function webservices($path = "")
    {
        // TODO: Implement webservices() method.
    }
}