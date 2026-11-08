<?php

session_start();

global $_start;
$_start = microtime(true);

const __cssversion = '1';
const __jsversion = '1';

require __DIR__ . '/config/dependanceInjection.php';
require __DIR__ . '/lang.php';
require 'src/requires.php';
Log::installGlobalHandler();
require 'route/WebService.php';
require 'route/App.php';
//require 'tests/ProductTest.php';

const assets = __env . 'web/assets/';
const webapp = __env . 'web/app/';
const __env_lang = 'front/';


global $viewdir, $dvlangs, $dlangs, $global_config, $pdocache;
$pdocache = [];
$dvlangs = [];
// E.7 : liste des langues actives mise en cache (cache/lang/active.php),
// plutot qu'une requete Dvups_lang::all() a chaque requete - voir
// Dvups_lang::cachedActiveDlangs()/invalidateActiveCache().
$dlangs = Dvups_lang::cachedActiveDlangs();
$viewdir = [web_dir . "views"];
$global_config = require ROOT.'config/dvups_configurations.php';