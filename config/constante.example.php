<?php

/**
 * Configuration de l'application.
 *
 * Installation : copier ce fichier en config/constante.php (ignore par git)
 * et le fichier .env.example en .env, puis renseigner .env. Les secrets ne
 * doivent JAMAIS etre ecrits en dur ici : ce fichier est versionne.
 */

require_once __DIR__ . '/env.php';

define('PROJECT_NAME', dv_env('APP_NAME', 'Devups'));

/**
 * Base de donnees
 */
define('dbname', dv_env('DB_NAME', 'devups'));
define('dbuser', dv_env('DB_USER', 'root'));
define('dbpassword', dv_env('DB_PASSWORD', ''));
define('dbhost', dv_env('DB_HOST', 'localhost'));
define('dbdumper', dv_env('DB_DUMPER', false));
define('dbtransaction', dv_env('DB_TRANSACTION', true));

const __v = '4.3';

/**
 * URLs. En production, laisser APP_PORT vide.
 */
define('__ip', "");
define('__server', dv_env('APP_URL', 'http://127.0.0.1'));
define('__env_port', dv_env('APP_PORT', ''));
const __env = __server . __env_port . '/';
define('__env_front', dv_env('FRONT_URL', 'http://localhost:5173/'));

// origines autorisees (CORS), separees par des virgules
define('__csrf_origin', dv_env('CORS_ORIGINS', 'http://localhost:5173,http://localhost:3000'));

const __vendor_folder = __DIR__ . '/../vendor';
define("__front", __env . 'web/assets/');
const __admin = __env . 'admin/assets/';
define('__prod', dv_env('APP_PROD', false));
// ETAPE E.4 (v7.1) : Router::runCache()/cacheRoute() sont du code mort
// quelle que soit cette valeur - voir le commentaire equivalent dans
// config/constante.php. Ne jamais mettre ROUTE_CACHE=1 en attendant un
// effet : aucun n'existe actuellement.
define('__route_cache', dv_env('ROUTE_CACHE', false));
define('__cache_version', dv_env('CACHE_VERSION', '1.0.0'));
define('__default_lang', dv_env('DEFAULT_LANG', 'fr'));
define('__lang', dv_env('DEFAULT_LANG', 'fr'));

/**
 * Synchronisation toolrad (optionnelle)
 */
define('__project_id', dv_env('PROJECT_ID', 'devups') . __env_port);
define('__toolrad_server', dv_env('TOOLRAD_SERVER', 'https://toolrad.spacekola.com/api/'));
define('__toolrad_api_key', dv_env('TOOLRAD_API_KEY', ''));

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

const LANG = "lang";
const PREVIOUSPAGE = "previous_page";
const JSON_ENCODE_DEPTH = 512;
const USERAPP = '_app';

/**
 * SMTP
 */
define('sm_port', dv_env('MAIL_PORT', '465'));
define('sm_smtp', dv_env('MAIL_HOST', ''));
define('sm_username', dv_env('MAIL_USERNAME', ''));
define('sm_password', dv_env('MAIL_PASSWORD', ''));
define('sm_from', dv_env('MAIL_FROM', 'no-reply@example.com'));
define('sm_name', dv_env('MAIL_FROM_NAME', PROJECT_NAME));
define('sm_smtpsecurity', dv_env('MAIL_ENCRYPTION', 'ssl'));

/**
 * JWT
 */
define('jwt_secret_Key', dv_env('JWT_SECRET', ''));
define('jwt_expire_time', dv_env('JWT_EXPIRE', false)); // ex. '+72 hours'

/**
 * FCM (notifications push)
 */
define('fcm_pair_key', dv_env('FCM_PAIR_KEY', ''));
define('fcm_user_id', dv_env('FCM_USER_ID', ''));
define('fcm_project_id', dv_env('FCM_PROJECT_ID', ''));
define('fcm_jwt_auth_file', dv_env('FCM_AUTH_FILE', ''));

define('cron_token', dv_env('CRON_TOKEN', ''));

/**
 * Google OAuth (optionnel)
 */
