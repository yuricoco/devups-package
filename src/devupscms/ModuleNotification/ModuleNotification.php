<?php


//namespace devupscms\ModuleNotification;

use dclass\devups\Controller\ModuleController;
use Genesis as g;
//use Notification;
//use NotificationTable;
//use Notificationtype;
//use NotificationtypeTable;
use Request as R;
//use Dvups_module;
//use Genesis;
//use Request;
//use Tree_item;
//use Tree_item_imageController;
//use Tree_itemFrontController;

class ModuleNotification extends ModuleController
{

    public function __construct($route = 'dashbaord')
    {
        parent::__construct($route);
    }

    public function dashboardView()
    {
        \dclass\devups\Controller\Controller::$jsfiles[] = Notification::classpath("Resource/js/notificationCtrl.js");
        $notificationtable = NotificationTable::init(new Notification());
        $notificationttypeable = NotificationtypeTable::init(new Notificationtype());
        Genesis::renderView("admin.overview",
            compact("notificationtable", "notificationttypeable"));

    }

    public function helloService()
    {
        Genesis::json_encode(['success'=>true]);
    }

    public function web()
    {

        $this->moduledata = Dvups_module::init('ModuleData');

        (new Request('layout'));

        switch (Request::get('path')) {

            case 'layout':
                Genesis::renderView("overview");
                break;

            default:
                Genesis::renderView('404', ['page' => Request::get('path')]);
                break;
        }
    }

    public function services()
    {

        (new Request('hello'));

        switch (R::get('path')) {

            default:
                g::json_encode(['success' => false, 'error' => ['message' => "404 : action note found", 'route' => R::get('path')]]);
                break;
        }
    }

    public function webservices()
    {

        (new Request('hello'));

        switch (R::get('path')) {

            case 'notified':
                g::json_encode((new \NotificationController())->notifiedAction());
                break;
            case 'notificationbroadcasted.alert':
                g::json_encode((new \NotificationController())->alertAction());
                break;
            case 'notificationbroadcasted.alertuser':
                g::json_encode((new \NotificationController())->alertAction("user"));
                break;

        }
    }

}