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

// Les points d'entree historiques src/<composant>/<Module>/{index,services}.php
// incluent ce fichier directement, sans passer par Router::manage() : ils
// doivent exiger une session d'administration ici, sinon n'importe quel
// visiteur anonyme pourrait creer, modifier ou supprimer des lignes.
// - services.php : toujours gardee, quel que soit le composant (c'est le
//   point d'entree CRUD admin du module, meme pour un module front comme
//   devupsfront/ModuleUser dont le services.php gere les utilisateurs cote
//   back-office).
// - index.php : gardee uniquement pour les composants back-office (devups,
//   devupscms). Le composant public devupsfront expose son index.php sans
//   session admin, cote /api/ (inscription, connexion, OAuth), protege par
//   sa propre authentification (@Auth(authorized=1) par methode, JWT).
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$scriptBasename = basename($scriptFile);
$requiresAdminSession = $scriptBasename === 'services.php'
    || ($scriptBasename === 'index.php' && preg_match('#/src/(devups|devupscms)/#', $scriptFile));
if (PHP_SAPI !== 'cli' && $requiresAdminSession && empty($_SESSION[ADMIN])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'detail' => 'authentication required']);
    exit;
}

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

