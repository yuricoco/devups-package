<?php

/* config environment
* DirectAdmin LOGIN INFORMATION:
 *
 * https://3ag-edition.com:2083/
 * login: agedngyh
* pwd: Q2yZPJXxrehuK4
*
 * app password bitbucket
* MHyw5ruN9MegtDBrsL7p
*
 * config environment
* info@3ag-edition.com pwd: =h^(!R~OOTsj
*
 * contact@3ag-edition.com
* pwd: AXlY5fS5k_%s
*
 *
 * no-reply@3ag-edition.com
* {9@H6sbPMClH
*
 * Informations de connexion
Utilisateur : xznwjmbs
Mot de passe : zGxXE7MBqhw9eZ
Connexion : https://mg.n0c.com
 *
 */

const PROJECT_NAME = "Devups";

const dbname = 'devups_bd';
const dbuser = 'root';
const dbpassword = '';
const dbhost = 'localhost';
const dbdumper = false;
const dbtransaction = true;

$ip_remote = @gethostbyname(getHostName());
$ipaddress = "127.0.0.1";

if (isset($_SERVER["SERVER_NAME"])) {
    $currentip = $_SERVER["SERVER_NAME"];

    if ($currentip == "127.0.0.1" && $ipaddress != "127.0.0.1") {

        $ipaddress = $ip_remote;
    }
}
// base url
// in production, replace by "/"
const __v = '4.3';

define('__ip', $ip_remote);
const __server = 'http://127.0.0.1';
const __env_port = ":3333"; // leave this empty in production
// const __env_port = "/devupstest"; // leave this empty in production
const __env = __server . __env_port . '/';
const __vendor_folder = __DIR__ . '/../vendor';
define("__front", __env . 'web/assets/');
const __admin = __env . 'admin/assets/';
const __prod = false;
const __route_cache = false;
const __cache_version = '1.0.0';
// define('__debug', false);
const __default_lang = "fr";
const __lang = 'fr';

/* config toolrad sync */
const __project_id = 'devupstuto' . __env_port;
const __toolrad_server = 'https://toolrad.spacekola.com/api/';
const __toolrad_api_key = 'apikey';


const ROOT = __DIR__ . '/../';
const CLASS_EXTEND = ROOT . "dclass/extends";
const UPLOAD_DIR = ROOT . '/../uploads/';
const admin_dir = ROOT . '/../admin/';
const web_dir = ROOT . '/../web/';

const SRC_FILE = __env . 'uploads/';
const CLASSJS = __env . 'dclass/devupsjs/';
const node_modules = __env . 'node_modules/';

const ENTITY = 0;
const VIEW = 1;

const ADMIN = __project_id . '_devups';
const CSRFTOKEN = __project_id . '_csrf_token';
const dv_role_navigation = __project_id . '_navigation';
const dv_role_permission = __project_id . '_permission';

/* NOTIFIACTION DEFINE */
const LANG = "lang";
const PREVIOUSPAGE = "previous_page";
const JSON_ENCODE_DEPTH = 512;
const USERAPP = '_app';


/* SMTP DEFINE */

const sm_port = 'xxx';
const sm_smtp = 'mail.spacekola.com';
const sm_username = 'no-reply@spacekola.com';
const sm_password = '***';
const sm_from = 'no-reply@spacekola.com';
const sm_name = PROJECT_NAME;
const sm_smtpsecurity = 'ssl';

/* JWT DEFINE */

const jwt_secret_Key = '68V0zWFrS72GbpPr...fj4v9m3Ti+DXc8OB0gcM=';
const jwt_expire_time = false; //'+72 hours'

/* FCM DEFINE */

const fcm_server_key = 'AAAAZfYoMKg:APA91bGg5SPh1X5u...YlzhUZRIz2W';
const fcm_pair_key = 'BG5ROP03CbHe_khfeFrR...GqR2jxs33VMDHaSs';
const fcm_user_id = '4379...5928';

const cron_token = 'da39a3ee5e6b4b0...fef95601890afd80709';


global $devups_cache;
$devups_cache = [];