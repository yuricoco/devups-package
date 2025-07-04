<?php

//require __DIR__ . '/../../config/constante.php';

use dclass\devups\Tchutte\DB_dumper;

define('__debug', false);

require __DIR__ . '/../../config/dependanceInjection.php';

require __DIR__ . '/BackendGenerator.php';
require __DIR__ . '/android/BackendGeneratorJava.php';
require __DIR__ . '/RequestGenerator.php';
require __DIR__ . '/Traitement.php';
require __DIR__ . '/__Generator.php';
require __DIR__ . '/android/__Generatorjava.php';


global $separator, $isWindows;
$isWindows = false;
$separator = '/';

//If the first three characters PHP_OS are equal to "WIN",
//then PHP is running on a Windows operating system.
if (strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0) {
    $isWindows = true;
    $separator = '\\';
}

$module_entities = [];

function getproject($namespace)
{
    global $separator;
    // get all components building the global project
    $components = Core::buildOriginCore();

    $ns = explode($separator, $namespace);
    return __Generator::findproject($components, $ns[0]);
}

function getcomponent($namespace)
{
    global $separator;
    // get all components building the global project
    $components = Core::getComponentCore();

    $ns = explode($separator, $namespace);
    return __Generator::findproject($components, $ns[0]);

}

function recurse_copy_dir(string $src, string $dest): int
{
    $count = 0;

    // ensure that $src and $dest end with a slash so that we can concatenate it with the filenames directly
    $src = rtrim($src, "/\\") . "/";
    $dest = rtrim($dest, "/\\") . "/";

    // use dir() to list files
    $list = dir($src);

    // create $dest if it does not already exist
    @mkdir($dest);

    // store the next file name to $file. if $file is false, that's all -- end the loop.
    while (($file = $list->read()) !== false) {
        if ($file === "." || $file === "..") continue;
        if (is_file($src . $file)) {
            copy($src . $file, $dest . $file);
            $count++;
        } elseif (is_dir($src . $file)) {
            $count += recurse_copy_dir($src . $file, $dest . $file);
        }
    }

    return $count;
}

if ($argv[1] === 'serve'){
    $port = __env_port;
    /*if (isset($argv[2])) {
        $argp = explode("=", $argv[2]);
        if ($argp[0] !="--port"){
            echo "you mean --port= ?";
        }
        if (!isset($argp[1])){
            echo "you post specify the port";
            die;
        }
        if (!is_numeric($argp[1])){
            echo "you post specify a valid port";
            die;
        }else
            $port = $argp[1];
    }*/

    exec("php -S 127.0.0.1$port", $result);

    echo __env;
    echo "\n";
    echo __env."admin/";
    die;
}

if ($argv[1] === 'tests') {

    $result = [];
    exec(__DIR__."\\..\\..\\vendor\\bin\\phpunit tests", $result);

    echo implode("\n ", $result);

    die;

}

