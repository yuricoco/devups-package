<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *
 * Description of Model
 *
 * @author azankang atemkeng  <azankang.developer@gmail.com>
 */

//namespace dclass\devups;

class Model extends \dclass\devups\model\ModelSerializer
{

    use ModelTrait;
    use \dclass\devups\model\DateTimeTrait;
//    use BaseModel;

    public static $jsonmodel;

    //public $dverrormessage = "";

    /**
     * Point d'entree public pour tout code metier
     * qui a besoin de la connexion PDO du socle - la MEME que celle utilisee
     * par Ticket::find(), ->__insert(), ->__update(), etc. Delegue a
     * DBAL::pdo(), seul proprietaire reel du cache de connexion ; ne pas
     * fabriquer une seconde connexion `new PDO(...)` a cote.
     */
    public static function pdo(): \PDO
    {
        return \DBAL::pdo();
    }

    /**
     * Profondeur d'imbrication des transactions
     * ouvertes via Model::beginTransaction(). Compteur sur la CONNEXION
     * (statique, partagee par toutes les entites), pas sur une
     * entite en particulier - une transaction protege la connexion, pas une
     * table.
     */
    private static int $transactionDepth = 0;

    /**
     * Ouvre une transaction, ou pose un SAVEPOINT si une transaction est
     * deja ouverte. PDO leve une exception sur un beginTransaction()
     * imbrique (`There is already an active transaction`) - sans ce
     * compteur, deux methodes transactionnelles ne pourraient jamais
     * s'appeler l'une l'autre.
     * Seul le niveau 0 ouvre une vraie transaction ; seul son commit() (ou
     * rollBack()) engage reellement la base.
     */
    public static function beginTransaction(): void
    {
        if (self::$transactionDepth === 0) {
            self::pdo()->beginTransaction();
        } else {
            self::pdo()->exec('SAVEPOINT model_tx_' . self::$transactionDepth);
        }
        self::$transactionDepth++;
    }

    /**
     * Valide le niveau courant. Au niveau 0, commit reel sur la
     * connexion. A un niveau interne, ne fait rien de plus que descendre le
     * compteur - le SAVEPOINT posé par beginTransaction() n'a pas besoin
     * d'etre "relache" explicitement, ses effets restent acquis a la
     * transaction englobante jusqu'a son propre commit (ou jusqu'a un
     * ROLLBACK TO SAVEPOINT explicite a ce niveau).
     */
    public static function commit(): void
    {
        if (self::$transactionDepth === 0) {
            throw new \RuntimeException('Model::commit() appele sans transaction ouverte (Model::beginTransaction())');
        }
        self::$transactionDepth--;
        if (self::$transactionDepth === 0) {
            self::pdo()->commit();
        }
    }

    /**
     * Annule le niveau courant. Au niveau 0, rollBack reel (annule
     * tout). A un niveau interne, ROLLBACK TO SAVEPOINT - annule uniquement
     * ce que CE niveau a ecrit, laisse intact ce que le niveau englobant a
     * deja ecrit avant d'appeler ce niveau. C'est ce qui rend l'imbrication
     * utile : le niveau externe peut continuer et valider malgre l'echec
     * d'un sous-appel.
     */
    public static function rollBack(): void
    {
        if (self::$transactionDepth === 0) {
            throw new \RuntimeException('Model::rollBack() appele sans transaction ouverte (Model::beginTransaction())');
        }
        self::$transactionDepth--;
        if (self::$transactionDepth === 0) {
            self::pdo()->rollBack();
        } else {
            self::pdo()->exec('ROLLBACK TO SAVEPOINT model_tx_' . self::$transactionDepth);
        }
    }

    /** Profondeur courante (0 = aucune transaction ouverte). */
    public static function transactionDepth(): int
    {
        return self::$transactionDepth;
    }

