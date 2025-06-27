<?php

/* header 1.3
 *
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

global $_start;
$_start = microtime(true);

session_start();


//require __DIR__ . '/../config/constante.php';
require __DIR__ . '/../config/dependanceInjection.php';
require __DIR__ . '/../lang.php';
require __DIR__ . '/../src/requires.php';

Request::$system = "admin";

define('VENDOR', __env . 'admin/vendors/');
define('assets', __env . 'admin/assets/');

define('__cssversion', '1');
define('__jsversion', '1');
define('__env_lang', 'admin/');

Dvups_adminController::restartsessionAction();

global $global_navigation, $viewdir, $global_config;

$viewdir = [admin_dir . "views"];
$dvups_navigation = [];
$global_config = require ROOT.'config/dvups_configurations.php';
if (isset($_SESSION[__project_id . "_navigation"])) {
//    $dvups_navigation = unserialize($_SESSION[__project_id . "_navigation"]);

    if (isset($_GET["notified"]) && $idnb = $_GET["notified"]) {
        Notification::readed($idnb);
    }
}
//$global_navigation = Core::buildOriginCore();

