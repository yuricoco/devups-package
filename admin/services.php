<?php

global $_start;
$_start = microtime(true);

require __DIR__ . '/header.php';
require 'Admin.php';

use route\Admin;

header("Access-Control-Allow-Origin: *");
//header('Content-Type: application/json');

global $admin;
($admin = new Admin('hello'))->manageServe();
die;
