<?php
//ModuleService

require '../../../admin/header.php';
global $app;
if (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], '/api/') === 0) {

    header("Access-Control-Allow-Origin: *");
//header('Content-Type: application/json');

    ($app = new \devupscms\ModuleNotification\ModuleNotification('hello'))->manageServe(" ");
}else
    ($app = new \devupscms\ModuleNotification\ModuleNotification('layout'))->manage();
die;


require '../../../admin/header.php';

// move comment scope to enable authentication
if (!isset($_SESSION[ADMIN]) and $_GET['path'] != 'connexion') {
    header("location: " . __env . 'admin/login.php');
}

global $viewdir, $moduledata;
$viewdir[] = __DIR__ . '/Resource/views';

$moduledata = Dvups_module::init('ModuleNotification');

$notificationCtrl = new NotificationController();
$notificationtypeCtrl = new NotificationtypeController();


(new Request('layout'));

switch (Request::get('path')) {

    case 'layout':
        break;

    case 'emailmodel/preview':
        $emailmodel = Reportingmodel::find(Request::get("id"));
        echo $emailmodel->getPreview();
        break;
    case 'emailmodel/pdf':
        $emailmodel = Reportingmodel::find(Request::get("id"));
        $mpdf = new \Mpdf\Mpdf([
            "margin_left" => 0,
            "margin_right" => 0,
            "margin_top" => 0,
            "margin_bottom" => 0,
        ]);

// Write some HTML code:
        $mpdf->WriteHTML($emailmodel->getPreview());

// Output a PDF file directly to the browser
        $mpdf->Output();
        // echo $emailmodel->getPreview();
        break;

    case 'notification/index':
        $notificationCtrl->listView();
        break;

    case 'notificationtype/index':
        $notificationtypeCtrl->listView();
        break;


    default:
        Genesis::renderView('404', ['page' => Request::get('path')]);
        break;
}
    
    