<?php 

use dclass\devups\Controller\ModuleController; 

class ModuleAdmin extends ModuleController
{

    public function __construct($route)
    {
        parent::__construct($route);
    }

    public function layoutView()
    {
        Genesis::renderView("admin.overview");
    }

    public function helloService()
    {
        Genesis::json_encode(['success'=>true]);
    } 

}