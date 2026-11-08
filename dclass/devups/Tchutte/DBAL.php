<?php

use dclass\devups\Tchutte\DB_dumper;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * Description of DBAL 3.1.0 *
 * Abstraction database layer implement object \DateTime structire
 * nullable value for many to one relation
 *
 * @author Atemkeng Azankang
 */
class DBAL extends Database
{
    /**
     * Cle UNIQUE du cache de connexion
     * ($pdocache global). Avant ce correctif, la cle etait $this->objectName
     * (nom de l'entite passee au constructeur) - `new DBAL()` sans argument
     * (chemin de Model::__insert()/__update()) tombait sur la cle `null`,
     * tandis que `new QueryBuilder($entity)` (chemin de Model::find(),
     * QueryBuilder etend DBAL et transmet l'entite au parent) tombait sur le
     * nom de la classe reelle - autant de cles que d'entites lues plus une
     * pour toutes les ecritures confondues, verifie empiriquement par
     * spl_object_id() : Agency::find() et Service::find() donnaient deja
     * deux connexions PDO distinctes entre elles, et une troisieme distincte
     * pour toute ecriture. Une seule connexion pour tout le projet : la cle
     * ne doit plus jamais varier avec l'entite traitee.
     */
    private const SHARED_CONNECTION_KEY = '__dbal_shared_connection__';

    protected $defaultjoin = "";
    public $custom_columns = "";
    public $custom_columns_array = [];
    public $custom_column_keys = [];
    protected $collect = [];
    public $_join_table = [];
    public $_with = [];
    public $_without = [];
    public $_with_lang = [];
    public $_query_values = [];
    public $_query_params = [];
    /**
     *
     * @var type
     */
    protected $object;
    protected $classmetadata;

    /**
     *
     * @var string
     */
    protected $objectName;
    protected $table;

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    protected $instanceid;

    protected $cache_key;
    /**
     *
     * @var type
     */
    protected $objectVar;
    /**
     *
     * @var type
     */
    protected $id_lang;
    public static $id_lang_static;

    /**
     *
     * @var type
     */
    protected $objectValue;

    /**
     *
     * @var type
     */
    protected $nbVar;

    /**
     * collection d'objet utilisé dans les relations de n:n et dans les relations 1:n bidirectionnelles
     * @var type
     */
    protected $listeEntity;
    protected $objectCollection;

    /**
     *
     * @var type
     */
    protected $softdelete = false;
    protected $dvtrashed = false;

    /**
     *
     * @var type
     */
    private $select;

    /**
     *
     * @var type
     */
    private $en;

    /**
     * liste des entités en relation de 1:n et 1:1
     * @var type
     */
    protected $entity_link_list;
    protected $fieldNames;
    protected $associations;
    private $iterat;
    private $update = false;

    public function __construct($object = null, $id_lang = null)
    {

        $this->id_lang = $id_lang;
        $this->instanciateVariable($object);
        //        echo "DBAL ".$this->objectName;

        global $pdocache;
        if (isset($pdocache[self::SHARED_CONNECTION_KEY]))
            $this->link = $pdocache[self::SHARED_CONNECTION_KEY];
        else {
            parent::__construct();
            $pdocache[self::SHARED_CONNECTION_KEY] = $this->link;
        }
        //        global $em;
        //        $this->em = $em;

    }

    /**
     * Connexion PDO UNIQUE, partagee par toute lecture et toute
     * ecriture passant par DBAL/QueryBuilder/Model - jamais une seconde
     * connexion fabriquee a cote. Doctrine (`getEntityManager()`) ne sert
     * qu'a la lecture de metadonnees (mapping des entites, migrations de
     * schema) : il n'execute aucune requete metier dans ce framework, sa
     * propre connexion reste donc volontairement separee et n'a pas a
     * apparaitre ici.
     *
     * Construit paresseusement au premier appel (comme toute utilisation
     * normale de DBAL), puis relit du meme cache `$pdocache` que le
     * constructeur - un seul et meme objet PDO pour toute la duree de la
     * requete, quel que soit l'appelant (verifie par spl_object_id() depuis
     * plusieurs FrontController et depuis Model::find()/->__insert()).
     */
    public static function pdo(): \PDO
    {
        global $pdocache;
        if (!isset($pdocache[self::SHARED_CONNECTION_KEY])) {
            new self();
        }
        return $pdocache[self::SHARED_CONNECTION_KEY];
    }

    //    public static function getEntityManager() {
    public static function getEntityManager()
    {
        global $enittycollection;

        $global_navigation = Core::buildOriginCore();
        $enittyfoldes = [];
        $enittyfoldes[] = ROOT . "dclass/extends";

        foreach ($global_navigation as $key => $project) {
            if (is_object($project)) {
                foreach ($project->listmodule as $key => $module) {
                    if (is_object($module)) {
                        if (file_exists($rep = ROOT . "src/" . $project->name . "/" . $module->name . "/Entity")) {
                            $enittyfoldes[] = $rep;
                            foreach ($module->listentity as $key => $entity) {
                                if (is_object($entity))
                                    //var_dump($entity);
                                    $enittycollection[strtolower($entity->name)] = ROOT . "src/" . $project->name . "/" . $module->name;
                            }
                        }
                    }
                }
            }
        }

        // Create a simple "default" Doctrine ORM configuration for Annotations
        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration($enittyfoldes, $isDevMode);
        // or if you prefer yaml or XML
        //$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
        //$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);
        // database configuration parameters
        $conn = array(
            'driver' => 'pdo_mysql',
            'dbname' => dbname,
            'user' => dbuser,
            'password' => dbpassword,
            'host' => dbhost,
            'charset' => 'utf8mb4', // T93 : sinon Doctrine cree toute nouvelle table en utf8mb3
            //    'path' => __DIR__ . '/db.sqlite',
        );

        // obtaining the entity manager
        return EntityManager::create($conn, $config);
    }


    public function setCollect(array $collect)
    {
        foreach ($collect as $el) {
            $this->collect[] = str_replace("this.", "`" . $this->table . "`.", $el);
        }
        //$this->collect = $collect;
    }

    protected $em;

    public function is_doctrine_entity($className)
    {
        //        $this->em = $this->getEntityManager();
        return !$this->em->getMetadataFactory()->isTransient("\\" . $className);
    }

