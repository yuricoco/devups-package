<?php
            //ModuleUser

        require '../../../admin/header.php';
        global $app;
if (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], '/api/') === 0) {

    header("Access-Control-Allow-Origin: *");
//header('Content-Type: application/json');

    ($app = new ModuleUser('hello'))->manageServe();
}else
    ($app = new ModuleUser('layout'))->manage();
die;
