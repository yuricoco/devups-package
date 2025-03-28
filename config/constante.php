<?php

/* config environment
 *
 */

const PROJECT_NAME = "Devups";

const dbname = 'devups_bd';
const dbuser = 'root';
const dbpassword = '';
const dbhost = 'localhost';
const dbdumper = false;

/**
 * Allow the log of each write in the database
 * Those log file are located in database/transaction/ folder
 */
const dbtransaction = true;

// base url
// in production, replace by "/"
const __v = '4.3';

const __server = 'http://127.0.0.1';
const __env_port = ":8080"; // leave this empty in production
// const __env_port = "/devupstest"; // leave this empty in production
const __env = __server . __env_port . '/';
const __vendor_folder = __DIR__ . '/../vendor';
define("__front", __env . 'web/assets/');
const __admin = __env . 'admin/assets/';


const __csrf_origin = "http://localhost:5173,http://localhost:3000,https://domain.org";
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
const UPLOAD_DIR = __DIR__ . '/../uploads/';
const admin_dir = __DIR__ . '/../admin/';
const web_dir = __DIR__ . '/../web/';


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

/**
 * FCM DEFINE
 */

const fcm_pair_key  = 'BJTb**************NiW8EsD-XM0KTDS*****************************pQ5a8YzKQ';
const fcm_user_id  = 100103111111111111117864;
const fcm_project_id  = '*******-d18cb';
const fcm_jwt_auth_file  = '********-d18cb-firebase-adminsdk-fbsvc-b7f7877399.json';

// Google API configuration
const GOOGLE_CLIENT_ID = '3760****246-4fekan*********************41n25.apps.googleusercontent.com';
const GOOGLE_CLIENT_SECRET = 'G***PX-CTlHiuJW**********OLVrV2m6';
const GOOGLE_REDIRECT_URL = 'https://backend.com/auth/google';

const cron_token = 'da39a3ee5e6b4b0...fef95601890afd80709';


global $devups_cache;
$devups_cache = [];