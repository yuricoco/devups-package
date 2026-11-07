<?php
            //ModuleConfig
		
        require '../../../admin/header.php';
        
header("Access-Control-Allow-Origin: *");
//header('Content-Type: application/json');

global $app;
($app = new ModuleConfig('hello'))->manageServe();
die;

