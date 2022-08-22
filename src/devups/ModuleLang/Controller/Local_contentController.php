<?php


use dclass\devups\Controller\Controller;
use Shuchkin\SimpleXLSX;

class Local_contentController extends Controller
{

    private static $path = ROOT . "cache/local/".__env_lang;
    const pathmodule = ROOT . "web/app3/frontend1/src/devupsjs/";

    public function listView($next = 1, $per_page = 10)
    {

        $this->datatable = Local_contentTable::init(new Local_content())->buildindextable();

        self::$jsfiles[] = Local_content::classpath('Resource/js/local_contentCtrl.js');

        $this->entitytarget = 'Local_content';
        $this->title = "Manage Local_content";

        $this->renderListView();

    }

    public function datatable($next, $per_page)
    {
        return ['success' => true,
            'datatable' => Local_contentTable::init(new Local_content())->buildindextable()->getTableRest(),
            // 'datatable' => Local_contentTable::init(new Local_content())->router()->getTableRest(),
        ];
    }


    public function createAction($local_content_form = null)
    {
        extract($_POST);

        $local_content = $this->form_fillingentity(new Local_content(), $local_content_form);


        if ($this->error) {
            return array('success' => false,
                'local_content' => $local_content,
                'action' => 'create',
                'error' => $this->error);
        }

        $id = $local_content->__insert();
        return array('success' => true,
            'local_content' => $local_content,
            'tablerow' => Local_contentTable::init()->buildindextable()->getSingleRowRest($local_content),
            'detail' => '');

    }

