<?php

session_start();

global $_start;
$_start = microtime(true);

const __cssversion = '1';
const __jsversion = '1';

require __DIR__ . '/config/dependanceInjection.php';
require __DIR__ . '/lang.php';
require 'src/requires.php';
require 'route/WebService.php';
require 'route/App.php';
//require 'tests/ProductTest.php';

const assets = __env . 'web/assets/';
const webapp = __env . 'web/app/';
const __env_lang = 'front/';


global $viewdir, $dvlangs, $dlangs, $global_config;
$dvlangs = Dvups_lang::all();
$viewdir = [web_dir . "views"];
$global_config = require ROOT.'config/dvups_configurations.php';

foreach($dvlangs as $lang){
    $dlangs[$lang->iso_code] = $lang->id;
    $dlangs[$lang->iso_code.'/'] = $lang->id;
    $dlangs[$lang->id] = $lang->iso_code;
}