<?php

namespace route;
use AdminTemplateGenerator;
use Dvups_entityController;
use Genesis;
use Request;

class Admin extends \dclass\devups\Controller\ModuleController
{

    public function __construct($route)
    {
        parent::__construct($route);
    }

    public function dashboardView()
    {
        Genesis::render("dashboard", AdminTemplateGenerator::dashboardView());
    }
    public function configView()
    {
        Genesis::render("config", []);
    }

    /**
     * @MethodView(GET="hello", Auth=1)
     */
    public function helloService()
    {
        Genesis::json_encode(["success" => true, "message" => "hello devups you made your first apicall"]);
    }

    public function ExportView()
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