if ($argv[1] === 'schema:update') {

    $result = [];
    exec("bin\doctrine orm:schema:update --dump-sql", $result);

    //
    /*$em = DBAL::getEntityManager();

    $listentities = ["Cmstext"];
    foreach ($listentities as $entity){
        $lc_entity = strtolower($entity);
        $migrationlang = ROOT."database/langs/migration_$lc_entity.json";
        if(!file_exists($migrationlang)) {
            \DClass\lib\Util::writein("{}", $migrationlang);
        }
        $dbal = new DBAL();
        $sql = "";
        $tableexist = $dbal->tableExists($lc_entity."_lang");
        if(!$tableexist){
            // todo: create table
            $sql = "
CREATE TABLE `$lc_entity\_lang` (
  `$lc_entity\_id` int(10) UNSIGNED NOT NULL,
  `lang_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
            $dbal->executeDbal($sql);
        }
        $migrationlang = json_decode(file_get_contents($migrationlang), true);
        $attrs = $migrationlang["attrs"];
        if($attrs != $entity::langs)
            continue;

        $sql .= "ALTER TABLE $lc_entity\_lang ";
        $ec = $em->getClassMetadata("\\" . $entity);
        $attribtodelete = array_diff($attrs, $entity::langs);
        $attribtoadd = array_diff($entity::langs, $attrs);
        foreach($attribtodelete as $attr){
            // todo: alter table drop
            $sql .= " DROP COLUMN $attr varchar(255)";
        }
        foreach($attribtoadd as $attr){
            // todo: alter table
            $sql .= " ADD $attr varchar(255)";
        }
        $sql .= ";";

        $dbal->executeDbal($sql);

    }*/

    $action = "--dump-sql";
    if (isset($argv[2]))
        $action = $argv[2];

    switch ($action) {
        case '--dump-sql':

            echo implode("\n ", $result);
            break;

        case '--force':

            require ROOT . 'src/requires.php';

            $entitiestoupdate = [];
//            foreach ($result as $row) {
//
//            }

            echo " Updating database schema...\n ";

            // todo: connect to toolrad and update the schema of the entity

            $dbal = new DBAL();
            for ($i = 2; $i < count($result); $i++) {

                if (!isset($result[$i]))
                    break;

                $query = $result[$i];
                if ($query) {

                    $pos = strpos($query, "ALTER TABLE");

                    if (!$pos)
                        $pos = strpos($query, "CREATE TABLE");

                    if (!$pos)
                        continue;
                    $ent = (explode(" ", trim($query))[2]);
                    if (!in_array($ent, $entitiestoupdate))
                        $entitiestoupdate[] = $ent;

                    $sanitize = "";
                    if (strpos($query,'ADD CONSTRAINT')) {
                        $fk = get_string_between($query,'KEY (', ')');
                        $fe = get_string_between($query,'REFERENCES ', ' (');

                        $sanitize = " UPDATE $ent SET $fk = null WHERE (SELECT COUNT(*) FROM $fe WHERE id = $fk) = 0; ";
                    }


                    try {

                        $r = $dbal->executeDbal($sanitize.$query, [], 0, true);
                        \dclass\devups\Tchutte\DB_dumper::migration($sanitize);
                        \dclass\devups\Tchutte\DB_dumper::migration($query);
                    }catch (Exception $exception){
                        \dclass\devups\Tchutte\DB_dumper::migration("-- ".$query."\n-- ".$exception->getMessage());
                    }

                }
            }
            //if (count($entitiestoupdate) )
            echo "\n \e[42m \e[1m\e[30m \n[OK] Database schema updated successfully!\n ";
            echo "\e[0m";


            foreach ($entitiestoupdate as $entity) {
                //__Generator::core($entity);
                $backend = new BackendGenerator();
                $backend->coreGenerator(ucfirst($entity), true);
            }

            echo "\n \e[42m \e[1m\e[30m \n All entity has well been synchronized";
            echo "\e[0m";

            break;
        default:
            echo " \n Enter the option --force to persist the query ";

            echo implode("\n ", $result);
            break;

            break;
    }
    echo "\n";
    die;

}

if ($argv[1] === 'transaction:rollback') {

    $lastdump = ROOT . 'database/dump/dump_' . $argv[2] . '.sql';
    if (!file_exists($lastdump)) {
        echo "\n > dump_" . $argv[2] . " file not exist! rollback aborded!!!\n";
        die;
    }
    $root_path = ROOT . 'database/transaction/transaction_' . $argv[2] . '.data';
    if (!file_exists($root_path)) {
        echo "\n > transaction_" . $argv[2] . " file not exist! rollback aborded!!!\n";
        die;
    }

    $db_dump = new DB_dumper();
    $db_dump->dump(true);
    echo " > current version of .\n\n" . dbname . " has been dumped successfully ...\n";

    RequestGenerator::databasecreate(dbname); //,
    echo " > Creating Database.\n\n" . dbname . ": created with success ...\n";

    $rqg = new Database();
    $dvupsadminsql = file_get_contents($lastdump);
    $rqg->link()->prepare($dvupsadminsql)->execute();

    echo "\n\n > Last stable version of database has been imported successfully ... \n\n";

    //$db_dump = new \dclass\devups\DB_dumper();
    $db_dump->rollback($argv[2]);
    echo "\n\n > " . $argv[2] . "'s transaction successfully rollback :) \n\n";

    die;
}

