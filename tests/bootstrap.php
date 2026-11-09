<?php

/**
 * Amorcage de la suite de tests.
 *
 * - charge les constantes depuis l'environnement (phpunit.xml.dist) ;
 * - pour les suites integration/e2e, (re)cree la base de test DEDIEE, son
 *   schema (Doctrine SchemaTool) et les donnees de base (config_data.sql) ;
 * - charge le noyau et les modules, comme header.php, sans session ni rendu.
 *
 * DEVUPS_TEST_SKIP_DB=1 saute la preparation de la base (tests unitaires
 * seuls, machine sans MySQL). `--testsuite unit` la saute aussi.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DEVUPS_TESTING', true);
chdir(__DIR__ . '/..');

/** Vrai si la commande en cours lance une suite qui touche la base. */
function dv_test_needs_db(): bool
{
    if (getenv('DEVUPS_TEST_SKIP_DB') === '1')
        return false;
    $argv = implode(' ', $_SERVER['argv'] ?? []);
    return !preg_match('/--testsuite[= ]+unit\b/', $argv);
}

// LANG_DIR (traductions) : jamais les fichiers versionnes lang/*.php -
// dossier temporaire, recree a chaque run, partage avec le serveur e2e
// (HttpServer::start() transmet getenv() au process php -S qu'il demarre).
$dv_test_lang_dir = sys_get_temp_dir() . '/devups-phpunit-lang-' . getmypid() . '/';
if (is_dir($dv_test_lang_dir)) {
    foreach (glob($dv_test_lang_dir . '*') ?: [] as $stale)
        @unlink($stale);
    @rmdir($dv_test_lang_dir);
}
mkdir($dv_test_lang_dir, 0777, true);
foreach (['fr', 'en'] as $iso)
    file_put_contents($dv_test_lang_dir . $iso . '.php', "<?php\n\nreturn [];\n");
putenv('LANG_DIR=' . $dv_test_lang_dir);
register_shutdown_function(function () use ($dv_test_lang_dir) {
    foreach (glob($dv_test_lang_dir . '*') ?: [] as $f)
        @unlink($f);
    @rmdir($dv_test_lang_dir);
});
unset($dv_test_lang_dir);

require __DIR__ . '/../config/load_constants.php';

if (dv_test_needs_db()) {
    if (!preg_match('/(test|phpunit)/i', dbname)) {
        fwrite(STDERR, "\nRefus : la base de test doit contenir 'test' ou 'phpunit' dans son nom (actuel : " . dbname . ").\n");
        exit(1);
    }
    $pdo = new PDO('mysql:host=' . dbhost, dbuser, dbpassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('DROP DATABASE IF EXISTS `' . dbname . '`');
    $pdo->exec('CREATE DATABASE `' . dbname . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = null;

    // La base de test repart de zero (ids d'auto-increment inclus) : un
    // cache/rights/<role_id>.php laisse par une run precedente referrerait
    // un role qui n'est plus le meme, faussant compiledRights() pour le
    // role qui reutilise cet id dans cette run.
    foreach (glob(__DIR__ . '/../cache/rights/*.php') as $staleCache)
        unlink($staleCache);
    // meme raisonnement pour cache/lang/active.php (Dvups_lang::cachedActiveDlangs()) :
    // des ids de langue qui ne correspondent plus a cette run.
    foreach (glob(__DIR__ . '/../cache/lang/*.php') as $staleCache)
        unlink($staleCache);
    // ETAPE L.1 : cache/ratelimit/ (RateLimiter) est indexe par des cles
    // qui incluent souvent un id d'utilisateur/compte - memes ids
    // reutilises par la base qui repart de zero a chaque run, sans que les
    // compteurs d'echecs d'une run precedente aient de sens pour la run en
    // cours (et pourraient faire echouer a tort des tests sur la limite,
    // comme `UserActivationTest::testResendRegeneratesTheStoredHash`).
    foreach (glob(__DIR__ . '/../cache/ratelimit/*.json') as $staleCache)
        unlink($staleCache);
}

// noyau + EntityManager + modules. Les deprecations emises a la compilation
// par des dependances tierces ne concernent pas le code teste.
error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../config/dependanceInjection.php';
require __DIR__ . '/../src/requires.php';
error_reporting(E_ALL);

// schema + donnees de base, une fois toutes les classes d'entites chargees
if (dv_test_needs_db()) {
    global $em;
    $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
    $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

    // Le sql_mode par defaut de MySQL 8 (utilise par la CI) inclut
    // STRICT_TRANS_TABLES ; celui de MariaDB (utilise en local par la
    // plupart des dev) ne l'inclut pas - un bug sensible au mode strict
    // (colonne NOT NULL sans valeur fournie, par exemple) passait donc en
    // local et seulement en local. Force ici, sur LA connexion PDO
    // partagee de tout le framework (DBAL::pdo(), un seul objet pour tout
    // le run de cette suite), le sql_mode par defaut de MySQL 8 - pour que
    // `composer test` en local echoue exactement comme la CI plutot que de
    // masquer ce genre de bug. Permanent pour toute la suite (pas de reset
    // entre les tests) : c'est le comportement reel d'un serveur MySQL 8.
    \Model::pdo()->exec(
        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE," .
        "ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
    );

    $seed = file_get_contents(__DIR__ . '/../dclass/generator/config_data.sql');
    \Model::pdo()->exec($seed);
}

// lang.php (ETAPE H) appelle desormais local(), qui valide la langue via
// dv_valid_lang() - lequel interroge le cache des langues ACTIVES
// (Dvups_lang::cachedActiveDlangs()), donc la base. Chargé seulement une
// fois le schema/les donnees prets ci-dessus (sinon la toute premiere
// requete SQL de la suite se ferait sur une table dvups_lang inexistante).
require __DIR__ . '/../lang.php';

foreach (glob(__DIR__ . '/Support/*.php') as $support)
    require_once $support;

// etat global attendu par une partie du noyau (cf. header.php)
global $viewdir, $dvlangs, $dlangs, $global_config;
$viewdir = [web_dir . "views"];
$global_config = require __DIR__ . '/../config/dvups_configurations.php';
$dvlangs = [];
$dlangs = [];
if (dv_test_needs_db())
    $dlangs = Dvups_lang::cachedActiveDlangs();
