<?php
            //ModuleUser

        require '../../../admin/header.php';

header("Access-Control-Allow-Origin: *");
//header('Content-Type: application/json');

global $app;
($app = new ModuleUser('hello'))->manageServe();
die;

