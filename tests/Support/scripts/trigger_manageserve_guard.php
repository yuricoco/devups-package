<?php

/**
 * Script autonome (lance dans son propre processus PHP par
 * ManageServeSessionGuardTest) pour verifier le garde de session de
 * Router::manageServe() SANS passer par admin/header.php (qui garde deja
 * services.php/index.php par le nom du script - voir AdminSecurityTest) :
 * isole la protection au niveau du Router lui-meme, celle qui protege tout
 * appelant de manageServe() independamment du script qui l'inclut.
 *
 * Usage : php trigger_manageserve_guard.php <path_info> <with_session:0|1>
 */

require __DIR__ . '/../../../config/dependanceInjection.php';
require __DIR__ . '/../../../lang.php';
require __DIR__ . '/../../../src/requires.php';

$pathInfo = $argv[1] ?? '/api/hello';
$withSession = ($argv[2] ?? '0') === '1';

$_SERVER['PATH_INFO'] = $pathInfo;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $pathInfo;

if ($withSession)
    $_SESSION[ADMIN] = ['id' => 1];

Router::getPathInfo();

(new ModuleConfig('hello'))->manageServe('');