$project = null;
if (isset($argv[2])) {
    $project = getproject($argv[2]);

    chdir('src/' . $project->name);

    switch ($argv[1]) {

        case 'entity:e:data':
            break;

        case 'entity:g:core':
            require ROOT . 'src/requires.php';
            if (isset($argv[3]) && $argv[3] == "--sync")
                __Generator::core($argv[2], true); //,
            else
                __Generator::core($argv[2]); //,

            echo $argv[2] . ": Core generated with success";
            break;

        case 'entity:g:postmandoc':
            __Generator::postmandoc($argv[2], $project); //,
            echo $argv[2] . ": Postmandoc generated with success";
            break;

        case 'core:g:views':
            __Generator::views($argv[2], $project); //,
            echo $argv[2] . ": Views generated with success";
            break;

        case 'core:g:genesis':
            __Generator::genesis($argv[2], $project); //,
            echo $argv[2] . ": Genesis generated with success";
            break;

        case 'core:g:controller':
            __Generator::controller($argv[2], $project); //,
            echo $argv[2] . ": Controller generated with success";
            break;

        case 'core:g:frontcontroller':
            __Generator::frontcontroller($argv[2], $project); //,
            echo $argv[2] . ": Front Controller generated with success";
            break;

        case 'core:g:form':
            __Generator::form($argv[2], $project); //,
            echo $argv[2] . ": Form generated with success";
            break;

        case 'core:g:table':
            __Generator::table($argv[2], $project); //,
            echo $argv[2] . ": Table generated with success";
            break;

        case 'core:g:formwidget':
            __Generator::formwidget($argv[2], $project); //,
            echo $argv[2] . ": Form widget generated with success";
            break;

        case 'core:g:viewswidget':
            __Generator::detailwidget($argv[2], $project); //,
            echo $argv[2] . ": Detail widget generated with success";
            break;

        case 'core:g:entity':

            if (isset($argv[3])) {
                if ($argv[3] == "--lang") {
                    __Generator::entityLang($argv[2], $project); //,
                    echo $argv[2] . ": Entity lang generated with success";
//                }
//                elseif(isset($argv[4])){
//                    __Generatorjava::entity($argv[2], $project, $argv[4]); //,$argv[4] for package
//                    echo $argv[2] . ": Entity java generated with success";
                } else
                    echo "warning: did you mean --lang?";
            } else {
                __Generator::entity($argv[2], $project); //,
                echo $argv[2] . ": Entity generated with success";
            }
            break;

        case 'core:g:crud':
            if (isset($argv[3])) {
                if (isset($argv[4])) {
                    __Generatorjava::crud($argv[2], $project, $argv[4]); //,$argv[4] for package
                    echo $argv[2] . ": Entity java generated with success";
                } else
                    echo "warning: package missing!";
            } else {
                __Generator::crud($argv[2], $project); //,
                echo $argv[2] . ": CRUD generated with success";
            }
            Core::updateDvupsTable();
            break;

        case 'core:g:dependencies':
            __Generator::__entitydependencies($argv[2], $project); //,
            echo $argv[2] . ": Dependencies generated with success";
            break;

        case 'core:g:module':
            __Generator::module($project, $argv[2]); //,
            echo $argv[2] . ": Module generated with success";
            Core::updateDvupsTable();
            break;

        case 'core:g:moduleendless':
            __Generator::__moduleendless($project, $argv[2]); //,
            echo $argv[2] . ": Moduleendless generated with success";
            break;

        case 'core:g:moduledependencies':
            __Generator::__dependencies($project, $argv[2]); //,
            echo $argv[2] . ": Dependencies generated with success";
            break;

        case 'core:g:moduleindex':
            __Generator::__index($project, $argv[2]); //,
            echo $argv[2] . ": Index generated with success";
            break;

        case 'core:g:moduleservices':
            __Generator::__services($project, $argv[2]); //,
            echo $argv[2] . ": Services generated with success";
            break;

        case 'core:g:moduleressources':
            __Generator::__ressources($project, $argv[2]); //,
            echo $argv[2] . ": Resources generated with success";
            break;

        case 'core:g:component':
            __Generator::component(Core::getComponentCore($argv[2])); //,
            echo $project->name . ": component generated with success";
            Core::updateDvupsTable();
            break;

        default :
            __Generator::help();
            break;
    }

    chdir('../../');
} else {

    require __DIR__ . '/../../src/devups/ModuleLang/Entity/Dvups_lang.php';
    require __DIR__ . '/../../src/devups/ModuleAdmin/Entity/Dvups_role.php';
    require __DIR__ . '/../../src/devups/ModuleAdmin/Entity/Dvups_right.php';
    require __DIR__ . '/../../src/devups/ModuleConfig/Entity/Dvups_entity.php';
    require __DIR__ . '/../../src/devups/ModuleConfig/Entity/Dvups_entity_lang.php';

    switch ($argv[1]) {

        case 'init:caches':

            if (!file_exists("cache")) {
                echo " > /cache folders created with success ...\n";
                mkdir('cache', 0777);
                mkdir('cache/views', 0777);
                mkdir('cache/local', 0777);
                mkdir('cache/local/admin', 0777);
                mkdir('cache/local/front', 0777);
            }
            if (!file_exists("database")) {
                echo " > /database folders created with success ...\n";
                mkdir('database', 0777);
                mkdir('database/dump', 0777);
                mkdir('database/log', 0777);
                mkdir('database/migration', 0777);
                mkdir('database/transaction', 0777);
            }

            break;
        case 'build':

            $dir = __DIR__ . '/../../build';

// we delete the previews version
            if (!file_exists($dir . ''))
                mkdir($dir . '', 777, true);

            if (file_exists($dir . '/' . __project_id . '.zip'))
                unlink($dir . '/' . __project_id . '.zip');

            recurse_copy_dir(ROOT . "src", ROOT . "build/src");
            recurse_copy_dir(ROOT . "dclass", ROOT . "build/dclass");
            //$files = scanDir::scan(ROOT, [], true);

            HZip::zipDir($dir, $dir . '/' . __project_id . '.zip');

            echo 'succes de la compression';
            break;
        case 'deploy':

            $dir = __DIR__ . '/../../build';
            $file = $dir . '/' . __project_id . '.zip';

            $zip = new ZipArchive;
            if ($zip->open($file) === TRUE) {

                echo 'unzip start';
                $zip->extractTo('./');
                $zip->close();

                echo 'succes de la décompression';
            } else {
                echo 'échec de la décompression de ' . $file . '.zip';
            }
            break;
        case 'install':

            if (!file_exists("cache")) {
                echo " > /cache folders created with success ...\n";
                mkdir('cache', 0777);
                mkdir('cache/views', 0777);
                mkdir('cache/local', 0777);
                mkdir('cache/local/admin', 0777);
                mkdir('cache/local/front', 0777);
            }

            if (!file_exists("database")) {
                echo " > /database folders created with success ...\n";
                mkdir('database', 0777);
                mkdir('database/dump', 0777);
                mkdir('database/log', 0777);
                mkdir('database/migration', 0777);
                mkdir('database/transaction', 0777);
            }

            if (!__prod) {
                RequestGenerator::databasecreate(dbname); //,
                echo " > Creating Database.\n\n" . dbname . ": created with success ...\n";
                $result = [];
                exec("bin\doctrine orm:schema:create", $result);

                echo "\n > Update database schema (DOCTRINE ORM).\n\n" . implode("\n", $result);

                $rqg = new Database();
                $path = __DIR__ . '/config_data.sql';
                $dvupsadminsql = file_get_contents($path);
                $dvupsadminsql .= "
            TRUNCATE `configuration`;
            INSERT INTO `configuration` ( `_key`, `_value`, `_type`) VALUES
                    (\"PROJECT_NAME\", \"" . PROJECT_NAME . "\", 'string'),
                    (\"LANG\", \"en\", 'string'),
                    (\"__lang\", \"en\", 'string'),
                    (\"JSON_ENCODE_DEPTH\", 512, 'integer');
            ";

                try {
                    $rqg->link()->prepare($dvupsadminsql)->execute();
                } catch (Exception $e) {
                    dv_dump($e->getMessage());
                }
            }else{
                echo "\nEscape Database creation in production\n ";
            }

            Dvups_lang::cacheData();
            Core::updateDvupsTable();

            echo "\n\n > Set the master admin.\n\nData master admin initialized with success.\ncredential\nlogin: dv_admin\npassword: admin
            \nYour project is ready to use. Do your best :)
            \n>php devups serve";
            break;

        case 'database:create':
            RequestGenerator::databasecreate(dbname); //,
            $result = [];
            exec("bin\doctrine orm:schema:create", $result);

            echo dbname . ": created with success\n\n " . implode("\n", $result);
            break;

        case 'dvups_:admin':
            $rqg = new DBAL();
            $path = __DIR__ . '/config_data.sql';
            $dvupsadminsql = file_get_contents($path);
            $rqg->executeDbal($dvupsadminsql);
            echo "Data admin initialized with success";
            break;

        case 'dvups_:implement':
            Core::updateDvupsTable();
            echo "Data admin updated with success";
            break;

        case 'dvups_:update':
            if (Core::updateDvupsTable())
                echo "Data admin updated with success";
            else
                echo "Data admin already uptodate";
            break;

        default :
            __Generator::help();
            break;
    }
}

echo "\n";



