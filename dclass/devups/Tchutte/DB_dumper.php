<?php


namespace dclass\devups\Tchutte;

use DBAL;
use Ifsnop\Mysqldump as IMysqldump;
use Bugmanager;

class DB_dumper extends \Database
{

    public function dump($isrollbackprocess = false)
    {

        $file = ROOT . 'database/dump/dump_' . date("Ymd") . '.sql';
        if ($isrollbackprocess)
            $file = ROOT . 'database/dump/dump_' . date("YmdHis") . '.sql';

        //$dir = dirname(__FILE__) . '/dump_' . date("Ymd") . '.sql';

        if (file_exists($file))
            return;

        $user = dbuser;
        $pass = dbpassword;
        $database = dbname;
        $host = dbhost;

        try {
            $dump = new IMysqldump\Mysqldump("mysql:host=$host;dbname=$database", dbuser, dbpassword);
            $dump->start($file);
        } catch (\Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
        }
        // exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$file} 2>&1", $output);

//        $moddepend = fopen($file, "a+");
//        fputs($moddepend, $output );
//        fclose($moddepend);
        // var_dump($output);

    }

    public function transaction($table, $sql, $values, $identifier = null)
    {
        if (strpos($sql, "INSERT INTO") !== FALSE) {
            $action = "INSERT INTO";
        } else if (strpos($sql, "UPDATE") !== FALSE) {
            $action = "UPDATE";
        } else
            $action = "DELETE";

        $transaction = [
            "hour" => date("H:i:s"),
            "table" => $table,
            "action" => $action,
            "sql" => $sql,
            "values" => $values,
            "status" => false,
            "identifier" => $identifier,
        ];
        $file = ROOT . 'database/transaction/transaction_' . date("Ymd") . '.data';

        // journal de transactions : ne doit jamais faire echouer l'ecriture
        // en base (dossier absent ou non inscriptible)
        if (!is_dir(dirname($file)))
            @mkdir(dirname($file), 0775, true);
        $moddepend = @fopen($file, "a+");
        if (!$moddepend) {
            error_log("devups: journal de transactions non inscriptible ($file)");
            return;
        }
        fputs($moddepend, json_encode($transaction) . "\n");
        fclose($moddepend);

    }

    public static function migration($sql)
    {

        $file = ROOT . 'database/migration/migration_' . date("Ymd") . '.sql';

        $moddepend = fopen($file, "a+");
        fputs($moddepend, $sql . "\n");
        fclose($moddepend);

    }

    public function rollback($date)
    {

        $root_path = ROOT . 'database/transaction/transaction_' . $date . '.data';

        //$files = scandir($root_path);

        //for ($i = 2; $i < count($files); $i++) {

        //if (($handle = fopen($root_path . "/" . $files[$i], "r")) !== FALSE) {
        if (($handle = fopen($root_path, "r")) !== FALSE) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $transaction = json_decode($buffer);
                echo $this->execRollback($transaction);
                //$dbal->executeDbal($transaction->sql, $transaction->values, $transaction->action);
            }
        }

        //}

    }

    public function execRollback($transaction)
    {

        $sql = $transaction->sql;
        $values = (array)$transaction->values;
        if ($transaction->action == "INSERT INTO"
            && $transaction->identifier && is_numeric($transaction->identifier)) {

//            $sql = str_replace("` (`", "` (`id`, `", $transaction->sql);
//            $sql = str_replace("S (:", "S (:id, :", $sql);
            $values["id"] = $transaction->identifier;
            /*$values = [
                "id" => $transaction->identifier,
                ...((array) $transaction->values)
            ];*/
        }

//        dv_dump($sql, $values, $transaction->identifier);
        try {

            $query = $this->link->prepare($sql);
            $result = $query->execute($values);
            if (!$result) {

                $debug = [
                    "success" => false,
                    'class' => __CLASS__,
                    'entity' => $transaction->table,
                    'detail' => $query->errorInfo(),
                    'request' => $transaction->sql,
                    'values' => $transaction->values,
                ];

                $file = ROOT . 'database/log/log_' . date("Ymd") . '.log';

                $moddepend = fopen($file, "a+");
                fputs($moddepend, json_encode($debug) . "\n");
                fclose($moddepend);

                return $debug; //$transaction->sql . " failed with error: " . implode(",", $query->errorInfo()) . "\n";
            }
            /*if ($transaction->action == "INSERT INTO" && $transaction->identifier) {
                $req = $this->link->prepare("select @@IDENTITY as id");
                $req->execute();
                $id = $req->fetch() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo()));
                $return = $id['id'];
                $req = $this->link->prepare("UPDATE {$transaction->table} SET id =  {$transaction->identifier} WHERE id = {$return} ");
                $req->execute();
            }*/

            return [
                "success" => true,
                'detail' => $transaction->sql . " succeed \n",
            ];

        } catch (\Exception $e) {
            return [
                "success" => false,
                'class' => __CLASS__,
                'entity' => $transaction->table,
                'detail' => $e->getMessage(),
                'request' => $transaction->sql,
                'values' => $transaction->values,
            ];
        }
    }

}