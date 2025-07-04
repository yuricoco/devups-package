<?php

trait ModelTrait
{

    public $dvfetched = false;
    public $dvold = false;
    public $dvsoftdelete = false;
    public $dvinrelation = false;
    public $dvtranslate = false;
    public $dvid_lang = false; // this attribute has an issue I've forgot the one but this note is just to remind me of that
    // in fact if the attribute is not setted the __get() method will throw a error: attribute not found! why Have i commented it?
    public $dvtranslated_columns = [];
    private static $dvkeys = ["dvold", "dvid_lang", "dvfetched", "dvinrelation", "dvsoftdelete", "dvtranslate", "dvtranslated_columns",];

    public $dv_collection = [];

    public function __get($attribut)
    {
        global $em; //$global_config;
//        dv_dump($this);
//        $config = $global_config[get_class($this)];
        if (!property_exists($this, $attribut)) {

            if ($this->dvtranslate && in_array($attribut, $this->dvtranslated_columns)) {
                if (!$this->id)
                    return null;

                $classlang = get_class($this) . "_lang";

                $metadata = $em->getClassMetadata("\\" . $classlang);
                $cnl = $metadata->table['name'];
//                $cnl = strtolower($classlang);
                if (property_exists($classlang, $attribut)) {
                    $idlang = DBAL::$id_lang_static;

                    if (!$idlang) {

                        (new DBAL())->setClassname(get_class($this))->getLangValues($this, [$attribut]);
                        return $this->{$attribut};
                    }
                    $sql = " SELECT $attribut FROM `$cnl` WHERE lang_id = $idlang AND " . strtolower(get_class($this)) . "_id = " . $this->id;
                    $data = (new DBAL())->executeDbal($sql, [], DBAL::$FETCH);

                    $this->{$attribut} = $data[0];
                    return $data[0];
                }
            } else {
                $entityattribut = substr($attribut, 1, strlen($attribut) - 1);
                //var_dump($attribut);
                if ($attribut != "_" . $entityattribut) {
                    $trace = debug_backtrace();
                    trigger_error(
                        'Propriété non-définie via __get() : ' . $attribut .
                        ' de la class ' . get_class($this) .
                        ' dans ' . $trace[0]['file'] .
                        ' à la ligne ' . $trace[0]['line'],
                        E_USER_NOTICE);
                    die;
                }
                if (is_object($this->{$entityattribut})) { //  && isset($this->{$entityattribut . "_id"})

                    if ($this->{$entityattribut}->dvfetched)
                        return $this->{$entityattribut};

                    $this->{$attribut} = $this->{$entityattribut}->hydrate($this->dvid_lang);
//                    $classname = get_class($this->{$attribut});
//                    $this->{"_".$attribut} = $classname::findrow($this->{$attribut . "_id"});

                    return $this->{$attribut};
                }

            }
        } elseif (property_exists($this, $attribut)) {//$this->id &&

            /*
             * if id is defined and value of the attribut of this instance is null (problem with default value) and
             * if devups has never fetch it before then we hydrate the hole instance with it row in database
             */

            if (!$this->dvfetched && $attribut != "id") { // $this->id &&  && !$this->{$attribut}

                /*
                 * the fact is that by a mechanism I don't understand by now once the method detect an association
                 * it automatically makes request to the db what I don't want.
                 * by the way even if we do $entity = $object->imbricate; when the dev will do $entity->attrib it will
                 * automatically hydrate the entity what solve the problem (at least for the current use case)
                 */
                /*if (is_object($this->{$attribut}) && isset($this->{$attribut."_id"})){

                    $classname = get_class($this->{$attribut});
                    $this->{$attribut} = $classname::findrow($this->{$attribut."_id"});

                    return $this->{$attribut};
                }*/
                if (!isset($this->id))
                    return $this->{$attribut};

                $classlang = get_class($this);
                $metadata = $em->getClassMetadata("\\" . $classlang);
                $fieldNames = $metadata->fieldNames;
                $assiactions = array_keys($metadata->associationMappings);
                /*if ($attribut === 'parent_id') {
                $config = $global_config[get_class($this)];
                    dv_dump($config, $metadata->table['name']);
                }*/
                $cn = $metadata->table['name'];
                if (!$this->id)
                    return $this->{$attribut};

                $sql = " SELECT * FROM `$cn` WHERE id = " . $this->id;
                $data = (new DBAL())->executeDbal($sql, [], DBAL::$FETCH);
                //var_dump($classlang." - ".$attribut, $data, $fieldNames);
                foreach ($fieldNames as $k => $val) {
                    $this->{$k} = $data[$k];
                }
                foreach ($assiactions as $k) {
                    //if(isset($data[$k]))
                    $this->{$k}->id = $data[$k . "_id"];
                    $this->{$k . "_id"} = $data[$k . "_id"];
                }

                $this->dvfetched = true;
                //return $data[0];
            }
            return $this->{$attribut};

        }

        $trace = debug_backtrace();
        trigger_error(
            'Propriété non-définie via __get() : ' . $attribut .
            ' de la class ' . get_class($this) .
            ' dans ' . $trace[0]['file'] .
            ' à la ligne ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __set($name, $value)
    {
        if (str_contains($name, '_id')) {
            $attribut = str_replace('_id', '', $name);
            if (property_exists($this, $attribut) && is_object($this->{$attribut})) {
                $this->{$attribut}->id = $value;
            }
        }
        // TODO: Implement __set() method.
        $this->{$name} = $value;
    }

    /**
     * return the row as design in the database
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function count($parameter = null, $value = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        if (is_null($parameter))
            return $qb->select()->count();

        if (is_object($parameter) || is_array($parameter))
            return $qb->select()->where($parameter)->count();

        return $qb->select()->where($parameter, "=", $value)->count();

    }


    /**
     * return the firt
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function first($id_lang = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->select()->getInstance();
    }

    /**
     * return the firt
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function firstOrCreate($constraint, $data = [], $id_lang = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->firstOrCreate($constraint, $data, $id_lang);

    }

    /**
     * return the firt
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function firstOrNull($id_lang = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->firstOrNull($id_lang);

    }

    /**
     * return the firt
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function firstOrNew($constraint, $data = [], $id_lang = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->firstOrNew($constraint, $data, $id_lang);

    }

    /**
     * return the row as design in the database
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function last($id_lang = null)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->select()->orderby($qb->getTable() . ".id desc")->limit(1)->getInstance();
    }

    /**
     * return the row as design in the database
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function lastrow()
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->select()->orderby("id desc")->limit(1)->getInstance();
    }

    /**
     * return the row as design in the database
     * @example http://easyprod.spacekola.com description
     * @param type $id
     * @return $this
     */
    public static function index($index = 1, $id_lang = null)
    {
        $i = (int)$index;
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->getIndex($index, $id_lang);
        /*if ($i < 0) {
            $nbel = $qb->count();
            if ($nbel == 1)
                return $entity;

            $i += $nbel;
            return $qb->select()->limit($i - 1, $i)->getInstance($recursif, $collect);
        }

        return $qb->select()->limit($i - 1, $i)->getInstance($recursif, $collect);*/
    }

    /**
     * return the attribut as design in the database
     * @example http://easyprod.spacekola.com description
     * @param string $attribut
     * @param int $id
     * @return mixed
     */
    public static function getattribut($attribut, $id)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->select($attribut)->where("this.id", $id)->getValue();
    }

    /**
     * return the attribut as design in the database
     * @example http://easyprod.spacekola.com description
     * @param string $attribut
     * @param string $value
     * @return $this
     */
    public static function getbyattribut($attribut, $value, $id_lang = false)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        if ($id_lang)
            $entity->dvid_lang = $id_lang;

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        return $qb->select()->where($attribut, $value)->getInstance();
    }