    /**
     * Forme recommandee par defaut - valide au retour de
     * $fn, annule sur toute exception (rethrow apres rollBack, jamais
     * avalee). Protege contre l'oubli d'un rollBack() sur un retour
     * anticipe : un `return` a l'interieur de $fn rend simplement la main
     * a ce bloc, qui enchaine normalement sur commit().
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function transaction(callable $fn)
    {
        self::beginTransaction();
        try {
            $result = $fn();
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
        self::commit();
        return $result;
    }

    public function inrelation()
    {

        global $em;
        $this->classmetadata = $em->getClassMetadata("\\" . get_class($this));

        $objecarray = (array)$this;
        $association = array_keys($this->classmetadata->associationMappings);
        if (count($association))
            return true;

        return false;
//        foreach ($objecarray as $obkey => $value) {
//            // gere les attributs hérités en visibilité protected
//            if (is_object($value)) {
//                $classname = get_class($value);
//                if ($classname != 'DateTime' && $association[0] != $classname) {
//                    return true;
//                }
//            }
//        }
    }

    /**
     * static method gives the path of the module where the entity is/
     * @return string the path of the module where the class is.
     */
    public static function classpath($src = "", $route = __env)
    {
        //return get_called_class();
        $reflector = new ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dirname = explode("src", dirname($fn));
        $dirname = str_replace("Entity", "", $dirname[1]);
        return str_replace("\\", "/", $route . "src" . $dirname . $src);
    }

    /**
     * static method gives the path of the module where the entity is/
     * @return string the path of the module where the class is.
     */
    public static function classview($view, $route = __env."admin")
    {
        //return get_called_class();
        $reflector = new ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dirname = explode("src", dirname($fn));
        $dirname = str_replace("Entity", "", $dirname[1]);
        return $route . str_replace(["\\", '//'], "/", "/". $dirname . $view);
    }

    /**
     * static method gives the path of the module where the entity is/
     * @return string the path of the module where the class is.
     */
    public static function services($src = "", $route = __env)
    {
        //return get_called_class();
        $reflector = new ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dirname = explode("src", dirname($fn));
        $dirname = str_replace("Entity", "", $dirname[1]);
        return str_replace("\\", "/", $route . "src" . $dirname . "services.php?path=" . $src);
    }

    public static function classroot($src)
    {
        $reflector = new ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dirname = explode("src", dirname($fn));
        $dirname = str_replace("Entity", "", $dirname[1]);
        return ROOT . "src" . $dirname . $src;
    }

    /**
     * static method gives the directory of the module where the entity is/
     * @return classroot the directory of the module where the class is.
     */
    public static function classdir()
    {
        return ROOT . '..' . self::classpath();
    }

    public static function post($attribute, $default = null)
    {

        $class = strtolower(get_called_class());
        if (isset($_POST[$class . "_form"][$attribute]))
            return $_POST[$class . "_form"][$attribute];
        else
            return $default;

    }

    /**
     *
     * @param type $param
     * @return \Dfile
     */
    public static function Dfile($fileparam)
    {
//        $reflection = new ReflectionClass(get_called_class());
//        $entity = $reflection->newInstance();
        $entity = get_called_class();
        $dfile = new Dfile($fileparam, $entity);
        return $dfile;
    }

    /**
     * @param $file
     * @return bool
     */
    public function uploadfile($file)
    {

        $uploadmethod = 'set' . ucfirst($file);
        if (!method_exists($this, $uploadmethod)) {
            Genesis::json_encode(["success" => false,
                "error" => ["method not exist" => " You may create method " . $uploadmethod . " to update the file. "]]);
            die;
        }

        $dfile = new Dfile($file, $this);

        if ($dfile->error)
            return false;

        if ($this->id) {
            $getcurrentfile = 'get' . ucfirst($file);
            if (!method_exists($this, $getcurrentfile)) {
                Genesis::json_encode(["success" => false,
                    "error" => ["method not exist" => " You may create method " . $getcurrentfile . " to update the file. "]]);
                die;
            }

            $currentfile = call_user_func(array($this, $getcurrentfile));
            if ($currentfile)
                $dfile::deleteFile($currentfile, $dfile->uploaddir);
        }

        $url = $dfile->sanitize()->upload();

        if (!$url['success']) {
            Genesis::json_encode($url);
            die;
            return false;
        }

        $return = call_user_func(array($this, $uploadmethod), $url["file"]["hashname"]);

        if ($return) {
            Genesis::json_encode(["success" => false,
                "error" => ["method not exist" => " You may create method " . $getcurrentfile . " to update the file. "]]);
            die;
        }
    }