define('GOOGLE_CLIENT_ID', dv_env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_ID_WEB', dv_env('GOOGLE_CLIENT_ID_WEB', ''));
define('GOOGLE_CLIENT_ID_ANDROID', dv_env('GOOGLE_CLIENT_ID_ANDROID', ''));
define('GOOGLE_CLIENT_ID_IOS', dv_env('GOOGLE_CLIENT_ID_IOS', ''));
define('GOOGLE_CLIENT_SECRET', dv_env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URL', dv_env('GOOGLE_REDIRECT_URL', ''));

/**
 * Journalisation (dclass/devups/Log.php)
 */
define('LOG_MIN_LEVEL', dv_env('LOG_MIN_LEVEL', 'warning'));
define('LOG_RETENTION_DAYS', dv_env('LOG_RETENTION_DAYS', 90));

/**
 * ETAPE L.1 : expiration des codes a 6 chiffres (activation, changement de
 * telephone, reinitialisation de mot de passe) et limiteur de frequence
 * generique (dclass/devups/RateLimiter.php) sur la connexion et la
 * verification/le renvoi de ces codes.
 */
define('ACTIVATION_CODE_TTL_MINUTES', (int)dv_env('ACTIVATION_CODE_TTL_MINUTES', 15));
/**
 * ETAPE P.1 : expiration du jeton dedie de reinitialisation de mot de
 * passe (distinct du code court d'activation ci-dessus).
 */
define('RESET_PASSWORD_TOKEN_TTL_MINUTES', (int)dv_env('RESET_PASSWORD_TOKEN_TTL_MINUTES', 30));
define('RATE_LIMIT_CODE_MAX_ATTEMPTS', (int)dv_env('RATE_LIMIT_CODE_MAX_ATTEMPTS', 5));
define('RATE_LIMIT_LOGIN_MAX_ATTEMPTS', (int)dv_env('RATE_LIMIT_LOGIN_MAX_ATTEMPTS', 5));
define('RATE_LIMIT_LOGIN_MAX_ATTEMPTS_IP', (int)dv_env('RATE_LIMIT_LOGIN_MAX_ATTEMPTS_IP', 20));
define('RATE_LIMIT_LOGIN_WINDOW_SECONDS', (int)dv_env('RATE_LIMIT_LOGIN_WINDOW_SECONDS', 900));
define('RATE_LIMIT_RESEND_MAX_ATTEMPTS', (int)dv_env('RATE_LIMIT_RESEND_MAX_ATTEMPTS', 3));
define('RATE_LIMIT_RESEND_WINDOW_SECONDS', (int)dv_env('RATE_LIMIT_RESEND_WINDOW_SECONDS', 3600));
/**
 * ETAPE P.3 : liste blanche d'IP (reverse proxy, load balancer...)
 * autorisees a fixer l'IP du client via X-Forwarded-For (ou assimile) -
 * voir Util::get_IP_address(). Vide par defaut : aucun en-tete client
 * n'est jamais pris en compte tant que ce n'est pas configure
 * explicitement avec l'IP (ou la plage) du proxy reel.
 */
define('TRUSTED_PROXIES', dv_env('TRUSTED_PROXIES', ''));

/**
 * ETAPE M.3 : Web Push (VAPID) - jamais versionnees, dans .env
 * uniquement (dclass/devupscms/ModulePushNotification/Entity/Push_subscription.php).
 * Independant du mecanisme existant (Configuration en base) utilise par
 * l'ancien flux FCM/mobile, laisse inchange.
 */
define('VAPID_PUBLIC_KEY', dv_env('VAPID_PUBLIC_KEY', ''));
define('VAPID_PRIVATE_KEY', dv_env('VAPID_PRIVATE_KEY', ''));
define('VAPID_SUBJECT', dv_env('VAPID_SUBJECT', 'mailto:' . dv_env('MAIL_FROM', 'no-reply@example.com')));

/**
 * Geolocalisation (Util::getTimezone(), ipgeolocation.io - optionnel) :
 * jamais codee en dur dans le code, vide par defaut (getTimezone()
 * retombe alors sur 'UTC' sans appel HTTP).
 */
define('IPGEOLOCATION_API_KEY', dv_env('IPGEOLOCATION_API_KEY', ''));

/**
 * Langues (lang.php) : LANG_DIR permet de pointer ailleurs que lang/ (la
 * suite de tests utilise un dossier temporaire recree par tests/bootstrap.php
 * pour ne jamais ecrire dans les fichiers versionnes). LANG_COLLECT coupe
 * la collecte automatique des cles manquantes independamment de __prod.
 */
$__lang_dir = dv_env('LANG_DIR', 'lang');
if (!preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $__lang_dir))
    $__lang_dir = ROOT . $__lang_dir;
define('LANG_DIR', rtrim($__lang_dir, '/\\') . '/');
unset($__lang_dir);
define('LANG_COLLECT', dv_env('LANG_COLLECT', !__prod));

global $devups_cache;
$devups_cache = [];