    /**
     * @param ...$columns
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public static function addColumns(...$columns)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);

        $qb->custom_columns_array = [...$columns];
//        $qb->custom_columns .= implode(", ", $columns);
        return $qb;// ->addColumns($columns)->getValue();
    }

    public static $columns = [];

    /**
     * @param ...$columns
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public static function addColumn($column, $as = "")
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        if ($as) {
            if (!isset(Model::$columns[get_called_class()]))
                Model::$columns[get_called_class()] = [];

            $var = explode(':', $as);
            if (count($var) == 2) {
                Model::$columns[get_called_class()][] = $var;
                $as = $var[0];
            } else
                Model::$columns[get_called_class()][] = $as;

            $as = " AS $as";
        }
        $qb = new QueryBuilder($entity);
        $qb->custom_columns .= (" " . $column . " $as ");
        return $qb;// ->addColumns($columns)->getValue();

    }

    public static function addAttributes($entity)
    {
        $attrs = [];
        if (!isset(Model::$columns[get_called_class()]))
            return [];
        //dv_dump($entity, Model::$columns[get_called_class()]);
        foreach (Model::$columns[get_called_class()] as $att) {
            if (!isset($entity->{$att[0]}))
                continue;

            if (is_array($att)) {
                if ($att[1] == 'bool')
                    $attrs[$att[0]] = (bool)$entity->{$att[0]};
                elseif ($att[1] == 'int')
                    $attrs[$att[0]] = (int)$entity->{$att[0]};
                else
                    throw new Exception("The parsing key " . $att[1] . " is not recognized!");
            } else
                $attrs[$att] = $entity->{$att};
        }
        return $attrs;
    }

    /**
     * @param ...$columns
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public static function addIndexColumn($columnOrder, $alias)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        $qb->addIndex($columnOrder, $alias);
        return $qb;// ->addColumns($columns)->getValue();
    }

    /**
     * return the row as design in the database
     * @example http://easyprod.spacekola.com description
     * @param int $id
     * @return $this
     */
    public static function findrow($id, $id_lang = null, $qb = null)
    {

        $classname = get_called_class();
        $reflection = new ReflectionClass($classname);
        $entity = $reflection->newInstance();
        $entity->setId($id);

        if ($id_lang)
            $entity->dvid_lang = $id_lang;

        if (!$qb)
            $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        if (is_array($id)) {

            return $qb->where("this.id")->in($id)->get();
        }
        if ($entity->dvtranslate) {
//            if (!$id_lang)
//                $id_lang = Dvups_lang::defaultLang()->getId();

            return $qb //->select()
            //->leftjoinrecto($classname . "_lang")
            ->where("this.id", "=", $id)
                //->where($classname . "_lang.lang_id", "=", $id_lang)
                ->getInstance();
        } else
            return $qb->select()->where("id", "=", $id)->getInstance();

    }