    /**
     * create a new entry
     * @return integer
     */
    public function __insert()
    {
        $this->created_at = date(\DClass\lib\Util::dateformat);
        $dbal = new DBAL();
        DBAL::$id_lang_static = $this->dvid_lang;
        $id = $dbal->createDbal($this);
        return $id;
    }

    /**
     * @param array $update
     * @return integer
     */
    public function __dclone($update = [])
    {
        $qb = new QueryBuilder($this);
        return $qb->__dclone($update)->where("this.id", $this->getId())->exec(DBAL::$INSERT);
    }

    /**
     * update a part or an entire entity
     * @example http://easyprod.spacekola.com description
     * @param Mixed $arrayvalues
     * @param Mixed $seton
     * @param Mixed $case
     * @return boolean | \QueryBuilder
     */
    public function __update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        $this->updated_at = date(\DClass\lib\Util::dateformat);

        /**
         * Avant d'ecrire une mise a jour complete
         * (pas une creation : $this->id deja pose) d'une entite traduite :
         * si la fiche n'a aucune traduction valide (voir
         * DvtranslateInspector::diagnose(),,
         * la synchronisation de traduction plus bas
         * (DBAL::updateDbal() -> updateLangValue()) peut produire une erreur SQL
         * illisible pour un administrateur. Refus explicite ici, avec un
         * motif clair, avant d'y arriver - uniquement sur ce chemin
         * ($arrayvalues === null, celui de CrudTrait::updateAction()/
         * __save()) : l'autre branche (mise a jour partielle via
         * QueryBuilder::update()) n'a pas ce defaut mesure, elle n'est pas
         * touchee. N'ecrit rien : seulement une lecture (DvtranslateInspector)
         * puis un refus.
         */
        if ($arrayvalues === null && $this->id && !empty($this->dvtranslate)) {
            $diagnosis = DvtranslateInspector::diagnose(get_class($this), (int)$this->id);
            if (in_array($diagnosis['case'], ['no_translation', 'orphan_lang'], true)) {
                throw new \RuntimeException($diagnosis['message_admin']);
            }
        }