    public function updateAction($id, $local_content_form = null)
    {
        extract($_POST);

        $local_content = $this->form_fillingentity(new Local_content($id), $local_content_form);


        if ($this->error) {
            return array('success' => false,
                'local_content' => $local_content,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $local_content->__update();
        return array('success' => true,
            'local_content' => $local_content,
            'tablerow' => Local_contentTable::init()->buildindextable()->getSingleRowRest($local_content),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Local_content';
        $this->title = "Detail Local_content";

        $local_content = Local_content::find($id);

        $this->renderDetailView(
            Local_contentTable::init()
                ->builddetailtable()
                ->renderentitydata($local_content)
        );

    }

    public function deleteAction($id)
    {

        Local_content::delete($id);
        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Local_content::delete()->where("id")->in($ids)->exec();

        return array('success' => true,
            'detail' => '');

    }

    public static function buildlocalcachesinglelang($lang)
    {

        $lcs = Local_content::where("lang", $lang)->get();

        $info = [];

        foreach ($lcs as $lc) {
            $info[$lc->getReference()] = $lc->getContent();
        }

        if ($info) {
            $contenu = json_encode($info, 1024);

            $entityrooting = fopen(self::$path . $lang . ".json", 'w');
            fputs($entityrooting, $contenu);
            fclose($entityrooting);

        }

    }

    public function regeneratecacheAction()
    {
        self::$path = ROOT . "cache/local/front/";
        $files = scandir(self::$path);
        foreach ($files as $file){
            if (!in_array($file, ['fr.json', 'en.json', '.', '..']))
                unlink(self::$path.$file);
        }
        self::buildlocalcache();

        self::$path = ROOT . "cache/local/admin/";
        $files = scandir(self::$path);
        foreach ($files as $file){
            if (!in_array($file, ['fr.json', 'en.json', '.', '..']))
                unlink(self::$path.$file);
        }
        self::buildlocalcache();

        Response::success()
            ->message(t("local cache regenerated with success"))
            ->json();

    }

    public static function buildlocalcache($path = null)
    {

        $lans = Dvups_lang::all();
        foreach ($lans as $lang) {
            $iso_code = $lang->getIso_code();

            if ($path)
                $lcs = Local_content::select()
                    ->where("this.path_key", $path)
                    ->setLang($lang->id)->get();
            else
                $lcs = Local_content::select()
                    ->setLang($lang->id)->get();

            $info = [];

            foreach ($lcs as $lc) {
                $info[$lc->getReference()] = $lc->content;
            }

            if ($info) {
                // todo - fix issue on php warning during the first call of the function translate t().

                $contenu = json_encode($info, 1024);

                if ($path) {
                    if (file_exists(self::$path . $iso_code . "_$path.json"))
                        unlink(self::$path . $iso_code . "_$path.json");
                    $entityrooting = fopen(self::$path . $iso_code . "_$path.json", 'w');
                } else {
                    if (file_exists(self::$path . $iso_code . ".json"))
                        unlink(self::$path . $iso_code . ".json");
                    $entityrooting = fopen(self::$path . $iso_code . ".json", 'w');
                }
                fputs($entityrooting, $contenu);
                fclose($entityrooting);

            }

        }
    }

    public static function newdatacollection($ref, $default, $path = null)
    {

        $lck = new Local_content_key();
        $lck->setReference($ref);
        $lck->path = __env_lang.Request::get("path");
        $lck->path_key = $path;
        $lck->__insert();

        $lans = Dvups_lang::all();
        $lc = new Local_content();
        $content = [];
        foreach ($lans as $lang) {
            //$lang = $lang->getIso_code();
            $content[$lang->getIso_code()] = $default;
        }

        //$lc->setLang($lang);
        $lc->setReference($ref);
        $lc->content = $content;
        $lc->path = __env_lang.Request::get("path");
        $lc->path_key = $path;
        $lc->local_content_key = $lck;
        $lc->__insert();

        if ($path)
            self::buildlocalcache($path);
        self::buildlocalcache();

        return ["success" => true];

    }

    public static function getdata($path = null)
    {

        $lang = DClass\lib\Util::local();

        if ($path) {

            if (!file_exists(self::$path . $lang . "_$path.json")) {
                \DClass\lib\Util::log("", $lang . "_$path.json", self::$path);

                self::buildlocalcache($path);
            }

            $content = file_get_contents(self::$path . $lang . "_$path.json");
            return json_decode($content, true);

        }
        if (!file_exists(self::$path . $lang . ".json")) {
            \DClass\lib\Util::log("", $lang . ".json", self::$path);
            self::buildlocalcache();
        }

        $content = file_get_contents(self::$path . $lang . ".json");
        return json_decode($content, true);

    }

    public static function getdatajs()
    {

        $lang = DClass\lib\Util::local();

        if (!file_exists(self::$path . $lang . ".json")) {
            self::buildlocalcache();
        }

        return file_get_contents(self::$path . $lang . ".json");

    }

    public function exportlangView()
    {
        $langs = Dvups_lang::all();
        $pages = Local_content_key::select()->whereNotNull("path_key")->groupBy("path_key")->get();
        Genesis::renderView("admin.exportlang", compact("langs", "pages"));
    }

    public static function devups($lang_dest, $lang, &$excelData, $target)
    {
        $id_lang_dest = $lang_dest->id;
        $id_lang = $lang->id;
        // Database configuration
        $dbHost = dbhost;
        $dbUsername = dbuser;
        $dbPassword = dbpassword;
        $dbName = dbname;

// Create database connection
        $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        if ($target == "all")
            $entities = Dvups_entity::all();
        elseif ($target == "entities")
            $entities = Dvups_entity::where("this.name", "!=", "local_content")->get();
        else {
            $entity = Dvups_entity::where("this.name", "local_content")->first();
            self::localContentExport($entity, $id_lang, $id_lang_dest, $db, $target, $excelData);
            return 1;
        }
        foreach ($entities as $table) {

            if (!class_exists($table->name)) continue;

            if (in_array($table->name, ["cmstext"]))
                continue;

            $class = ucfirst($table->name);
            $entity = new $class;
            if (!$entity->dvtranslate)
                continue;

            $attfield = "";
            $attribs = $entity->dvtranslated_columns;

            $table = $table->name;
            foreach ($attribs as $attr) {
                $attfield .= ", dest.$attr AS dest_$attr ";
            }
            $sql = " select t.* $attfield, 'entity' AS path from " . $table . "_lang t,
         (select * from " . $table . "_lang where 1 ) dest
          where t.lang_id = $id_lang AND dest.lang_id = $id_lang_dest AND dest." . $table . "_id = t.$table" . "_id";

            //dv_dump($sql);
            $key = $table;

            $query = $db->query($sql);
            if ($query->num_rows > 0) {
                // Output each row of the data
                while ($row = $query->fetch_assoc()) {
                    foreach ($attribs as $attrib) {
                        if (!$row[$attrib] && !$row["dest_" . $attrib])
                            continue;

                        $lineData = array($table,
                            $row[$key . '_id'], $attrib, $row["path"], $row[$attrib], $row["dest_" . $attrib]);
                        array_walk($lineData, 'filterData');
                        $excelData .= implode("\t", array_values($lineData)) . "\n";
                    }
                }
            } else {
                //$excelData .= 'No records found...' . "\n";
            }

        }


    }

    private static function localContentExport(\Dvups_entity $table, $id_lang, $id_lang_dest, $db, $target, &$excelData)
    {

        $entity = new Local_content();
        $attfield = "";
        $attribs = $entity->dvtranslated_columns;

        $table = "local_content";
        foreach ($attribs as $attr) {
            $attfield .= ", dest.$attr AS dest_$attr ";
        }
        if ($target != "local_content")
            $sql = " select t.* $attfield, lc.path from " . $table . "_lang t left join local_content lc on lc.id = t.local_content_id,
         (select * from " . $table . "_lang where 1 ) dest
          where t.lang_id = $id_lang AND dest.lang_id = $id_lang_dest AND dest." . $table . "_id = t.$table" . "_id AND lc.path_key = '$target'";
        else
            $sql = " select t.* $attfield, lc.path from " . $table . "_lang t left join local_content lc on lc.id = t.local_content_id,
         (select * from " . $table . "_lang where 1 ) dest
          where t.lang_id = $id_lang AND dest.lang_id = $id_lang_dest AND dest." . $table . "_id = t.$table" . "_id";

        // dv_dump($sql);
        $key = $table;

        $query = $db->query($sql);
        if ($query->num_rows > 0) {
            // Output each row of the data
            while ($row = $query->fetch_assoc()) {
                foreach ($attribs as $attrib) {
                    if (!$row[$attrib] && !$row["dest_" . $attrib])
                        continue;

                    $lineData = array($table,
                        $row[$key . '_id'], $attrib, $row["path"], $row[$attrib], $row["dest_" . $attrib]);
                    array_walk($lineData, 'filterData');
                    $excelData .= implode("\t", array_values($lineData)) . "\n";
                }
            }
        } else {
            //$excelData .= 'No records found...' . "\n";
        }


    }

    public function exportlang($iso_code)
    {

        function filterData(&$str)
        {
            $str = preg_replace("/\t/", "\\t", $str);
            $str = preg_replace("/\r?\n/", "\\n", $str);
            if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
        }

        $dest = Request::get("dest");
        $lang = Dvups_lang::getbyattribut("iso_code", $iso_code);
        $lang_dest = Dvups_lang::getbyattribut("iso_code", $dest);
        $target = Request::get("target", "all");

// Excel file name for download

        $fields = array('table', 'row', 'attribut', 'path', 'content ' . (($iso_code)),);

        $fields[] = "content " . $dest;
        $excelData = implode("\t", array_values($fields)) . "\n";

        self::devups($lang_dest, $lang, $excelData, $target);

        $fileName = "database-lang_" . date('Y-m-d_H-i') . ".csv";
//        $excelData = file_get_contents(__DIR__ . "/../import/datalang.csv");

        header('Content-Type: text/html; charset=windows-1252');
        //header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$fileName\"");

// Render excel data
        echo $excelData;

        exit;
    }

    public static function importlang()
    {
        require ROOT . "/dclass/lib/SimpleXLSX.php";
        $file = null;
        if (!file_exists(UPLOAD_DIR . "/importlang"))
            mkdir(UPLOAD_DIR . "/importlang", 0777, true);

        if (isset($_FILES['filelang'])) {
            if (move_uploaded_file($_FILES['filelang']['tmp_name'], UPLOAD_DIR . "/importlang/translation.xlsx")) {

                return [
                    "success" => true,
                    "detail" => "le fichier a bien ete uploade",
                ];

            } else {
                return array("success" => false, 'detail' => 'Problème lors de l\'uploadage !');
            }
        }

        $file = UPLOAD_DIR . "/importlang/translation.xlsx";
        if (!file_exists($file))
            return [
                "success" => false,
                "detail" => "le fichier est introuvable",
            ];

        //$langs = explode(",", $_GET['langs']);
        $lang = Request::get('lang');
        $iteration = Request::get("iteration");
        $next = Request::get("next");
        $iterator = 0;
        // $xlsx = new SimpleXLSX(__DIR__ . '/../import/database-lang_2022-02-23.xlsx');
        $xlsx = new SimpleXLSX($file);

        foreach ($xlsx->rows() as $i => $fields) {

            if ($i == 0) {
                $head = $fields;
                continue;
            }

            if ($i < $next)
                continue;
            $iterator++;
            if ($i > $next + $iteration)
                break;

            $iso = explode(" ", $head[5])[1];
            $idlangs = [];
            // foreach ($langs as $iso) {
            if ($lang != $iso) {
                return [
                    "success" => false,
                    "created" => $lang,
                    "updated" => $iso,
                    "i" => $i,
                    "remain" => -1,
                    "detail" => "la langue choisi n'est pas la bonne ",
                ];
                //if (!isset($row['content ' . $iso]))
                continue;
            }

            $row = array_combine($head, $fields);
            if (!$row['content ' . $iso])
                continue;

            if (!isset($idlangs[$iso]))
                $idlangs[$iso] = Dvups_lang::getbyattribut("iso_code", $iso)->id;


            $sql = "  select COUNT(*) from " . $row["table"]
                . "_lang where {$row["table"]}_id = {$row["row"]} AND lang_id =  " . $idlangs[$iso];
            $exist = (new DBAL())->executeDbal($sql);

            if ($exist) {
                DBAL::_updateDbal($row["table"] . "_lang",
                    [
                        $row["attribut"] => $row['content ' . $iso],
                    ],
                    "{$row["table"]}_id = {$row["row"]} AND lang_id =  " . $idlangs[$iso]);
            } /*else
                    Db::getInstance()->insert($row["table"],
                        [
                            "id_lang" => $idlangs[$iso],
                            "id_" . $row["table"] => $row["row"],
                            "" . $row["attribut"] => $row['content ' . $iso],
                        ]);*/


            //}

        }

        if ($iterator - $iteration < 0) {
            self::buildlocalcache();
        }

        return [
            "success" => true,
            "created" => true,
            "updated" => true,
            "i" => $i,
            "remain" => $iterator - $iteration,
            "detail" => "le fichier est ok",
        ];
    }

}