    /**
     * return the entity
     * when recursif set to false, attribut as relation manyToOne has just their id hydrated
     * when recursif set to true, the DBAL does recursif request to hydrate the association entity and those of it.
     * @param integer | array $id the id of the entity
     * @param boolean $recursif [true] tell the DBAL to find all the data of the relation
     * @return $this | array
     */
    public static function find($id, $id_lang = null, $qb = null)
    {
        $classname = get_called_class();
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        if ($id_lang)
            $entity->dvid_lang = $id_lang;

        if (!$qb)
            $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        if (is_array($id)) {
            if (is_string(array_key_first($id)))
                return $qb->where($id)->getInstance();

            return $qb->whereIn("this.id", $id)->get();
        }
        $entity->setId($id);

        return $qb->select()->where("this.id", "=", $id)
            ->getInstance();

    }

    /**
     * return the entity
     * when recursif set to false, attribut as relation manyToOne has just their id hydrated
     * when recursif set to true, the DBAL does recursif request to hydrate the association entity and those of it.
     * @param integer | array $id the id of the entity
     * @param boolean $recursif [true] tell the DBAL to find all the data of the relation
     * @return $this | array
     */
    public static function findOrNull($id, $id_lang = null)
    {
        $classname = get_called_class();
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        if ($id_lang)
            $entity->dvid_lang = $id_lang;

        $qb = new QueryBuilder($entity);
        $qb->setLang($id_lang);
        if (is_array($id)) {
            if (is_string(array_key_first($id)))
                return $qb->where($id)->firstOrNull();

            return $qb->whereIn("this.id", $id)->get();
        }
        $entity->setId($id);

        return $qb->select()->where("this.id", "=", $id)
            ->firstOrNull();

    }