        $dbal = new DBAL();
        DBAL::$id_lang_static = $this->dvid_lang;
//        dv_dump($arrayvalues, $this->dvpersistence);
        if ($arrayvalues === null) {// && !$this->dvpersistence

            return $dbal->updateDbal($this);
        } else {
            $qb = new QueryBuilder($this, false);
//            if ($arrayvalues)
            return $qb->whereId($this->id)->update($arrayvalues, $seton, $case);

//            return $qb->whereId($this->id)->update($this->dvpersistence, $seton, $case);
        }
    }

    public function __save()
    {

        $dbal = new DBAL();
        if ($this->getId()) {
            $this->updated_at = date("Y-m-d");
            return $this->__update();
            //return $dbal->updateDbal($this);
        } else {
            $this->created_at = date("Y-m-d");
            return $this->__insert();
            //return $dbal->createDbal($this);
        }
    }

    public function __show($recursif = false, $id_lang = null)
    {
        if ($this->dvfetched) {
            return $this;
        }

        $dbal = new DBAL();
        return $dbal->findByIdDbal($this, $recursif, [], $id_lang);
    }

    public function __findrow()
    {
        $qb = new QueryBuilder($this);
        return $qb->select()->where("id", "=", $this->id)->getInstance();
    }

    public function __delete($exec = true)
    {
        if ($exec) {
            $dbal = new DBAL();
            return $dbal->deleteDbal($this);
        } else {
            $qb = new QueryBuilder($this);
            $qb->delete();
            return $qb;
        }
    }

    public function __hasmany($collection, $exec = true, $incollectionof = null, $id_lang = null)
    {
        if (!is_object($collection)) {
            $reflection = new ReflectionClass($collection);
            $collection = $reflection->newInstance();
        }
        if ($this->getId()) {
            $dbal = new DBAL();
            return $dbal->hasmany($this, $collection, $exec, $incollectionof, $id_lang);
        } elseif (!$exec)
            return new QueryBuilder($this);
        else {
            return [];
        }
    }

    /**
     * @param $relation
     * @param bool $recursif
     * @return $this
     * @throws ReflectionException
     */
    public function __belongto($relation)
    {

        if (is_object($relation) && ($relation->dvfetched || !$relation->dvinrelation))
            return $relation;

        if (!$this->getId()) {
            if (is_object($relation)) :
                return $relation;
            else:
                $reflection = new ReflectionClass($relation);
                return $reflection->newInstance();
            endif;
        } else {
            $reflection = new ReflectionClass($relation);
            $relation = $reflection->newInstance();
        }

        $dbal = new DBAL();
        return $dbal->belongto($this, $relation);
    }

    /**
     * @param $relation
     * @param bool $recursif
     * @return $this | QueryBuilder
     * @throws ReflectionException
     */
    public function __hasone($relation, $id_lang = null, $get = true)
    {
        if (!is_object($relation)) :
            $reflection = new ReflectionClass($relation);
            $relation = $reflection->newInstance();
        endif;

        $qb = new QueryBuilder($relation);
        $qb->setLang($id_lang);
        if ($get)
            return $qb->select()->where("this." . strtolower(get_class($this)) . "_id", $this->getId())->getInstance();

        return $qb->select()->where("this." . strtolower(get_class($this)) . "_id", $this->getId());
    }

    public function hydrate($idlang = false)
    {
        if (!$this->id || $this->dvfetched)
            return $this;

        if (!$this->dvid_lang)
            $this->dvid_lang = $idlang;

        global $em;
        $classlang = (get_class($this));
        $metadata = $em->getClassMetadata( $classlang);

        //dv_dump($metadata);
        $tablename = $metadata->table["name"];
        $fieldNames = $metadata->fieldNames;
        $assiactions = array_keys($metadata->associationMappings);

        if ($this->dvtranslate && $this->dvid_lang) {
            $columns = "{$classlang}_lang.`" . implode("`, {$classlang}_lang.`", $this->dvtranslated_columns) . "`";
            $sql = " SELECT {$classlang}.*, $columns FROM `$classlang` , {$classlang}_lang 
        WHERE {$classlang}_lang.lang_id = {$this->dvid_lang} AND {$classlang}_lang.{$classlang}_id = {$this->id} AND `{$classlang}`.id = " . $this->id;
        }else
            $sql = " SELECT * FROM `$tablename` WHERE id = " . $this->id;
        // dv_dump($sql);
        $data = (new DBAL($this))->executeDbal($sql, [], DBAL::$FETCH);
        //var_dump($classlang." - ".$attribut, $data, $fieldNames);
        foreach ($fieldNames as $k => $val) {
            $this->{$k} = $data[$k];
        }
        if ($this->dvtranslate && $this->dvid_lang) {
            foreach ($this->dvtranslated_columns as $val) {
                $this->{$val} = $data[$val];
            }
        }
        foreach ($assiactions as $k) {
            //if(isset($data[$k]))
            $this->{$k}->id = $data[$k . "_id"];
            $this->{$k . "_id"} = $data[$k . "_id"];
        }

        if ($this->dvtranslate && !$this->dvid_lang) {
            (new DBAL($this))->getLangValues($this, $this->dvtranslated_columns);
        }
        $this->dvfetched = true;
        // une COPIE de l'etat lu : une reference vers $this rendrait toute
        // modification ulterieure invisible pour entityKeyUpdate()
        $this->dvold = clone $this;
        $this->dvpersistence = [];
        return $this;
    }

 /*   public function hydrateData(
        $flowBD, $id_lang, $table, $custom_column_keys, $with){

        foreach ($this->dvtranslated_columns as $append)
            if (isset($flowBD[$append]))
                $this->{$append} = $flowBD[$append];

        foreach ($custom_column_keys as $custom) {
            $this->{$custom} = $flowBD[$custom];
        }

        foreach ($this as $key => $value) {


            if (array_key_exists($key."_id", $flowBD)) {

                $this->{$key . "_id"} = $flowBD[$key."_id"];
                if (!$flowBD[$key."_id"] || !$value) {
                    if (array_key_exists($key, $flowBD))
                        $this->{$key} = $flowBD[$key];
                    continue;
                }

                $value->setId($flowBD[$key."_id"]);
                $value->dvid_lang = $id_lang;
                $value->hydrateMatch($flowBD, $key);
                $this->{$key} = $value;

            }

            elseif (isset($flowBD[$key]))
                $this->{$key} = $flowBD[$key];

        }

        return $this;
    }*/

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function scan_entity_core()
    {
        return Core::__extract($this);
    }

    public function __construct($id = null)
    {
        $this->id = $id;

    }

    public function singlelang($value)
    {

        if (is_numeric($value))
            $iso = \Dvups_lang::getattribut("iso_code", $value);
        else
            $iso = $value;

        foreach ($this->dvtranslated_columns as $att){
            if (is_array($this->{$att}))
                $this->{$att} = $this->{$att}[$iso];
        }

    }

    /**
     * @return string
     */
    public function getCreatedAt($format = "d/m/Y")
    {
        if (!$format)
            return $this->created_at;

        return date($format, strtotime($this->created_at));
    }
    /**
     * @return float
     */
    public function getTimeStamp($attribute = "created_at", $precision = 1000 )
    {
        return  (new DateTime($this->{$attribute}))->getTimestamp() * $precision;
    }

    /**
     * @param string $created_at
     */
    public function setCreatedAt($created_at)
    {
        if ($created_at)
            $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getUpdatedAt($format = "")
    {
        if (!$format)
            return $this->updated_at;

        return date($format, strtotime($this->updated_at));
    }

    /**
     * @param string $updated_at
     */
    public function setUpdatedAt($updated_at)
    {
        if ($updated_at)
            $this->updated_at = $updated_at;
    }

    /**
     * @return string
     */
    public function getDeletedAt($format = "")
    {
        if (!$format)
            return $this->deleted_at;

        return date($format, strtotime($this->deleted_at));
    }

    /**
     * @param string $deleted_at
     */
    public function setDeletedAt($deleted_at)
    {
        $this->deleted_at = $deleted_at;
    }

    /**
     * Get value of entity X in $_POST global variable from submited form
     * @param string $formfeild
     * @return mixed
     */
    // todo: implement namespace for model class
//    public static function getvalue($formfeild){
//
//        $classname = strtolower(get_called_class());
//        return $_POST[$classname."_form['$formfeild']"];
//
//    }

    const split = ";";

    /**
     * map data to the specify model from the webservice call's
     * @param array $cutom_data an array (key -> value) of callback
     * @return array
     * @example $custom = [
     * "show"=>function(){ return $this->srcImage();}
     * ];
     */
    public function apimapper($cutom_data = [])
    {
        $datareturn = [];
        $rawdata = \Request::raw();
        $class = strtolower(get_class($this));
        if (isset(Request::$uri_raw_param["dataset"])) {
            if (isset(Request::$uri_raw_param["dataset"][$class])) {

                foreach ($this as $key => $value) {
                    if (in_array($key, Request::$uri_raw_param["dataset"][$class])) {
                        $datareturn[$key] = $value;
                    }
                }

                foreach ($cutom_data as $key => $value) {
                    if (!is_numeric($key)) {
                        if (is_callable($value)) {
                            $datareturn[$key] = $value();
                            continue;
                        }
                    } else
                        $key = $value;

                    if (in_array($key, Request::$uri_raw_param["dataset"][$class])) {
                        $method = 'get' . ucfirst($key);
                        if (method_exists($this, $method) && $result = call_user_func(array($this, $method))) {
                            $datareturn[$key] = $result;
                        } else {
                            $datareturn[$key] = null;
                        }
                    }
                }

                return $datareturn;
            }
        }

        return $datareturn;
    }

    /**
     * create entity row and return id(s) of row(s)
     * @param ...$keyvalues
     * @return array|int|mixed
     */
    public static function create(...$keyvalues)
    {
        $ids = [];
        foreach ($keyvalues as $keyvalue) {
            $keyvalue["created_at"] = date("Y-m-d H:i:s");
            self::$keyvalues = $keyvalue;
            $classname = get_called_class();
            $ids[] = DBAL::_createDbal($classname, $keyvalue);
        }
        if (count($ids) == 1)
            return $ids[0];

        return $ids;
    }

    public static function createInstance(...$keyvalues)
    {
        $ids = self::create($keyvalues[0]);
        if (is_array($ids)) {
            $instances = [];
            foreach ($ids as $id) {

            }
        }
        return self::find($ids);
    }

    /**
     * update a part or an entire entity
     * @example http://easyprod.spacekola.com description
     * @param Mixed $arrayvalues
     * @param Mixed $seton
     * @param Mixed $case id
     * @return \QueryBuilder
     */
    public static function update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();
        if ($seton != null && is_array($arrayvalues) || $case != null && !is_array($case))
            $entity->setId($case);

        $qb = new QueryBuilder($entity);
        return $qb->update($arrayvalues, $seton, $case, $defauljoin);
    }

    public function hasRelation($name)
    {
        $keys = [];
        foreach ($this as $key => $val) {
            if (in_array($key, self::$dvkeys))
                continue;
            if (is_object($val) && $name == $key)
                return $val;
        }
        return false;
    }

    public static function link($path = "/index")
    {
        $entity = strtolower(get_called_class());
        $admin = getadmin();
        $de = Dvups_role_dvups_entity::where($admin->dvups_role)->andwhere("dvups_entity.name", $entity)->getInstance();

        if ($de->getId()) {
            return $de->dvups_entity->route($path);
        }
        return "#";

    }

    /**
     * @return Dvups_entity
     */
    public static function getDvupsEntity()
    {
        $entity = strtolower(get_called_class());
        return $entity;
//        return Dvups_entity::getbyattribut("this.name", $entity, false);

    }

    public static function getStatusWhereKey($key){
        $classname = get_called_class();
        return Status::getStatus($key, strtolower($classname));
    }

    public static function groupKeysByPrefix(array $data, array $prefixes): array {
        $result = [];

        foreach ($data as $key => $value) {
            foreach ($prefixes as $prefix) {
                $prefixWithUnderscore = $prefix . '_';
                if (str_starts_with($key, $prefixWithUnderscore)) {
                    $subKey = substr($key, strlen($prefixWithUnderscore));
                    $result[$prefix][$subKey] = $value;
                    continue 2; // on passe à la prochaine clé du tableau
                }
            }

            // Si aucun des préfixes ne match, on garde la clé dans le tableau principal
            $result[$key] = $value;
        }

        return $result;
    }

}