    public function getDoctrineMetadata($className)
    {

        if ($this->is_doctrine_entity($className))
            return $this->em->getClassMetadata("\\" . $className);

        return null;
    }

    /**
     * persiste les entités issue d'un attribut en relation de n:n
     *
     * @param integer $id l'id de l'entité proprietère de la relation
     * @return
     */
    private function manyToManyAdd($id, $update = false, $change_collection = [])
    {
        /**
         * on traite chaque attribut de maniere unique, attribut qui est lui aussi une liste d'entité
         */
        $success = true;
        foreach ($this->objectCollection as $index => $listentity) {

            /**
             * on isole chaque entité contenu dans la liste des entités
             */
            $association = true;
            foreach ($listentity as $entity) {

                if (!is_object($entity))
                    continue;

                if (!$entity->getId())
                    break;
                /**
                 * chaque entité est persistée
                 */
                // valeur des attributs de la table
                $values = [];
                $keyvalue = [];
                $entityName = strtolower(get_class($entity));

                if ($this->tableExists($entityName . '_' . $this->objectName)) {
                    $entityTable = $entityName . "_" . $this->objectName;
                    $direction = "ld";
                } elseif ($this->tableExists($this->objectName . "_" . $entityName)) {
                    $entityTable = $this->objectName . "_" . $entityName;
                    $direction = "rl";
                } else {
                    $association = false;
                    $entityTable = $entityName;
                    $direction = "lr";
                }
                $entityTable = strtolower($entityTable);

                /**
                 * on instantie la class $entityTable trop cool
                 */
                $persistecollection = true;

                if ($direction == "lr") {

                    $persistecollection = false;
                    $sql = "update `" . $entityTable . "` set " . $this->table . "_id = $id where id = " . $entity->getId();

                    $query = $this->link->prepare($sql);
                    $success = $query->execute() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $values, $entityTable));
                    $this->link = null;
                } else {

                    $keyvalue[strtolower(get_class($entity)) . "_id"] = $entity->getId();
                    $keyvalue[$this->table . "_id"] = $this->object->getId();
                    DBAL::_createDbal($entityTable, $keyvalue);
                }

                if ($persistecollection) {
                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * cette methode est utilisé lors de l'update et non lors du delete mais il se peut que il ait une
     * moyen de faire son update sans les supprimer. mais je ne l'ai pas encore trouvé donc sa rester une
     * hypothèse
     *
     * @param type $id
     * @return type
     */
    private function manyToManyDelete($id, $change_collection = [])
    {

        $sql = "";
        $success = "";
        if (!$change_collection)
            return true;

        foreach ($change_collection as $i => $listentity) {

            if (!empty($listentity['todrop'])) {

                $entityName = strtolower(get_class($listentity['todrop'][0]));
                $objectarray = (array)$listentity['todrop'][0];
                $arrayvalues = array_values($objectarray);
                foreach ($arrayvalues as $value) {

                    foreach ($listentity['todrop'] as $entity) {
                        if ($this->tableExists($entityName . '_' . $this->objectName)) {
                            $entityTable = strtolower($entityName . "_" . $this->objectName);
                        } elseif ($this->tableExists($this->objectName . "_" . $entityName)) {
                            $entityTable = strtolower($this->objectName . "_" . $entityName);
                        } else {
                            $entityTable = $entityName;
                        }

                        $sql .= "delete from " . $entityTable . "  where " . $entityName . "_id = " . $entity->getId() . " and " . $this->objectName . "_id = $id; ";
                    }
                }
            } else {
                $success = TRUE;
            }
        }

        if ($sql != "") {

            $success = $this->executeDbal($sql);
            //            $success = $query->execute() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
            //            $this->link = null;
        }
        return $success;
    }

    public function belongto($entity, $relation)
    {

        if (is_object($relation)) {
            if ($relation->getId())
                return $relation->__show();
            else {
                $obarray = (array)$entity;
                $relationname = strtolower(get_class($relation));

                if (isset($obarray[$relationname . "_id"]))
                    $id = $obarray[$relationname . "_id"];
                else {
                    return $relation;
                }
            }
        } elseif (!is_object($relation)) {

            $obarray = (array)$entity;
            if (isset($obarray[$relation . "_id"]))
                $id = $obarray[$relation . "_id"];
            elseif (isset($obarray[$relation]))
                $id = $obarray[$relation]->getId();
            else {
                foreach ($obarray as $obkey => $value) {
                    //$key = str_replace(get_class($entity), '', $obkey);
                    //$key = str_replace('*', '', $key);

                    if (is_object($value) && strtolower(get_class($value)) == $relation) {

                        $id = $obarray[$obkey]->getId();
                        break;
                    }
                }
            }

            $reflection = new ReflectionClass($relation);
            $relation = $reflection->newInstance();
        }

        $qb = new QueryBuilder($relation);
        return $qb->select()->where("id", "=", $id)->first();
    }

    public function hasmany($entity, $collection, $exec = true, $incollectionof = null, $id_lang = null)
    {

        $collectionName = strtolower(get_class($collection));

        $this->id_lang = $id_lang;
        if ($incollectionof != null) {

            $qb = new QueryBuilder($collection);
            $qb->select()
                ->where("this.id") //$collectionName .
                ->in(
                    (new QueryBuilder(new $incollectionof))->select($collectionName . "_id")
                        ->where($entity)
                        ->close($qb)
                );

            if ($exec)
                return $qb->get();
            else
                return $qb;
        } else {
            $objectName = strtolower(get_class($entity));

            if ($this->tableExists($collectionName . '_' . $objectName)) {
                $entityTable = $collectionName . '_' . $objectName;
            } elseif ($this->tableExists($objectName . "_" . $collectionName)) {
                $entityTable = $objectName . "_" . $collectionName;
            } elseif ($this->tableExists($collectionName)) {

                $qb = new QueryBuilder($collection);
                $qb->select()
                    ->where($entity);

                if ($exec)
                    return $qb->get();
                else
                    return $qb;
            }
        }

        $tableinstance = ucfirst($entityTable);

        $qb = new QueryBuilder($collection);
        $qb->select()
            ->where($collectionName . ".id")
            ->in(
                (new QueryBuilder(new $tableinstance))->select($collectionName . "_id")
                    ->where($entity)
                    ->close($qb)
            );


        if ($exec)
            return $qb->get();
        else
            return $qb;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function insertBulk($object = null, $listentity = [])
    {
        if ($object)
            $this->instanciateVariable($object);

        $sql = "INSERT INTO `" . $this->table . '` ( `' . implode('` ,`', $this->objectVar) . "`)  VALUES ";
        //$sql = "insert into ".$this->objectName." value ";
        $sql .= implode(",", $this->_query_values) . ';';
        //        dv_dump($sql, $this->_query_params);
        $result = $this->executeDbal($sql, $this->_query_params, self::$INSERT);
        //die(var_dump($sql));
        return $result;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function updateserialiseDbal($table, $var, $arrayvalues)
    {

        if (is_object($table))
            $table = strtolower(get_class($table));

        $ids = array_keys($arrayvalues);
        $sql = "";
        if (count($arrayvalues) == 1) {
            $sql = " update " . $table . " set $var = '" . $arrayvalues[$ids[0]] . "' WHERE id = " . $ids[0] . "; ";
        } else {
            $parameterQuery = "";
            foreach ($arrayvalues as $key => $value) {
                //$parameterQuery .= " WHEN $key THEN $value ";
                $sql .= " update " . $table . " set $var = '" . $value . "' WHERE id = " . $key . "; ";
            }
        }

        return $sql;
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function deleteserialiseDbal($object, $listid)
    {
        if ($object)
            $this->instanciateVariable($object);

        $sql = "DELETE from `" . $this->table . "` WHERE id IN (" . implode(",", $listid) . ")";

        return $sql;
    }

    private $fetch_option = PDO::FETCH_DEFAULT;
    public static $NOTHING = 0;
    public static $INSERT = 1;
    public static $UPDATE = 1.1;
    public static $DELETE = -1;
    public static $FETCHALL = 2;
    public static $FETCHROW = 7;
    public static $FETCHNAMEDS = 8;
    public static $FETCHALLOBJECT = 5;
    public static $FETCHCOLUMN = 6;
    public static $FETCHOBJECT = 3;
    public static $FETCH = 4;

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function executeDbal($sql, $values = [], $action = 0, $force = false)
    {

        try {
            $query = $this->link->prepare($sql);
            $return = $query->execute($values);

            if ($action == self::$NOTHING) {
                // nothing

                if (dbtransaction) {
                    $bd_dump = new DB_dumper();
                    $bd_dump->transaction($this->table, $sql, $values);
                }
            } elseif ($action == self::$FETCH) {

                //                try {
                $return = $query->fetch($this->fetch_option);
                /*} catch (Exception $exception) {
                    if (__prod)
                        return $this->object;

                    die(Bugmanager::getError(__CLASS__, __METHOD__, $exception->getMessage(), $query->errorInfo(), $sql, $values));
                }*/
                //$return = 33;
            } elseif ($action == self::$INSERT) {
                if (count($this->identifier) > 1) {
                    //dump($this->identifier);
                    $return = $this->identifier;
                } else {
                    $req = $this->link->prepare("select @@IDENTITY as id");
                    $req->execute();
                    $id = $req->fetch() or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $values));
                    $return = $id['id'];
                    if ($this->object){
                        $this->object->setId($id['id']);
                        $this->object->dvserialised['id'] = $id['id'];
                    }
                }
                if (dbtransaction) {
                    $bd_dump = new DB_dumper();
                    $bd_dump->transaction($this->table, $sql, $values, $return);
                }
            } elseif ($action == self::$UPDATE) {

                if (dbtransaction) {
                    $bd_dump = new DB_dumper();
                    $bd_dump->transaction($this->table, $sql, $values);
                }
            } elseif ($action == self::$DELETE) {

                if (dbtransaction) {
                    $bd_dump = new DB_dumper();
                    $bd_dump->transaction($this->table, $sql, $values);
                }
            } elseif ($action == self::$FETCHALL) {
                $return = $query->fetchAll($this->fetch_option);
            } elseif ($action == self::$FETCHROW) {

                $return = $query->fetch(PDO::FETCH_NAMED);
            } elseif ($action == self::$FETCHCOLUMN) {

                $return = $query->fetchColumn(); // or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql));
            } elseif ($action == self::$FETCHOBJECT) {
                $return = $query->fetchObject($this->objectName);
            } elseif ($action == self::$FETCHALLOBJECT) {

                $return = $query->fetchAll($this->fetch_option, $this->objectName);
            }

            return $return;
        } catch (\dclass\devups\Tchutte\DBALException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            // Jamais de dump + die : l'appelant (controleur, API, test) doit
            // pouvoir intercepter l'erreur. Hors production, le message porte
            // la requete et ses parametres.
            throw \dclass\devups\Tchutte\DBALException::fromThrowable($exception, $sql, (array)$values);
        } finally {
            // Fermeture de la connexion
            $this->link = null;
        }
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \stdClass $object
     * @return int l'id de l'entité persisté
     */
    public function createDbal($object = null)
    {
        $this->id_lang = self::$id_lang_static;
        if ($object)
            $this->instanciateVariable($object);

        $values = [];
        $parameterQuery = ':' . implode(", :", $this->objectVar);

        $sql = " INSERT INTO `" . $this->table . "` (`" . strtolower(implode('` ,`', $this->objectVar)) . "`) 
        VALUES (" . strtolower($parameterQuery) . ");
        ";

        $id = $this->executeDbal($sql, $this->objectKeyValue, 1);
        // dv_dump($id, $sql, $this->objectKeyValue);

        // implement translation if anabled in class
        if ($this->object->dvtranslate) {

            $objarray = (array)$this->object;

            $langs = Dvups_lang::allrows();

            foreach ($langs as $lang) {

                $keyvalue = [];
                foreach ($this->object->dvtranslated_columns as $key) {
                    if (!isset($objarray[$key]))
                        continue;
                    if (isset($objarray[$key][$lang->getIso_code()]))
                        $keyvalue[$key] = $objarray[$key][$lang->getIso_code()];
                    else
                        $keyvalue[$key] = $id . "_" . $lang->getIso_code();
                    // $this->object->{$key} = $objarray[$key][$lang->getIso_code()];
                }
                $keyvalue["lang_id"] = $lang->getId();
                $keyvalue[$this->table . "_id"] = $id;
                DBAL::_createDbal($this->table . "_lang", $keyvalue);
            }
        }

        if (isset($this->objectCollection) && is_array($this->objectCollection) && !empty($this->objectCollection)) {
            $this->manyToManyAdd($id, false, null);
        }

        //        $eventListners = \dclass\devups\Controller\Controller::$eventListners["after"]["create"];

        /*if (isset($eventListners[$this->objectName])) {
            $methods = $eventListners[$this->objectName];
            foreach ($methods as $method) {
                if (is_array($method))
                    $this->object->{$method[0]}();
                else
                    $this->object->{$method}();
            }
        }*/

        /*if ($this->object->dvtranslate) {
            foreach ($this->object->dvtranslated_columns as $column) {
                if (isset($this->object->{$column}[$this->id_lang]))
                    $this->object->{$column} = $this->object->{$column}[$this->id_lang];
            }
        }
        if ($this->entity_link_list) {
            foreach ($this->object->dvtranslated_columns as $column) {
                if (isset($this->object->{$column}[$this->id_lang]))
                    $this->object->{$column} = $this->object->{$column}[$this->id_lang];
            }
        }*/
        return $id;
        //        return $this->object->getId();
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \string $object
     * @return int l'id de l'entité persisté
     */
    public static function _createDbal($object, $keyvalue)
    {

        global $em;

        $metadata = $em->getClassMetadata("\\" . $object);

        $table = $metadata->table["name"];
        $objectinst = new $object;

        if (isset($objectinst->dvtranslate) && $objectinst->dvtranslate) {
            $objarray = $keyvalue;
            $values = [];

            foreach ($objectinst->dvtranslated_columns as $key) {
                unset($objarray[$key]);
            }

            $id = self::_createAction($table, $objarray, $objectinst);
            $objarray = $keyvalue;
            $langs = Dvups_lang::all();
            foreach ($langs as $lang) {
                $keyvalue = [];
                foreach ($objectinst->dvtranslated_columns as $key) {
                    if (!isset($objarray[$key]))
                        continue;

                    if (isset($objarray[$key][$lang->getIso_code()]))
                        $keyvalue[$key] = $objarray[$key][$lang->getIso_code()];
                    else
                        $keyvalue[$key] = $id . "_" . $lang->getIso_code();
                    // $this->object->{$key} = $objarray[$key][$lang->getIso_code()];
                }
                $keyvalue["lang_id"] = $lang->getId();
                $keyvalue[$table . "_id"] = $id;
                self::_createAction($table . "_lang", $keyvalue, $objectinst);
            }
        } else {
            /*$objectvar = array_keys($keyvalue);
            $parameterQuery = ':' . implode(", :", $objectvar);
            $sql = "INSERT INTO `" . $table . "` (`" . strtolower(implode('` ,`', $objectvar)) . "`) VALUES (" . strtolower($parameterQuery) . ")";

            $db = new DBAL();
            $id = $db->executeDbal($sql, $keyvalue, 1);*/
            $id = self::_createAction($table, $keyvalue, $objectinst);
        }
        return $id;
    }

    private static function _createAction($table, $keyvalue, $objectinst = null)
    {
        $objectvar = array_keys($keyvalue);
        $parameterQuery = ':' . implode(", :", $objectvar);
        $sql = "INSERT INTO `" . $table . "` (`" . strtolower(implode('` ,`', $objectvar)) . "`) ";
        $sql .= " VALUES (" . strtolower($parameterQuery) . ")";

        // todo: avoid instance here due to direct insertion

        //        dv_dump( $objectinst );
        $db = new DBAL($objectinst);
        //        $db->object = $objectinst;
        return $db->executeDbal($sql, $keyvalue, 1);
    }

    /**
     * updateDbal
     * met à jour l'entité passé en parametre. et celon la valeur de $change_collection, la collection d'objet de l'entité en question.
     *
     * @param \stdClass $object l'entité a persister
     * @param array $change_collection Autorise la modification de la collection d'objet en bd true par defaut
     * @return \stdClass
     * @throws Exception
     */
    public function updateDbal($object = null)
    {

        global $_ENTITY_COLLECTION;

        $this->action = 'update';
        if ($object):
            $this->instanciateVariable($object);
        endif;
        //        dv_dump($this->objectVar);
        $parameterQuery = [];
        foreach ($this->objectVar as $var) {
            $parameterQuery[] = ' `' . $var . '`= :' . $var;
        }
        if (count($parameterQuery) === 0)
            throw new \Exception("Empty parameters");

        $sql = " UPDATE `" . $this->table . "` SET " . strtolower(implode(',', $parameterQuery)) . " WHERE 1 ";

        if ($this->identifier) {
            //            for ($i = 0; $i < $this->nbVar; $i++) {
            //                if (in_array($this->objectVar[$i], $this->identifier))
            //                    continue;
            foreach ($this->identifier as $key => $idf) {
                $sql .= " AND $key = :$key ";
                $this->objectKeyValue[$key] = $idf;
            }
        } else {
            $this->objectKeyValue['id'] = $this->instanceid;
            $sql .= " AND id = :id ";
        }
        //        try {

        //            $query = $this->link->prepare($sql);
        //            $result = $query->execute($this->objectKeyValue);
        $result = $this->executeDbal($sql, $this->objectKeyValue, self::$UPDATE);

        // implement translation if anabled in class
        if ($this->object->dvtranslate) {
            $objarray = (array)$this->object;
            if ($this->object->dvid_lang) {
                $parameterQuery = [];
                $keyvalue = [];
                foreach ($this->object->dvtranslated_columns as $key) {
                    if (!isset($objarray[$key]))
                        continue;

                    $parameterQuery[] = ' `' . $key . '`= :' . $key;
                    $keyvalue[$key] = $objarray[$key];
                }
                $this->updateLangValue($keyvalue, $parameterQuery, $this->object->dvid_lang);
            } else {
                //dv_dump($objarray);
                $langs = Dvups_lang::allrows();
                foreach ($langs as $lang) {
                    $parameterQuery = [];
                    $keyvalue = [];
                    foreach ($this->object->dvtranslated_columns as $key) {
                        if (!isset($objarray[$key]))
                            continue;
                        $parameterQuery[] = ' `' . $key . '`= :' . $key;
                        $keyvalue[$key] = $objarray[$key][$lang->getIso_code()];
                    }
                    $this->updateLangValue($keyvalue, $parameterQuery, $lang->getId());
                }
            }
        }

        $this->manyToManyDelete($this->objectValue[0], $_ENTITY_COLLECTION);

        if (isset($this->objectCollection) && is_array($this->objectCollection) && !empty($this->objectCollection)) {

            $this->manyToManyAdd($object->getId(), false, null);
        }

        return $result;
    }

    protected function updateLangValue($keyvalue, $parameterQuery, $id_lang)
    {
        //

        $keyvalue["lang_id"] = $id_lang;
        $keyvalue[$this->table . "_id"] = $this->object->getId();

        $sql = "UPDATE {$this->table}_lang SET  " . implode(",", $parameterQuery) . " 
                WHERE lang_id = :lang_id AND {$this->table}_id = :{$this->table}_id ";

        (new DBAL())->executeDbal($sql, $keyvalue, DBAL::$NOTHING);
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \string $object
     * @return int l'id de l'entité persisté
     */
    public static function _updateDbal($object, $keyvalue, $where = '')
    {

        $parameterQuery = [];
        $objectvar = array_keys($keyvalue);
        foreach ($objectvar as $v)
            $parameterQuery[] = '`' . $v . '` = :' . $v;

        $sql = " UPDATE `" . strtolower($object) . "` SET " . strtolower(implode(', ', $parameterQuery)) . " WHERE 1 $where ";

        $db = new DBAL();
        return $db->executeDbal($sql, $keyvalue, 1);
    }

    /**
     * createDbal
     * persiste les entités en base de données.
     *
     * @param \string $object
     * @return int l'id de l'entité persisté
     */
    public static function _deleteDbal($object, $where = "1")
    {

        $values = [];
        //        $objectvar = array_keys($keyvalue);
        //        $parameterQuery = ':' . implode(", :", $objectvar);

        $sql = " DELETE FROM `" . strtolower($object) . "` WHERE " . $where;

        $db = new DBAL();
        return $db->executeDbal($sql, [], 1);
    }

    /**
     * findAllDbal
     * returne toutes les occurences de l'entite en bd
     *
     * @return array
     */
    public function findAllDbal($critere = "")
    {
        $sql = 'select * from `' . $this->table . '` ' . $critere;
        $query = $this->link->prepare($sql);
        $query->execute();
        $flowBD = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);

        return $flowBD;
    }

    /*protected function initdefaultjoin()
    {
        if (!empty($this->entity_link_list)) {
            $entity_links = array_keys($this->entity_link_list);
            foreach ($entity_links as $entity_link) {
                $class_attrib = explode(":", $entity_link);
                if ($class_attrib[0] != $class_attrib[1])
                    $this->defaultjoin .= " left join `" . $class_attrib[0] . "` " . $class_attrib[1] . " on " . $class_attrib[1] . ".id = " . $this->table . "." . $class_attrib[1] . "_id";
                else
                    $this->defaultjoin .= " left join `" . $class_attrib[0] . "` on " . $class_attrib[0] . ".id = " . $this->table . "." . $class_attrib[0] . "_id";
            }
        }
    }*/

    public function findByIdDbal($object = null, $recursif = true, $collection = false, $id_lang = null)
    {
        $this->defaultjoin = "";
        if ($object):
            $this->instanciateVariable($object);
        endif;
        $join = "";
        $columns = "{$this->table}.`" . implode("`, {$this->table}.`", $this->objectVar) . "`";
        $where = ' where `' . $this->table . '`.' . $this->objectVar[0] . ' = ? ';
        if ($id_lang)
            $this->id_lang = $id_lang;
        if ($object->dvtranslate && $this->id_lang) {

            $thislang = $this->table . "_lang";
            $columns .= ", `" . $thislang . "`.`" . implode("`, $thislang.`", $object->dvtranslated_columns) . "`";
            $join .= " left join $thislang on $thislang.{$this->table}_id = `{$this->table}`.id ";
            $where .= " and $thislang.lang_id = {$this->id_lang} ";
        }

        $select = "select $columns from `" . $this->table . '`';

        if ($this->softdelete)
            $where .= ' and `' . $this->table . '`.deleted_at is null ';

        return $this->__findOne($select . $join . $where, array($this->objectValue[0]), $collection, $recursif);
    }

    public function deleteDbal($object = null, $force = false)
    {

        if ($object):
            $this->instanciateVariable($object);
        endif;

        if ($this->softdelete && $force == false)
            $sql = "UPDATE `" . $this->table . "` SET deleted_at = NOW() WHERE id = ?";
        else
            $sql = "DELETE FROM `" . $this->table . "` WHERE id = ?";

        return $this->executeDbal($sql, [$this->instanceid], self::$DELETE);
    }

    protected function __count($sql, $values = [])
    {

        return $this->executeDbal($sql, $values, self::$FETCHCOLUMN);
        $query = $this->link->prepare($sql);
        $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $values));

        return $query->fetchColumn();
    }


    private function extractNestedData(string $className, array $data): array
    {
        $prefix = strtolower($className) . '_';
        $filteredData = [];

        foreach ($data as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $filteredData[substr($key, strlen($prefix))] = $value;
            }
        }

        return $filteredData;
    }

    private function dbrow($flowBD, $global_config = [])
    {

        // if ($this->object->dvtranslate && !$this->id_lang) {
        //     $flowBD = (object)$flowBD;
        //     $this->getLangValues($flowBD, $this->object->dvtranslated_columns);
        //     $flowBD = (array)$flowBD;
        // }

        $classname = get_class($this->object);
        $instance = new $classname;

        //        if ($this->table == 'postimage')
        $flowBD = $instance->hydrateData(
            $flowBD,
            $this->id_lang,
            $this->_without,
            $this->custom_column_keys,
            $this->_with_lang
        );

        foreach ($this->_with as $entity => $columns) {
            if (!$columns || $columns === '*' || is_callable($columns)) {
                // todo: why not do this : $classname = get_class($this->object->{$entity});
                extract(EntityCore::parseEntity($entity));
                $cname = $entity;
                $entity = $classentity;
                if (!isset($global_config[ucfirst($entity)]))
                    continue;
                //                $entity = $global_config[ucfirst($entity)];
                //                $flowBD->{"$entity"} = $global_config[ucfirst($entity)]['namespace_name']::find($flowBD->{$entity . "_id"}, $this->id_lang);

                if (is_callable($columns)) {
                    $entity_config = $global_config[ucfirst($classentity)];
                    $entity_magic = $entity_config['namespace_name'];
                    if($cname != $entity)
                        $valuedata = $columns($flowBD);
                    else if (isset($flowBD->{$entity . "_id"}))
                        $valuedata = $columns($entity_magic::whereId($flowBD->{$entity . "_id"}));
                    else
                        $valuedata = $columns($entity_magic::where($this->objectName . "_id", $flowBD->{"id"}));
                } else {
                    $entity_magic = $global_config[ucfirst($classentity)]['namespace_name'];
                    if ($flowBD->{$entity . "_id"}) {
                        $q = $entity_magic::whereId($flowBD->{$entity . "_id"});

                        foreach ($relations as $relation => $cols) {

                            $q->with($relation, $cols);
                        }
                        $valuedata = $q->first($this->id_lang);
                    } else
                        $valuedata = new $entity_magic();
                    // dv_dump($entity, $valuedata);
                }
                $flowBD->{"$cname"} = $valuedata;
                $flowBD->dvserialised[$cname] = $flowBD->{"$cname"};
            }
            ///$flowBD->{"$entity"}->hydrate($this->id_lang);
        }

        $flowBD->dvold = clone $flowBD;
        //        foreach ($this->_with as $entity){
        //            $flowBD->{"$entity"}->hydrate($this->id_lang);
        //        }

        $flowBD->dvfetched = true;
        $flowBD->dvinrelation = true;
        if ($this->object->dvtranslated_columns) {
            $flowBD->dvtranslate = true;
            $flowBD->dvtranslated_columns = $this->object->dvtranslated_columns;
        }
        $flowBD->dvpersistence = [];
        return $flowBD;
    }

    /**
     * Return the row of the database map with the object.
     *
     * @param String $sql
     * @param Array $values
     * @return Object
     */
    protected function __findOneRow($sql, $values = [])
    {

        if (empty($this->entity_link_list) and empty($this->objectCollection)) {

            $flowBD = $this->executeDbal($sql, $values, self::$FETCHOBJECT);
            if (!$flowBD)
                return null;

            if ($this->object->dvtranslate) {
                $this->getLangValues($flowBD, $this->object->dvtranslated_columns);
                $flowBD->dvtranslated_columns = $this->object->dvtranslated_columns;
            }

            $flowBD->dvfetched = true;
            $flowBD->dvold = clone $flowBD;
            return $flowBD;
        }

        $this->fetch_option = PDO::FETCH_NAMED;
        $flowBD = $this->executeDbal($sql, $values, self::$FETCH);

        if (!$flowBD) {
            return null;
        }

        global $global_config;
        return $this->dbrow($flowBD, $global_config);
    }

    public function getLangValues(&$flowBD, $columns)
    {

        $flowBD->dvid_lang = $this->id_lang;
        if ($this->id_lang)
            return null;

        // var_dump($flowBD->id);
        $sql = " SELECT a." . implode(', a.', $columns) . ", dl.iso_code FROM {$this->table}_lang a
            LEFT JOIN  dvups_lang dl ON dl.id = a.lang_id
            WHERE {$this->table}_id = {$flowBD->id} ";

        $values = (new DBAL())->executeDbal($sql, [], DBAL::$FETCHALL);

        /*$sql = " SELECT * FROM dvups_lang  ";
        $langs = (new DBAL())->executeDbal($sql, [], DBAL::$FETCHALL);*/

        // this code add dvserialised in data of entities with dvtranslate = true
        // but it is important for deep hydrate when entity with dvtranslate = true is an imbricated entity
        // find the way to handle it better.
        foreach ($columns as $item) {
            $flowBD->{$item} = [];
            // dvserialised[$item] must be reset too: the main select already aliases
            // a GROUP_CONCAT('[iso_code]value...') string into this same column name,
            // so without this reset the per-lang merge below tries to offset-write
            // into that string instead of a fresh array.
            $flowBD->dvserialised[$item] = [];

            if ($values)
                foreach ($values as $value) {
                    $flowBD->{$item}[$value['iso_code']] = $value[$item];
                    $flowBD->dvserialised[$item][$value['iso_code']] = $value[$item];
                }
            else
                foreach (['fr', 'en'] as $value) {
                    $flowBD->{$item}[$value] = "";
                    $flowBD->dvserialised[$item][$value] = "";
                }/*foreach ($langs as $value) {
                    $flowBD->{$item}[$value['iso_code']] = "";
                }*/
        }
    }

    /**
     * return entire entity with all linked one
     *
     * @param type $sql
     * @param type $values
     * @param type $collection
     * @param type $recursif
     * @return type
     */
    protected function __findOne($sql, $values = [], $collection = false, $recursif = true)
    {

        //        $req = $this->link->prepare($sql);
        //        $req->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $req->errorInfo(), $sql, $values));

        //        $arrayReturn = $this->listeEntity;

        if (empty($this->entity_link_list) and empty($this->objectCollection))
            $flowBD = $this->executeDbal($sql, $values, self::$FETCHOBJECT);
        //            $flowBD = $req->fetchObject($this->objectName);
        /*elseif ($arrayReturn) {
            $flowBD = $this->executeDbal($sql, $values, self::$FETCHROW);
            $flowBD = $this->djoin($flowBD, $this->object, true, $recursif);
        }*/
        else {

            $this->fetch_option = PDO::FETCH_NAMED;
            $rows = $this->executeDbal($sql, $values, self::$FETCH);
            $flowBD = $this->djoin($rows, $this->object, $collection, $recursif);
        }
        if (!$flowBD)
            $flowBD = $this->object;

        $flowBD->dvfetched = true;

        return $flowBD;
    }


    /**
     * Return array of base entity
     *
     * @param type $sql
     * @param type $values
     * @return type
     */
    protected function __findAllRow($sql, $values = [], $callbackexport = null)
    {
        /*$result = [];
        try {*/

        //            $query = $this->link->prepare($sql);
        //            $query->execute($values) or die(Bugmanager::getError(__CLASS__, __METHOD__, __LINE__, $query->errorInfo(), $sql, $values));

        if (is_callable($callbackexport)) {
            $this->fetch_option = PDO::FETCH_NAMED;
            $rows = $this->executeDbal($sql, $values, self::$FETCHALL);
            foreach ($rows as $row) {
                $callbackexport($row, $this->objectName);
            }
            return true;
        }

        //            if (empty($this->entity_link_list) and empty($this->objectCollection))
        //                return $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);

        $this->fetch_option = PDO::FETCH_NAMED;
        $rows = $this->executeDbal($sql, $values, self::$FETCHALL);

        return $rows;
        /* } catch (Exception $exception) {

             dv_dump($exception, $query->errorInfo(), $sql, $values);
             return [];
         }*/
    }

    /**
     * Return array of base entity
     *
     * @param type $sql
     * @param type $values
     * @return type
     */
    protected function __findAll($sql, $values = [], $callbackexport = null)
    {
        $result = [];

        if (is_callable($callbackexport)) {

            $this->fetch_option = PDO::FETCH_NAMED;
            $rows = $this->executeDbal($sql, $values, self::$FETCHALL);
            foreach ($rows as $row) {
                $callbackexport($row, $this->objectName);
            }
            return true;
        }

        /* if (empty($this->entity_link_list) and empty($this->objectCollection) && !$this->object->dvtranslate) {
             $this->fetch_option = PDO::FETCH_CLASS;
             return  $this->executeDbal($sql, $values, self::$FETCHALLOBJECT);
             foreach ($data as $i => $row) {
                 $data[$i]->dvserialised = $row;
             }

             dv_dump($data);
             return $data;
 //            return $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);
         }*/

        //        $rows = $query->fetchAll(PDO::FETCH_GROUP);

        $this->fetch_option = PDO::FETCH_NAMED;
        $rows = $this->executeDbal($sql, $values, self::$FETCHALL);

        global $global_config;
        foreach ($rows as $row) {
            $result[] = $this->dbrow($row, $global_config);
        }

        return $result;
    }

    protected function __cursor($sql, $values, $callback = null, $return_collection = false)
    {

        if ($this->cache_key) {
            global $devups_cache;
        }

        try {

            $query = $this->link->prepare($sql);
            $query->execute($values);
        } catch (\Throwable $e) {
            throw \dclass\devups\Tchutte\DBALException::fromThrowable($e, $sql, (array)$values);
        }

        if (is_callable($callback)) {
            $retour = [];
            $i = 0;
            $arg = \DClass\lib\Util::get_func_argNames($callback);

            while ($row = $query->fetch(PDO::FETCH_NAMED)) {
                $row = $this->dbrow($row);
                //$row = $this->djoin($row, $this->object, false, false);

                if (count($arg) > 1)
                    $callback($row, $i);
                else
                    $callback($row);

                if ($this->cache_key) {
                    $devups_cache[$this->cache_key][] = $row;
                }
                if ($return_collection)
                    $retour[] = $row;

                $i++;
            }
            //            while ($row = $query->fetch(PDO::FETCH_OBJ)) {
            //                $callback($row);
            //            }
            if (!$return_collection)
                return;
        } elseif (empty($this->entity_link_list)) {
            $this->fetch_option = PDO::FETCH_CLASS;
            return $this->executeDbal($sql, $values, self::$FETCHALLOBJECT);
            //            $retour = $query->fetchAll(PDO::FETCH_CLASS, $this->objectName);
        } elseif ($arraybd = $query->fetchAll(PDO::FETCH_NAMED)) {
            return $arraybd;
        } else
            $retour = array();

        //            $this->ResetObject();
        return $retour;
    }


    private function inarray4($flowvalue)
    {
        $return = null;
        for ($i = 0; $i < count($flowvalue); $i++) {
            if (isset($flowvalue[$i])) {
                $return = $flowvalue[$i];
                unset($flowvalue[$i]);
                break;
            }
        }
        return $return;
    }

    /**
     * orm v_3.0
     * methode recurcivi qui permet de recreer les entités imbriques dans l'entité courantes
     * prend en parametre un tableau qui est le résultat d'une requete avec jointure et retourne
     * l'entité souhaité.
     * la version actuelle ne peut traiter que la premiere couche d'imbrication mais c'est deja un bon debut
     *
     * @param type $flowBD
     * @param type $object
     * @param type $entity_link_list
     * @param type $arrayReturn
     * @param type $list
     * @param type $recursif
     *
     * @return type
     */


    protected $iteration = 0;
    protected $limit_iteration = 0;

    private function orm($flowBD, $object, $imbricateindex = 0, $recursif = true, $collection = false)
    {

        $object_array = (array)$object;
        if ($this->object->dvtranslated_columns)
            $object_array += array_combine($this->object->dvtranslated_columns, $this->object->dvtranslated_columns);

        /*if(get_class($object) == "Enterprise")
            die(var_dump($object_array));*/

        if ($this->limit_iteration != 0 && $this->iteration >= $this->limit_iteration) {

            $object_array["dvinrelation"] = true;

            return $object_array;
        }

        foreach ($object_array as $key => $value) {
            //                    $imbricateindex = 0;

            $k = str_replace(get_class($object), '', $key);
            $k = str_replace('*', '', $k);
            $k2 = substr($k, 2);

            foreach ($flowBD as $key2 => $value2) {

                if (is_object($value)) {
                    // object imbricate
                    $innerrecursif = $recursif;

                    $attclassname = strtolower(get_class($value));

                    if (
                        isset($this->entity_link_list[$attclassname . ":" . $key])
                        && $key == substr($key2, 0, -3)
                        //&& $this->entity_link_list[$attclassname.":".$key] == $attclassname.":".substr($key2, 0, -3)
                    ) {
                        //if (strtolower(get_class($value)) . '_id' == $key2) {
                        /*if($attclassname.":".$key == "tree_item:enterprise_type"){ //  && $key2 == "enterprise_type_id"
                            die(var_dump($key, $flowBD, $flowBD[$key."_id"], $attclassname, $attclassname.":".substr($key, 0, -3)));
                        }*/
                        if (!empty($this->collect)) {
                            $el = strtolower(get_class($object)) . "." . strtolower(get_class($value));
                            if (!in_array($el, $this->collect)) {
                                //break;
                                $innerrecursif = false;
                            }
                        }
                        if (is_array($flowBD[$key2])) {

                            $imbricateindex++;
                            $this->iteration++;
                            $value->setId($this->inarray4($flowBD[$key2]));

                            if ($innerrecursif)
                                $object_array[$key] = $this->findByIdDbal($value);
                            else
                                $object_array[$key] = $value;
                        } else {

                            $this->iteration++;
                            $value->setId($flowBD[$key . "_id"]);
                            if ($innerrecursif)
                                $object_array[$key] = $this->findByIdDbal($value);
                            else
                                $object_array[$key] = $value;
                        }

                        $this->instanciateVariable($object);
                        break;
                    }
                } elseif (is_array($value)) {
                    if ($collection)
                        $object_array[$key] = $object->__hasmany(strtolower(get_class($value[0])));
                    else
                        $object_array[$key] = $value;

                    break;
                } else {
                    if ($k2 == $key2 || $key == $key2) {
                        if (is_array($flowBD[$key2])) {
                            //dv_dump($flowBD[$key2]);
                            $object_array[$key] = $flowBD[$key2];
                        } else {
                            $object_array[$key] = $flowBD[$key2];
                        }

                        break;
                    }
                }
            }
        }

        $object_array["dvinrelation"] = true;

        return $object_array;
    }

    /**
     *
     * @param type $flowBD les données de la bd extrait par PDO avec le parametre PDO::FETCH_NAMED
     * @param type $object the instance of the entity
     * @param Boolean $collection
     * @param Boolean $recursif weither or not the requeste should go deeper in finding entity relation.
     * @return type
     */
    private function djoin($flowBD, $object, $collection = false, $recursif = true)
    {

        if (!is_array($flowBD)) {
            return null;
        }

        if ($this->object->dvtranslate) {
            $flowBD = (object)$flowBD;
            $this->getLangValues($flowBD, $this->object->dvtranslated_columns);
            $flowBD = (array)$flowBD;
        }

        $object_array = $this->orm($flowBD, $object, 0, $recursif, $collection);

        return Bugmanager::cast((object)$object_array, get_class($object));
    }

    public $hasrelation = false;
    public $objectKeyValue = [];
    public $identifier = [];
    public $action = '';
    public $has_multiple_identifier = false;

    public function setClassname($objectname)
    {

        $array = explode("\\", $objectname);
        $this->objectName = $array[count($array) - 1];
        $this->table = strtolower($this->objectName);
        return $this;
    }

    private function mappingProd($object, $objectarray)
    {

        global $em;

        $this->object = $object;
        $this->softdelete = $object->dvsoftdelete;

        $this->objectName = get_class($object);

        $metadata = $em->getClassMetadata("\\" . $this->objectName);

        $this->table = $metadata->table["name"];

        $this->fieldNames = $metadata->fieldNames;
        $this->associations = array_keys($metadata->associationMappings);

        if ($this->action === 'update') {
            $keys = $object->entityKeyUpdate($this->fieldNames, $this->associations, $metadata->identifier);

            if (count($metadata->identifier) > 1)
                foreach ($metadata->identifier as $ident) {
                    //                    if (key_exists($ident . '_id', $keys))
                    $this->identifier[$ident . '_id'] = $object->{$ident . '_id'};
                    //                    $keys[$ident . '_id'] = $object->{$ident . '_id'};
                }
            else {
                //                $keys["id"] = $object->id;
                $this->instanceid = $object->id;
            }
        } else {
            $keys = $object->entityKey($this->fieldNames, $this->associations, $this->_without);

            $this->identifier = [];
            if (key_exists("id", $keys))
                $this->instanceid = $keys["id"];
            else {
                foreach ($metadata->identifier as $ident) {
                    if (key_exists($ident . '_id', $keys))
                        $this->identifier[$ident . '_id'] = $keys[$ident . '_id'] ?? null;
                }
            }
        }

        foreach ($metadata->associationMappings as $key => $maps) {

            //var_dump($val);
            $classnamespace = explode("\\", $maps['targetEntity']);
            $classnamespace = strtolower($classnamespace[count($classnamespace) - 1]);
            $this->entity_link_list[($classnamespace . ":" . $key)] = $objectarray[$key];
            // fix issue while creating an entity then try to access a foreign key user this->key_id. previously throw an exception
            // $this->object->{$key . '_id'} = $val->getId();

        }

        foreach ($object->dv_collection as $key) {

            $val = $object->{$key};
            if (is_array($val))
                $this->objectCollection[] = $val;
        }

        $this->objectVar = array_keys($keys);
        $this->objectValue = array_values($keys);
        $this->objectKeyValue = $keys;
        $this->hasrelation = !empty($this->entity_link_list);
        $this->nbVar = count($this->objectVar);
    }

    /**
     * methode qui initialise les variables d'instance. elle est notament utilisé pour permetre de persister
     * des entités en utilisant directement les methodes du dbal sans passé par le dao comme ça se faisait
     * avant.
     *
     * @param type $object
     */
    protected function instanciateVariable($object)
    {
        global $em;

        $this->entity_link_list = [];
        $this->listeEntity = [];
        $this->objectCollection = [];
        $this->objectKeyValue = [];
        $this->select = false;
        if (is_object($object)) {
            $objectarray = (array)$object;
            $this->mappingProd($object, $objectarray);
        }
    }

    /**
     * Réinitialise l'instance du DBAL
     * c'est notament utile pour la persistance des objets avec des attributs null dans le cas d'une relation 1:n
     */
    private function ResetObject()
    {
        $link = $this->link;
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
        $this->link = $link;
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param PDO $pdo PDO instance connected to a database.
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    public function tableExists($table, $pdo = null)
    {

        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $query = $this->link->query("SELECT 1 FROM `" . strtolower($table) . "` LIMIT 1");
        } catch (Exception $e) {
            // We got an exception == table not found
            return FALSE;
        }
        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        if ($query) {
            $result = $query->fetch();
            return true;
        } else {
            return false;
        }
    }
}