    private static $keyvalues = [];

    /**
     * create entity row and return id(s) of row(s)
     * @param ...$keyvalues
     * @return array|int|mixed
     */
    public static function create(...$keyvalues)
    {
        $ids = [];
        foreach ($keyvalues as $keyvalue) {
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
        $ids = self::create($keyvalues);
        if (count($ids)) {
            $instances = [];
            foreach ($ids as $id) {

            }
        }
    }

    public static function createFrom($data, $table_targeted)
    {

        $classname = get_called_class();

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->createFrom($data, $table_targeted);

    }

    /**
     * update a part or an entire entity
     * @example http://easyprod.spacekola.com description
     * @param Mixed $arrayvalues
     * @param Mixed $seton
     * @param Mixed $case id
     * @return \QueryBuilder
     */
    public static function update($arrayvalues, $primarykey, $where = "", $classname = "")
    {
        if (!$classname)
            $classname = get_called_class();

        foreach ($primarykey as $key => $value) {
            $where .= " AND $key = $value ";
        }

        return DBAL::_updateDbal($classname, $arrayvalues, $where);

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
        $dbal = new DBAL();
        DBAL::$id_lang_static = $this->dvid_lang;
        if (!$arrayvalues) {
            return $dbal->updateDbal($this);
        } else {
            $qb = new QueryBuilder($this);
            return $qb->update($arrayvalues, $seton, $case, $defauljoin);
        }
    }


    /**
     * return the entity
     * when recursif set to false, attribut as relation manyToOne has just their id hydrated
     * when recursif set to true, the DBAL does recursif request to hydrate the association entity and those of it.
     * @param type $id the id of the entity
     * @param boolean $recursif [true] tell the DBAL to find all the data of the relation
     * @return \QueryBuilder
     */
    public static function delete($id = null, $force = false)
    {

        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        if (is_array($id)) {
            $qb = new QueryBuilder($entity);
            return $qb->whereIn("this.id", $id)->delete($force);
        } elseif (is_numeric($id)) {
            $entity->setId($id);
            $dbal = new DBAL();
            return $dbal->deleteDbal($entity, $force);
        } else {
            $qb = new QueryBuilder($entity);
            return $qb->delete($force);
        }

    }

    /**
     *
     * @param string $sort
     * @param type $order
     * @return type
     */
    public static function all($sort = 'id', $order = "asc", $id_lang = null)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        if ($entity->dvtranslate) {
            $qb->setLang($id_lang);
        }
        if ($sort == 'id')
            $sort = $qb->getTable() . "." . $sort;

        return $qb->select()->handlesoftdelete()->orderby($sort . " " . $order)->get();

    }

    /**
     * Return an array of rows as in database.
     * @example http://easyprod.spacekola.com/doc/#allrow
     * @param String $att the attribut you want to order by
     * @param String $order the ordering model ( ASC default, DESC, RAND() )
     * @return Array
     */
    public static function allrows($sort = 'id', $order = "", $id_lang = null)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        if ($entity->dvtranslate) {
            $qb->setLang($id_lang);
        }
        if ($sort == 'id')
            $sort = $qb->getTable() . "." . $sort;

        return $qb->select()
            //->handlesoftdelete()
            ->orderBy($sort . " " . $order)->get();
    }

    public static function trashed($sort = 'id', $order = "", $id_lang = null)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        if ($entity->dvtranslate) {
            $qb->setLang($id_lang);
        }
        if ($sort == 'id')
            $sort = $qb->getTable() . "." . $sort;

        return $qb->select()
            ->handlesoftdelete()
            ->orderBy($sort . " " . $order)->trashed();
    }


    /**
     * return instance of \QueryBuilder white the select request sequence.
     * @param string $columns
     * @return \QueryBuilder
     * @example name, description, category if none has been set, all will be take.
     */
    public static function select($columns = '*', $id_lang = null, $defaultjoin = true)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity, $defaultjoin);
        if ($entity->dvtranslate) {

            $qb->setLang($id_lang);
        }

        return $qb->select($columns);
    }

    /**
     * @param $column
     * @return QueryBuilder
     */
    public static function selectSum($column)
    {
        return self::select("SUM($column)", false, false);
    }

    /**
     * @param $defaultjoin
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public static function initQb($defaultjoin = true)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity, $defaultjoin);
        return $qb;
    }

    /**
     * @param $defaultjoin
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public static function initBulk($payload)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->initBulk($payload);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return QueryBuilder
     */
    public static function where($column, $operator = null, $value = null, $id_lang = null)
    {
        return self::select("*", $id_lang)->where($column, $operator, $value);
    }
    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return QueryBuilder
     */
    public static function whereId($id, $id_lang = null)
    {
        return self::select("*", $id_lang)->where("this.id", $id);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return QueryBuilder
     */
    public static function where_str($column, $link = "AND", $id_lang = null)
    {
        return self::select("*", $id_lang)->where_str($column, $link);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return QueryBuilder
     */
    public static function with($entity, $attr = [])
    {
        return self::select()->with($entity, $attr);
    }

    /**
     * @param $classname
     * @param $alias
     * @param $constraint
     * @return QueryBuilder
     */
    public static function leftJoinOn($classname, $alias = "", $constraint = "")
    {
        return self::select()->leftJoinOn($classname, $alias = "", $constraint = "");
    }

    /**
     * @param $column
     * @param $collection
     * @return QueryBuilder
     */
    public static function whereIn($column, $collection)
    {
        return self::select()->whereIn($column, $collection);
    }

    /**
     * @param $column
     * @param $collection
     * @return QueryBuilder
     */
    public static function whereInReverse($value, $collection)
    {
        return self::select()->whereInReverse($value, $collection);
    }

    public static function join($classname, $classnameon = null, $id_lang = null)
    {
        return self::select("*", $id_lang)->leftjoin($classname, $classnameon);
    }

    /**
     * @param null $id
     * @param null $update
     * @return mixed
     * @throws ReflectionException
     */
    public static function dclone($id = null, $update = null)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        if ($id)
            return $qb->__dclone($update)->where("this.id", $id)->exec(DBAL::$INSERT);

        return $qb->__dclone($update);
    }

    /**
     * return instance of \QueryBuilder white the select request sequence.
     * @param string $columns
     * @return QueryBuilder
     * @example name, description, category if none has been set, all will be take.
     */
    public static function orderBy($column, $sort = "")
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->orderBy($column, $sort);

    }

    /**
     * return instance of \QueryBuilder white the select request sequence.
     * @param string $columns
     * @return float
     * @example name, description, category if none has been set, all will be take.
     */
    public static function sum($columns)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->sum($columns);

    }

    public static function avg($columns, $as = "")
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);

        return $qb->avg($columns, $as);
    }

    public static function max($columns, $as = "")
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->max($columns, $as);
    }

    public static function distinct($columns)
    {
        $reflection = new ReflectionClass(get_called_class());
        $entity = $reflection->newInstance();

        $qb = new QueryBuilder($entity);
        return $qb->distinct("$columns");
    }

    public function hydrateMatch($flowDB, $entity, $deep = true)
    {

        foreach ($this as $key => $val) {
            if (isset($flowDB[$entity . '_' . $key])) {
                $this->{$key} = $flowDB[$entity . '_' . $key];
            } elseif (is_object($this->{$key})) {
                if (isset($flowDB[$entity . '_' . $key . '_id'])) {
                    $this->{$key}->id = $flowDB[$entity . '_' . $key . '_id'];
                    $this->{$key}->hydrateMatch($flowDB, $entity . '_' . $key, false);
                }
            }
        }

//        if (!$deep)
//            return;

    }

    public function inCollectionOf($collection, $key_map = "")
    {

        if (!$this->getId())
            return [];

        $thisclass = get_class($this);
        $entityTable = $collection;
        $entity_id = "id";

        $dbal = new DBAL();
        if ($key_map) {
            // do nothing
            $entityTable = $key_map;
        } elseif ($dbal->tableExists($collection . '_' . $thisclass)) {
            $entityTable = $collection . '_' . $thisclass;
            $entity_id = $collection . "_id";
        } elseif ($dbal->tableExists($thisclass . "_" . $collection)) {
            $entityTable = $thisclass . "_" . $collection;
            $entity_id = $collection . "_id";
        }
//        else {
//            $association = false;
//            $entityTable = $entityName;
//            $direction = "lr";
//        }

        $collection_ids = [];

        $dbal = new DBAL();
        $results = $dbal->executeDbal(strtolower(" select $entity_id from `$entityTable` where " . $thisclass . "_id = " . $this->getId()), [], DBAL::$FETCHALL);
        foreach ($results as $index => $values)
            $collection_ids[] = $values[0];
        //$result = $result[$index][0];

        return $collection_ids;
        //return implode(",", $collection_ids);
    }

    public function entityKey($fieldNames, &$entity_link_list = null, &$collection = null, &$softdelete = null)
    {
        $keys = [];
        foreach ($this as $key => $val) {
            if (isset($fieldNames[$key]))
                $keys[$key] = $val;
        }
        return $keys;

    }

    public function entityKeyForm()
    {
        $keys = [];

        foreach ($this as $key => $val) {
            if (in_array($key, self::$dvkeys))
                continue;
            if (is_object($val)) {
                $keys[$key . '.id'] = $val->getId();
            } else
                $keys[$key] = $val;
        }
        return $keys;
    }

    public static function getIdentifyKey($classname = '')
    {

        global $em;
        if (!$classname)
            $classname = get_called_class();
        $metadata = $em->getClassMetadata("\\" . $classname);

        if (count($metadata->identifier) > 1) {
            $identifier = [];
            foreach ($metadata->identifier as $id) {
                $identifier[$id . "_id"] = $id . "_id";
            }
            return $identifier;
        }
        return array_combine($metadata->identifier, $metadata->identifier);
    }


    /**
     * @param $next
     * @param $perpage
     * @param $order
     * @param $debug
     * @return \dclass\devups\Datatable\Lazyloading|int|QueryBuilder
     * @throws ReflectionException
     */
    public static function lazyloading($order = "", $qb = null, $debug = false)
    {//
        $classname = get_called_class();
        $reflection = new ReflectionClass($classname);
        $entity = $reflection->newInstance();

        $ll = new \dclass\devups\Datatable\Lazyloading($entity);
        $ll->start(new QueryBuilder($entity));
        if ($debug)
            return $ll->renderQuery()->lazyloading($entity, $qb, $order);

        return $ll->lazyloading($entity, $qb, $order);

    }

    public static function importSanitizer($key, $value)
    {
        return $value;
    }

    public static function truncate()
    {

        $classname = strtolower(get_called_class());
        $sql = "TRUNCATE `" . $classname . "`; ";
        $dbal = new DBAL();
        $dbal->executeDbal($sql);

    }

    public function hydrateData(
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
                if (!$flowBD[$key."_id"] || !$value)
                    continue;

                $value->setId($flowBD[$key."_id"]);
                $value->dvid_lang = $id_lang;
                $value->hydrateMatch($flowBD, $key);
                $this->{$key} = $value;

            }

            elseif (isset($flowBD[$key]))
                $this->{$key} = $flowBD[$key];

        }

        return $this;
    }


}
