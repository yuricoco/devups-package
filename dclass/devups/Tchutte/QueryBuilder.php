<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QueryBuilder
 *
 * @author Aurelien Atemkeng
 */
class QueryBuilder extends \DBAL
{

    public static $debug = false;

    //    private $table;
    public $_select = "";
    public $_from = "";
    public $_set = "";
    public $_into = "";
    public $_select_option = "*";
    public $_where = "";
    public $_group_by = "";
    public $_join = "";
    public $_group_lang = "";
    public $_join_lang = "";
    public $_order_by = "";
    public $_limit = "";

    private $query = "";
    private $sequence = "";
    private $parameters = [];
    /** Compteur de placeholders whereLike()/like()/_like()/like_() - evite les collisions de nom lie au sein d'une meme requete. */
    private $likeParamSeq = 0;
    /**
     * ETAPE B.1/B.2 (v7.1) : meme role que $likeParamSeq, pour les
     * placeholders de whereIn()/in()/notIn()/whereInReverse()/
     * whereBetween()/between() - evite toute collision entre plusieurs
     * appels sur la meme requete (ex. whereGroup()).
     */
    private $inParamSeq = 0;
    private $columns = "*";
    //private $defaultjoin = "";
    private $columnscount = "COUNT(*)";
    private $endquery = "";
    private $initwhereclause = false;
    private $defaultjoinsetted = true;
    private $sequensized = false;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    private $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'between',
        'ilike',
        'is',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        'rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
    ];

    public $tablecollection = "";
    private $joincollection = [];


    public function __construct($entity = null, $defaultjoinsetted = true)
    {
        $this->initwhereclause = false;
        if (is_object($entity))
            parent::__construct($entity);

        $this->_from = "`" . strtolower($this->table) . "`";
        if ($defaultjoinsetted) {
            $this->defaultjoinsetted = $defaultjoinsetted;
            $this->initdefaultjoin();
        }
        //        $this->table = strtolower(get_class($entity));
        /*if ($this->object->dvtranslate) {
            $this->custom_columns_array = [
                ...$this->custom_columns_array,
                ...$this->object->dvtranslated_columns
            ];
            $thislang = $this->table . "_lang";
            if( $this->id_lang) {
                $this->_group_lang .= ", `" . $thislang . "`.`" . implode("`, $thislang.`", $this->object->dvtranslated_columns) . "`";
                $this->_join_lang .= " left join $thislang on ($thislang.{$this->table}_id = `{$this->table}`.id  and $thislang.lang_id = {$this->id_lang}) ";
            }else {
                $groupment = [];
                foreach ($this->object->dvtranslated_columns as $col)
                    $groupment[] = "  GROUP_CONCAT( CONCAT('[', l.iso_code, ']', $thislang.$col) SEPARATOR '' ) AS $col ";
                $this->_group_lang .= ", " . implode(", ", $groupment) . " ";
//                $this->_group_lang .= ", " . implode(", ", $groupment) . " ";
                $this->_group_lang .= ", " . implode(", ", $groupment) . " ";
                $this->_join_lang .= " left join $thislang on ($thislang.{$this->table}_id = `{$this->table}`.id ) ";
                $this->_join_lang .= " join dvups_lang l on ($thislang.lang_id = l.id ) ";
//                $this->_join_lang .= " join dvups_lang l on ($thislang.lang_id = l.id ) ";
                $this->_group_by .= ("{$this->table}.id");
            }
        }*/
    }

    /**
     * Set the columns to be selected.
     *
     * @param array|mixed $columns
     * @return string
     */
    public function close(\QueryBuilder &$qb)
    {
        $this->initSelect();
        $this->sequensization();

        $qb->addParameter($this->parameters);

        return $this->query;
    }

    public function addParameter($parameters)
    {
        $this->parameters += $parameters;
    }

    /**
     * Set the columns to be selected.
     *
     * @param array|mixed $columns
     * @return $this
     * @example name, description, category
     * @example name, description, category
     */
    public function setLang($id_lang)
    {
        $this->id_lang = $id_lang;
        DBAL::$id_lang_static = $id_lang;
        return $this;
    }


    /**
     * Set the columns to be selected.
     *
     * @param array|mixed $columns
     * @return $this
     * @example name, description, category
     * @example name, description, category
     */
    public function select($columns = '*', $object = null, $defaultjoin = true)
    {

        // it means the select has been called to initialize the querybuilder
        if ($this->_select_option != "*")
            return $this;

        $this->softdeletehandled = false;
        $this->defaultjoin = "";
        if (is_object($columns)):
            $columns = "this.*";
        endif;

        if ($columns == '*') {
            $columns = "this.`" . implode("`, this.`", $this->objectVar) . "`";
        }

        $this->columns = $columns;
        $this->_select_option = $columns;
        $this->query = " ";

        return $this;
    }

    public function addColumns(...$columns)
    {
        $this->custom_columns_array = [...$this->custom_columns_array, ...$columns];
        //        $this->custom_columns .= implode(", ", $columns);
        return $this;
    }

    public function addColumn($column, $as, $_with = false)
    {
        $as = trim($as);
        //        if ($this->custom_columns)
        //            $this->custom_columns .= ", ";
        if (in_array($as, $this->objectVar)) {

            unset($this->objectVar[array_search($as, $this->objectVar)]);
        }
        if (!$_with)
            $this->custom_column_keys[] = $as;
        if (is_object($column))
            $columns = "( " . $column->getSqlQuery()['query'] . " ) AS $as ";
        else
            $columns = "( " . $column . " ) AS $as ";

        $this->custom_columns_array[] = $columns;
        return $this;
    }

    /**
     * @param bool $update
     * @return $this
     */
    public function from($collection, $alias = "")
    {

        //        $classname = [];
        //        foreach ($collection as $val)
        //            $classname[] = strtolower(get_class($val));

        if (is_array($collection))
            $this->tablecollection .= ", " . implode(", ", $collection) . " $alias";
        else
            $this->tablecollection .= ", " . strtolower($collection) . " $alias ";

        // $this->_from .= ", `".strtolower($this->tablecollection)."` $alias";
        return $this;
    }

    /**
     * @param $entity
     * @param $columns Array
     * @return $this
     */
    public function with($entity, $columns = [])
    {
        //        foreach ($columns as $column)
        //            $this->addColumn($entity . "." . $column, $entity . "_" . $column);
        //        if (is_string($columns)) {
        //
        //        }else
        $this->_with[$entity] = $columns;
        return $this;
    }
    /**
     * @param $entity
     * @param $columns Array
     * @return $this
     */
    public function without(...$entity)
    {
        //        foreach ($columns as $column)
        //            $this->addColumn($entity . "." . $column, $entity . "_" . $column);

        $this->_without = ($entity);
        return $this;
    }


    /**
     * @param bool $update
     * @return $this
     */
    public function __dclone($update = false)
    {
        unset($this->objectVar[0]);
        $col = "`" . strtolower(implode('`,`', $this->objectVar)) . "`";
        $objectVar = explode(",", $col);
        if ($update)
            foreach ($objectVar as $i => $var) {
                foreach ($update as $key => $value) {
                    if ($var == "`" . $key . "`") {
                        $objectVar[$i] = "'" . $value . "'";
                        unset($update[$key]);
                    }
                }
                //                if(count($update))
                //                    break;
            }

        $this->query = " insert into " . $this->_from . " (" . $col . ") select " . strtolower(implode(' ,', $objectVar)) . " from {$this->_from} ";
        return $this;
    }

    private function sequensization($aggregation = false)
    {
        $query = $this->query;
        $query .= " WHERE 1 ";
        if ($this->_where)
            $query .= " {$this->_where} ";

        if ($this->softdelete) {
            if ($this->dvtrashed) {
                if ($this->hasrelation)
                    $query .= ' AND ' . $this->table . '.deleted_at IS NOT NULL ';
                else
                    $query .= ' AND deleted_at IS NOT NULL ';
            } //elseif ($this->hasrelation)
            $query .= ' AND ' . $this->table . '.deleted_at IS NULL ';
            //            else
            //                $query .= ' AND deleted_at IS NULL ';
        }

        if (!$aggregation) {
            if ($this->_group_by)
                $query .= " GROUP BY {$this->_group_by} ";

            if ($this->_order_by)
                $query .= " {$this->_order_by} ";

            if ($this->_limit)
                $query .= " {$this->_limit} ";
        }

        $this->query = $this->querysanitize($query); //. $this->endquery

        $this->sequensized = true;
        return $this;
    }

    /**
     * @return boolean | $this
     */
    public function delete($force = false)
    {

        if ($this->softdelete && $force == false) {
            $this->query = " UPDATE " . $this->table . " SET deleted_at = NOW() ";
        } else
            $this->query = "  DELETE {$this->_from} FROM {$this->_from} " . $this->_join;

        if (!$this->initwhereclause) {
            $this->_where = " AND id = " . $this->instanceid;
        }

        return $this->sequensization()->exec();
    }

    /**
     * @param $table
     * @param $primary_key_values
     * @param $keyvalue
     * @return int|true|type
     * @throws Exception
     */
    public function createFromTable($table, $primary_key_values, $keyvalue)
    {

        $primary_key = array_keys($primary_key_values);
        $primary_value = array_values($primary_key_values);
        //dv_dump($primary_key, $primary_value);
        $objectvar = array_keys($keyvalue);
        $finaldata = [];
        $sql = "INSERT INTO `" . $this->table . "` (`"
            . strtolower(implode('` ,`', $primary_key))
            . "`, `" . strtolower(implode('` ,`', $objectvar))
            . "`) ";
        $sql .= " SELECT ";
        $sqlcolumns = [];
        for ($i = 0; $i < count($primary_key); $i++) {
            if (is_null($primary_value[$i]))
                $sqlcolumns[] = " id AS {$primary_key[$i]} ";
            else {
                $finaldata[$primary_key[$i]] = $primary_value[$i];
                $sqlcolumns[] = " :{$primary_key[$i]} AS {$primary_key[$i]} ";
            }
        }

        foreach ($keyvalue as $key => $value) {
            if (is_object($value) && get_class($value) == QueryBuilder::class)
                $sqlcolumns[] = " (" . $value->getSqlQuery()['query'] . ") AS $key ";
            else {
                $sqlcolumns[] = " :$key AS $key ";
                $finaldata[$key] = $value;
            }
        }
        $sql .= (implode(', ', $sqlcolumns)) . " FROM  " . strtolower($table);


        $this->query = $sql;

        if ($this->_join)
            $this->query .= " {$this->_join} ";

        $this->query .= $this->tablecollection;

        $this->sequensization();
        // dv_dump($this->query, $finaldata);
        return $this->executeDbal($this->query, $finaldata, 1);
    }

    /**
     *
     * @param type $arrayvalues
     * @param type $seton
     * @param type $case
     * @return boolean
     */
    public function update($arrayvalues = null, $seton = null, $case = null)
    {

        $this->columns = null;
        $this->query = "  UPDATE {$this->_from} ";

        if ($this->_join)
            $this->query .= " {$this->_join} ";

        if ($arrayvalues) {
            if (self::$debug)
                dv_dump($this->set($arrayvalues, $seton, $case)->sequensization()->getSqlQuery());

            return $this->set($arrayvalues, $seton, $case)->sequensization()->exec();
        }
        if (self::$debug)
            return $this->sequensization()->getSqlQuery();

        return $this->sequensization()->exec();
    }

    public function set($arrayvalues, $seton = null, $case = null)
    {

        $this->_set = " SET ";

        // update a column on multiple rows
        if (is_object($arrayvalues)) {
            $class = strtolower(get_class($arrayvalues));
            $this->parameters[$class . "_id"] = $arrayvalues->getId();
            $this->_set .= " " . $this->table . "." . $class . "_id = :" . $class . "_id";
            if ($this->instanceid)
                $this->endquery = " AND " . $this->table . ".id = " . $this->instanceid;
        } else
            if (is_array($case)) {
            $whens = [];
            $this->_set .= " `" . $arrayvalues . "` = CASE " . $seton . " ";

            foreach ($case as $when => $then) {
                $whens[] = $when;
                $this->parameters[$when] = $then;
                $this->_set .= " WHEN '$when' THEN :$when ";
            }

            $this->_set .= " ELSE  $seton END ";

            $whens = implode("', '", $whens);
            $this->endquery = " AND `" . $arrayvalues . "`  IN('" . $whens . "'); ";
        } // update one column on one row
        else
            if ($arrayvalues && $seton != null) {
            //elseif (true) {
            $this->parameters[$arrayvalues] = $seton;
            $this->_set .= " $arrayvalues = :$arrayvalues ";
            /*if (($this->identifier) > 1) {

                $this->endquery = " AND " . $this->table . ".id = " . $this->instanceid;
            }else
                $this->endquery = " AND " . $this->table . ".id = " . $this->instanceid;*/
        } // update multiple column on one row
        else {
            $arrayset = [];

            if ($this->object->dvtranslate) {

                // we check of attribut translable is present in the arrayvalue to update for an entity dvtranslated
                // then if $attribnotexist comes with the same value of dvtranslated_columns that mean non translable attribute
                // has been setted for update
                $attribnotexist = array_diff($this->object->dvtranslated_columns, array_keys($arrayvalues));
                //
                //                dv_dump($attribnotexist, $this->object->dvtranslated_columns);
                if ($attribnotexist != $this->object->dvtranslated_columns) {

                    $objarray = $arrayvalues;
                    if ($this->object->dvid_lang) {
                        $parameterQuery = [];
                        $keyvalue = [];
                        foreach ($this->object->dvtranslated_columns as $key) {
                            if (!isset($objarray[$key]))
                                continue;

                            $parameterQuery[] = ' `' . $key . '`= :' . $key;
                            $keyvalue[$key] = $objarray[$key];
                            unset($arrayvalues[$key]);
                        }
                        if ($keyvalue)
                            $this->updateLangValue($keyvalue, $parameterQuery, $this->object->dvid_lang);
                    } else {
                        // dv_dump($this->object->dvtranslated_columns, $arrayvalues);
                        $langs = Dvups_lang::allrows();

                        foreach ($langs as $lang) {
                            $parameterQuery = [];
                            $keyvalue = [];

                            foreach ($this->object->dvtranslated_columns as $key) {
                                if (!isset($objarray[$key]))
                                    continue;
                                $parameterQuery[] = ' `' . $key . '`= :' . $key;
                                $keyvalue[$key] = $objarray[$key][$lang->getIso_code()];

                                unset($arrayvalues[$key]);
                            }

                            if ($keyvalue)
                                $this->updateLangValue($keyvalue, $parameterQuery, $lang->getId());
                        }
                    }
                }
            }

            foreach ($arrayvalues as $key => $value) {

                $keymap = explode(".", $key);
                $attrib = str_replace('.', '_', $key);

                $dot = "`";
                if (count($keymap) == 2)
                    $dot = "";

                if (is_object($value)) {

                    if (get_class($value) == \dclass\devups\Tchutte\Column::class) {
                        //$this->parameters[$attrib] = ;
                        $arrayset[] = $dot . implode('.`', $keymap) . "` = (" . $value->column . ")";
                    } elseif (get_class($value) == QueryBuilder::class) {
                        //$this->parameters[$attrib] = ;
                        $arrayset[] = $dot . implode('.`', $keymap) . "` = (" . $value->getSqlQuery()['query'] . ")";
                    } else {
                        $this->parameters[strtolower(get_class($value)) . "_id"] = $value->getId();
                        $arrayset[] = strtolower(get_class($value)) . "_id = :" . strtolower(get_class($value)) . "_id";
                    }
                } else {
                    $this->parameters[$attrib] = $value;
                    $arrayset[] = $dot . implode('.`', $keymap) . "` = :" . $attrib;
                }
            }
            $this->_set .= implode(", ", $arrayset);
            /*if (count($this->identifier) > 1) {
                $this->endquery = "";
//                dv_dump($this->identifier);
                foreach ($this->identifier as $identifier => $value)
                    $this->endquery .= " AND " . $this->table . ".$identifier"." = " . $value;
            }else if ($this->instanceid)
                $this->endquery = " AND " . $this->table . ".id = " . $this->instanceid;*/
        }

        $this->query .= $this->_set;

        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param array|mixed $columns
     * @return $this
     */
    public function join($joins, $type = " LEFT JOIN ")
    {
        // todo: to be thinked
        foreach ($joins as $key => $value)
            $join = $type . " ";
        return $this;
    }

    public function initdefaultjoin()
    {

        $this->defaultjoinsetted = true;
        if (!empty($this->entity_link_list)) {
            $entity_links = array_keys($this->entity_link_list);
            foreach ($entity_links as $entity_link) {
                $class_attrib = explode(":", $entity_link);
                if ($class_attrib[0] != $class_attrib[1])
                    $this->defaultjoin .= " LEFT JOIN `" . $class_attrib[0] . "` `" . $class_attrib[1] . "` ON `" . $class_attrib[1] . "`.id = " . $this->table . "." . $class_attrib[1] . "_id";
                else
                    $this->defaultjoin .= " LEFT JOIN `" . $class_attrib[0] . "` ON `" . $class_attrib[0] . "`.id = {$this->table}." . $class_attrib[0] . "_id";
            }
        }
        $this->_join .= $this->defaultjoin;

        return $this;
    }

    /**
     * init innerjoin of the $classname, base on the $classnameon. if the $classnameon is not specified, it will be set as the current
     * class
     * @param string $classname
     * @param string $classnameon
     * @param string $constraint
     * @return $this
     */
    public function innerjoin($classname, $classnameon = "", $constraint = "")
    {

        $join = strtolower($classname);

        if (!$classnameon)
            $classnameon = $this->table;

        $this->_join .= " INNER JOIN `" . $join . "` ON ( `" . $join . "`.id = `" . strtolower($classnameon) . "`." . $join . "_id $constraint ) ";

        return $this;
    }

    public function innerJoinOn($classname, $alias = "", $constraint = "")
    {
        $join = strtolower(str_replace(" ", "", $classname));

        if (!$alias)
            $alias = $this->table;

        $this->_join .= " INNER JOIN `" . $join . "` $alias ON ( $constraint )";

        return $this;
    }

    public function join_str($classname, $on)
    {
        $join = strtolower($classname);

        $this->_join .= " INNER JOIN `" . $join . "` ON ( $on ) ";

        return $this;
    }

    /**
     * init leftjoin of the $classname, base on the $classnameon. if the $classnameon is not specified, it will be set as the current
     * class
     * @param string $classname
     * @param string $classnameon
     * @param string $constraint
     * @return $this
     */
    public function leftjoin($classname, $classnameon = "", $constraint = "")
    {
        $join = strtolower($classname);

        if (!$classnameon)
            $classnameon = $this->table;

        $this->_join .= " LEFT JOIN `" . $join . "` ON ( `" . $join . "`.id = `" . strtolower($classnameon) . "`." . $join . "_id $constraint )";

        return $this;
    }

    public function leftJoinOn($classname, $alias = "", $constraint = "")
    {
        $join = strtolower(str_replace(" ", "", $classname));

        if (!$alias)
            $alias = $this->table;

        $this->hasrelation = true;
        $this->_join .= " LEFT JOIN `" . $join . "` $alias ON ( $constraint )";
        $this->_join_table[] = $alias;

        return $this;
    }

    /**
     * rather than take relation.id = table.relation_id to create the link,
     * it uses relation.table_id = table.id
     *
     * class
     * @example get all the post in different timeline (timelineuser, timelinepage, timelinegroup, ...) timeline's got post_id
     * but post doesn't have timeline's id. therefore to establish the relation, we need inverse the usual way. also we use left join
     * because it support null value, again right join and inner join
     * @param string $classname
     * @param string $classnameon
     * @return $this
     */
    public function leftjoinrecto($classname, $classnameon = "", $alias = "")
    {
        $this->join = strtolower($classname);

        if (!$classnameon)
            $classnameon = $this->objectName;

        if (!$alias)
            $alias = $this->join;

        $this->_join .= " LEFT JOIN `" . $this->join . "` `$alias` ON `" . $alias . "`." . strtolower($classnameon) . "_id = `" . strtolower($classnameon) . "`.id";

        return $this;
    }

    public function on($entity)
    {
        //" left join `".strtolower(get_class($entity)).

        $this->query .= " ON `" . $this->join . "`.id = `" . strtolower(get_class($entity)) . "`." . $this->join . "_id";

        return $this;
    }

    public $softdeletehandled = false;

    public function handlesoftdelete()
    {

        if ($this->softdeletehandled)
            return $this;

        if ($this->softdelete) {
            if ($this->hasrelation)
                $this->query .= ' WHERE ' . $this->table . '.deleted_at IS NULL ';
            else
                $this->query .= ' WHERE deleted_at IS NULL ';
        }

        $this->softdeletehandled = true;
        return $this;
    }

    /**
     * document https://sql.sh/fonctions/date_format
     * @param $column
     * @param $value
     * @param $link
     * @return $this
     */
    public function whereDay($column, $value, $link = 'AND')
    {

        $this->_where .= " $link DATE_FORMAT($column, '%d') = " . $value;
        return $this;
    }

    public function whereMonth($column, $value, $link = 'AND')
    {

        $this->_where .= " $link DATE_FORMAT($column, '%m') = " . $value;
        return $this;
    }

    public function whereYear($column, $value, $link = 'AND')
    {
        $this->_where .= " $link DATE_FORMAT($column, '%Y') = " . $value;
        return $this;
    }

    public function whereDate($column, $value, $link = 'AND')
    {

        $this->_where .= " $link DATE_FORMAT($column, '%Y-%m-%d') = '$value' ";
        return $this;
    }

    public function whereHour($column, $value, $link = 'AND')
    {

        $this->_where .= " $link DATE_FORMAT($column, '%H') = '$value'  ";
        //        $this->_where .= " $link DATE_FORMAT($column, '%H:%i:%s') = '$value'  ";
        return $this;
    }

    /**
     * Sentinelle distinguant « rien n'a ete passe » de
     * « null/0/''/false a ete passe explicitement ». Necessaire car la forme
     * a DEUX arguments (`where('col', $value)`) fait arriver la valeur dans
     * `$operator` (voir plus bas) : avec un defaut `null` ordinaire,
     * impossible de distinguer `where('col')` (colonne nue volontaire, voir
     * `whereId()`/`WorkersController::deletegroupAction()` qui la
     * completent par `->in()`) de `where('col', null)` (comparaison
     * explicite a NULL). PUBLIC : `Model::where()` (ModelTrait.php) doit
     * declarer les MEMES defauts et les transmettre tels quels pour que la
     * distinction survive a sa delegation.
     */
    public const NO_ARG = "\0devups_no_arg\0";

    /**
     * Add a basic where clause to the query.
     *
     * CORRECTIF : la forme a deux arguments
     * (`where('this.status', 0)`) faisait arriver la valeur dans
     * `$operator`, et le bloc qui construit le predicat etait garde par
     * `if ($operator)` - un test de VERITE. Toute valeur fausse au sens PHP
     * (`0`, `''`, `null`, `false`) sautait donc le bloc entier, laissant
     * `$this->_where` ne porter que la colonne NUE deja posee plus haut
     * (`AND this.status`) - en MySQL, une colonne seule dans un contexte
     * booleen signifie `!= 0`. **Le filtre etait donc INVERSE, pas absent.**
     * Couvert par tests/Unit/QueryBuilderWhereTest.php.
     *
     * @param string|array|\Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @return $this
     */
    /**
     * ETAPE C.4 (v7.1) - D2 : une colonne NON qualifiee (sans "table."/"this.")
     * qui appartient a l'entite de base devient ambigue dans un WHERE/IN/
     * BETWEEN des qu'une jointure de traduction est presente - initSelect()
     * joint toujours "<table>_lang" (colonne "id", entre autres), et en mode
     * bilingue (sans id_lang) joint aussi "dvups_lang" aliase "l" (colonnes
     * "id"/"name", entre autres) : MySQL/MariaDB refuse alors toute
     * reference non qualifiee a une colonne presente des deux cotes
     * ("Column 'x' in where clause is ambiguous", 1052).
     *
     * Prefixe la colonne avec le pseudo-alias "this." (deja utilise
     * ailleurs dans ce fichier - resolu en fin de requete par
     * querysanitize() vers le vrai nom de table) quand l'entite est
     * traduite et que la colonne n'est pas deja qualifiee - inoffensif
     * pour une entite non traduite (retournee inchangee).
     */
    private function qualifyIfAmbiguous(string $column): string
    {
        if ($this->object && $this->object->dvtranslate && strpos($column, '.') === false && strpos($column, ' ') === false) {
            return 'this.' . $column;
        }

        return $column;
    }

    public function where($column, $operator = self::NO_ARG, $value = self::NO_ARG, $link = "AND")
    {
        $this->endquery = "";

        //        if($this->initwhereclause && $link == "where")
        //            $link = "and";

        // Forme a deux arguments : la valeur est arrivee dans $operator,
        // $value n'a pas ete fourni du tout (toujours la sentinelle). NE
        // S'APPLIQUE QU'A LA BRANCHE COLONNE SIMPLE plus bas (le `else`
        // final) : ni la branche objet, ni la branche tableau, qui ont
        // chacune leur propre lecture de $operator/$value et ne doivent pas
        // changer.
        $twoArgForm = ($value === self::NO_ARG && $operator !== self::NO_ARG);
        if ($operator === self::NO_ARG) {
            $operator = null;
        }
        if ($value === self::NO_ARG) {
            $value = null;
        }

        if (is_object($column)) {
            $colmnlinkname = explode("\\", get_class($column));
            $colmnlinkname = $colmnlinkname[count($colmnlinkname) - 1];
            $attrib = strtolower($colmnlinkname) . '_id';
            if ($this->defaultjoinsetted) {
                $this->_where .= " " . $link . " " . strtolower($colmnlinkname) . '.id';
            } else
                $this->_where .= " " . $link . " " . $attrib;

            if ($column->getId()) {
                if ($operator == "not") {
                    $this->_where .= " != :$attrib";
                } else {
                    $this->_where .= " = :" . $attrib;
                }
                $this->parameters[$attrib] = $column->getId();
            } else {
                $this->_where .= " is null ";
            }
        } elseif (is_array($column)) {
            if (is_array($operator)) {
                for ($index = 0; $index < count($column); $index++) {
                    $this->where($column[$index], "=", $operator[$index]);
                }
            }
            // todo: handle the operation as we do for the lazyloading api
            /*elseif (is_array($column[0])) {
                foreach ($column as $value) {
                    $this->andwhere($value[0], $value[1], $value[2]);
                }
            }*/ else {
                foreach ($column as $key => $value) {
                    $this->where($key, "=", $value);
                }
            }
        } else {
            $column = $this->qualifyIfAmbiguous($column);
            $keymap = explode(".", $column);
            $attrib = str_replace(".", "_", $column);
            if (count($keymap) == 2) {
                $this->_where .= " " . $link . " " . $column;
            } else {
                $keymapattr = explode(" ", $column);
                if (count($keymapattr) >= 2) {
                    $column = $keymapattr[0];
                    unset($keymapattr[0]);
                    $extra = implode(" ", $keymapattr);
                    $this->_where .= " " . $link . ' `' . $column . "` " . $extra;
                } else
                    $this->_where .= " " . $link . ' `' . $column . "` ";
            }
            //$this->query .= " " . $link . " " . $column;

            // Forme a deux arguments : $operator porte la
            // VRAIE valeur. `null` se traite a part - "IS NULL" en dur (pas
            // dans $this->operators, et surtout jamais lie comme parametre
            // : `IS :attrib` n'est pas une comparaison valide). Pour toute
            // autre valeur (0, '', false compris), on redevient une
            // comparaison d'egalite ordinaire, qui tombe dans le meme
            // dispatch que la forme a trois arguments ci-dessous.
            if ($twoArgForm) {
                $value = $operator;
                if ($value === null) {
                    $this->_where .= " IS NULL ";
                    $operator = null;
                } else {
                    $operator = '=';
                }
            }

            if ($operator) {
                if (in_array($operator, $this->operators)) {

                    if (is_object($value)) {

                        if (get_class($value) == \dclass\devups\Tchutte\Column::class) {
                            //$this->parameters[$attrib] = ;
                            $this->_where .= " = (" . $value->column . ")";
                        } elseif (get_class($value) == QueryBuilder::class) {
                            //$this->parameters[$attrib] = ;
                            $this->_where .= " = (" . $value->getSqlQuery()['query'] . ")";
                        } else {
                            throw new Exception('The class Is not recognized');
                        }
                    } else {
                        $this->_where .= " " . $operator . " :$attrib";
                        $this->parameters[$attrib] = $value;
                    }
                } elseif (strtolower($operator) == "like") {
                    // CORRECTIF : c'etait $operator (litteralement
                    // la chaine "like"/"LIKE"...) qui etait interpole ici,
                    // jamais $value - `where('x','LIKE','foo')` produisait
                    // `LIKE '%LIKE%'`. Interpolation SQL directe, sans
                    // parametre lie : AUCUN appelant de ce depot n'atteint
                    // cette branche avec une valeur venue d'une requete HTTP
                    // (verifie par recherche exhaustive, voir le rapport),
                    // mais corrige quand meme - une chaine utilisateur ici
                    // serait une injection.
                    $this->_where .= " LIKE '%" . $value . "%' ";
                } else {
                    $this->_where .= " = :" . $attrib;
                    $this->parameters[$attrib] = $operator;
                }
            }
        }

        $this->initwhereclause = true;

        return $this;
    }

    /**
     * Memes sentinelles que where(), transmises TELLES
     * QUELLES - c'est ce qui fait survivre la distinction deux/trois
     * arguments a cette delegation (sans ca, `andwhere('col', 0)` passerait
     * TOUJOURS trois arguments explicites a where(), qui ne pourrait plus
     * distinguer cette forme d'un appel a trois arguments deliberes).
     */
    public function andwhere($column, $sign = self::NO_ARG, $value = self::NO_ARG)
    {
        return $this->where($column, $sign, $value, 'and');
    }

    public function orWhere($column, $sign = self::NO_ARG, $value = self::NO_ARG)
    {
        return $this->where($column, $sign, $value, 'OR');
    }

    public function whereId($id)
    {
        if (is_array($id)) {
            if (is_string(array_key_first($id)))
                return $this->where($id);

            return $this->whereIn("this.id", $id);
        }
        return $this->where("this.id", "=", $id);
    }

    public function orWhere_str($constraint)
    {
        return $this->where_str($constraint, 'OR');
    }

    public function where_str($constraint, $link = "AND")
    {
        $this->_where .= " " . $link . " " . $constraint;
        return $this;
    }

    public function whereRaw($constraint, $link = "AND")
    {
        $this->_where .= " " . $link . " " . $constraint;
        return $this;
    }

    /**
     * Groupe une serie de conditions entre parentheses, chacune posee dans
     * la closure via where()/orWhere()/whereLike()/etc. sur CE MEME
     * QueryBuilder - le groupe entier est ensuite rattache au reste par
     * $outerLink. Sert typiquement une recherche multi-champs (meme valeur
     * testee sur plusieurs colonnes en OR) sans jamais construire de SQL a
     * la main :
     *
     *   Agency::where('this.status', '=', 'active')
     *       ->whereGroup(function ($qb) use ($q) {
     *           $qb->whereLike('label', $q)
     *              ->orWhereLike('city', $q)
     *              ->orWhereLike('address', $q);
     *       })
     *       ->orderBy('this.label')
     *       ->get();
     *
     * Pas une surcharge de whereRaw() : whereRaw()/where_str() existent deja
     * et prennent une chaine SQL brute (deux noms pour la meme chose - la
     * duplication elle-meme est une trouvaille de ce lot, laissee telle
     * quelle, hors perimetre). Changer leur signature pour accepter une
     * closure aurait ete cassant pour tout appelant existant qui leur passe
     * une chaine.
     */
    public function whereGroup(callable $closure, string $outerLink = "AND")
    {
        $savedWhere = $this->_where;
        $this->_where = "";

        $closure($this);

        $groupSql = trim($this->_where);
        // le premier where()/whereLike() du groupe a prefixe son propre
        // connecteur (AND/OR) - superflu et invalide juste apres une
        // parenthese ouvrante, on ne garde que la condition elle-meme.
        $groupSql = preg_replace('/^(AND|OR)\s+/i', '', $groupSql);

        $this->_where = $savedWhere;
        if ($groupSql !== '') {
            $this->_where .= " " . $outerLink . " (" . $groupSql . ")";
        }
        $this->initwhereclause = true;

        return $this;
    }

    /**
     * Prochain nom de placeholder whereIn()/in()/notIn()/whereInReverse()/
     * whereBetween()/between() - jamais de collision au sein d'une meme
     * requete (meme principe que nextLikeParam()).
     */
    private function nextInParam(string $hint): string
    {
        $this->inParamSeq++;
        return 'in_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $hint) . '_' . $this->inParamSeq;
    }

    /**
     * ETAPE B.1 (v7.1) : la forme chaine brute de whereIn()/in()/notIn()
     * ("1,2,3") est DEPRECIEE - elle interpolait $values directement dans
     * le SQL (`IN ( $values )`), une injection SQL immediate pour tout
     * appelant qui y passerait une entree utilisateur non filtree. Aucun
     * appelant du depot n'utilise plus cette forme (verifie par recherche
     * exhaustive - tous passent desormais un tableau), mais elle reste
     * supportee pour la compatibilite : elle n'accepte plus qu'une liste
     * d'entiers separes par des virgules (le seul usage qu'elle ait jamais
     * eu de maniere sure), journalise un avertissement au premier appel,
     * et refuse (exception) tout ce qui ne ressemble pas a ça - jamais
     * d'interpolation, meme validee.
     *
     * @throws \InvalidArgumentException si $values n'est pas une liste
     *         d'entiers separes par des virgules.
     */
    private function parseDeprecatedRawIntegerList(string $values, string $method): array
    {
        \Log::warning("QueryBuilder::$method() : la forme chaine brute (\"\$values\" string) est depreciee, utilisez un tableau.");

        if (!preg_match('/^\s*\d+(\s*,\s*\d+)*\s*$/', $values)) {
            throw new \InvalidArgumentException(
                "QueryBuilder::$method() : la forme chaine brute n'accepte plus qu'une liste d'entiers separes par des virgules (recu : \"$values\")."
            );
        }

        return array_map('intval', array_map('trim', explode(',', $values)));
    }

    /**
     * find a value of a database column from a list of potential values
     * @param $column
     * @param $values
     * @param $negate
     * @return $this
     *
     * Parametree (:param lie par valeur) - qb_sanitize() entourait la
     * valeur de guillemets SANS echapper les guillemets qu'elle
     * contiendrait deja (injection SQL pour toute valeur utilisateur
     * contenant une apostrophe, meme passee en tableau). La forme chaine
     * brute est depreciee (voir parseDeprecatedRawIntegerList()).
     */
    public function whereIn($column, $values, $negate = "")
    {
        $column = $this->qualifyIfAmbiguous($column);

        if (is_array($values)) {
            $placeholders = [];
            foreach ($values as $value) {
                $attrib = $this->nextInParam($column);
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $value;
            }
            $this->_where .= " AND $column $negate IN (" . implode(",", $placeholders) . ")";
        } else if (is_object($values)) {
            $this->_where .= " AND $column $negate IN ( " . $values->getSqlQuery()['query'] . " ) ";
        } else {
            $placeholders = [];
            foreach ($this->parseDeprecatedRawIntegerList((string)$values, 'whereIn') as $id) {
                $attrib = $this->nextInParam($column);
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $id;
            }
            $this->_where .= " AND $column $negate IN (" . implode(",", $placeholders) . ")";
        }

        return $this;
        // return $this->where($column)->in($collection);
    }

    /**
     * find the value in list of database column's values,
     * you have to pay attention on string
     * @param $value
     * @param $columns
     * @param $negate
     * @return $this
     *
     * $value (la donnee utilisateur) est desormais liee en parametre ;
     * $columns reste interpole - ce ne sont jamais des valeurs mais des
     * NOMS DE COLONNE (identifiants SQL, pas de placeholder possible),
     * fournis par l'appelant (code), jamais directement par une requete.
     */
    public function whereInReverse($value, $columns, $negate = "")
    {
        $attrib = $this->nextInParam('reverse');
        $this->parameters[$attrib] = $value;

        if (is_array($columns)) {
            $columns = array_map(fn($col) => $this->qualifyIfAmbiguous($col), $columns);
            $this->_where .= " AND :$attrib $negate IN (" . implode(",", $columns) . ")";
        } else {
            $columns = $this->qualifyIfAmbiguous($columns);
            $this->_where .= " AND :$attrib $negate IN ( $columns )";
        }

        return $this;
        // return $this->where($column)->in($collection);
    }

    /**
     *
     * @param String|Array $values
     * @return $this
     *
     * Parametree - voir whereIn(). La forme chaine brute est depreciee
     * (voir parseDeprecatedRawIntegerList()).
     */
    public function in($values)
    {
        if (is_array($values)) {
            $placeholders = [];
            foreach ($values as $value) {
                $attrib = $this->nextInParam('in');
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $value;
            }
            $this->_where .= " in (" . implode(",", $placeholders) . ")";
        } else {
            $placeholders = [];
            foreach ($this->parseDeprecatedRawIntegerList((string)$values, 'in') as $id) {
                $attrib = $this->nextInParam('in');
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $id;
            }
            $this->_where .= " in (" . implode(",", $placeholders) . ")";
        }

        return $this;
    }

    /**
     * @param $values
     * @return $this
     *
     * Parametree - voir whereIn(). La forme chaine brute est depreciee
     * (voir parseDeprecatedRawIntegerList()).
     */
    public function notIn($values)
    {
        if (is_array($values)) {
            $placeholders = [];
            foreach ($values as $value) {
                $attrib = $this->nextInParam('notin');
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $value;
            }
            $this->_where .= " NOT IN (" . implode(",", $placeholders) . ")";
        } else {
            $placeholders = [];
            foreach ($this->parseDeprecatedRawIntegerList((string)$values, 'notIn') as $id) {
                $attrib = $this->nextInParam('notin');
                $placeholders[] = ":$attrib";
                $this->parameters[$attrib] = $id;
            }
            $this->_where .= " NOT IN (" . implode(",", $placeholders) . ")";
        }

        return $this;
    }

    public function whereNull($column)
    {
        $this->_where .= " AND $column IS NULL ";

        return $this;
    }

    public function whereNotNull($column)
    {
        $this->_where .= " AND $column IS NOT NULL ";

        return $this;
    }

    /**
     * Genere un nom de parametre lie sans collision au sein d'une meme
     * requete - plusieurs whereLike() sur des colonnes differentes (ou la
     * meme, dans un whereGroup()) ne doivent jamais partager un placeholder.
     */
    private function nextLikeParam(string $column): string
    {
        $this->likeParamSeq++;
        return 'like_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . '_' . $this->likeParamSeq;
    }

    /**
     * @param $column
     * @param $value
     * @param string $constraint specify the constraint of like. the value is either "" = %value%; l = value%; r = %value
     * @return $this
     *
     * Parametree (:param lie), plus d'interpolation directe de $value dans
     * le SQL - l'ancienne version (`LIKE '%" . $value . "%'`) etait une
     * injection SQL des que $value venait d'une entree utilisateur,
     * trouvee en construisant whereGroup() pour une recherche publique
     * (AgencyFrontController::directory()). Signature et comportement
     * observable inchanges pour tout appelant existant.
     */
    public function whereLike($column, $value, $constraint = "")
    {
        $attrib = $this->nextLikeParam($column);
        $pattern = $constraint === 'l' ? ($value . '%') : ($constraint === 'r' ? ('%' . $value) : ('%' . $value . '%'));
        $this->_where .= " AND $column LIKE :$attrib ";
        $this->parameters[$attrib] = $pattern;

        return $this;
    }

    public function orWhereLike($column, $value, $constraint = "")
    {
        $attrib = $this->nextLikeParam($column);
        $pattern = $constraint === 'l' ? ($value . '%') : ($constraint === 'r' ? ('%' . $value) : ('%' . $value . '%'));
        $this->_where .= " OR $column LIKE :$attrib ";
        $this->parameters[$attrib] = $pattern;

        return $this;
    }

    public function like($value)
    {
        $attrib = $this->nextLikeParam('chain');
        $this->_where .= " LIKE :$attrib ";
        $this->parameters[$attrib] = '%' . $value . '%';

        return $this;
    }

    public function _like($value)
    {
        $attrib = $this->nextLikeParam('chain');
        $this->_where .= " LIKE :$attrib ";
        $this->parameters[$attrib] = '%' . $value;

        return $this;
    }

    public function like_($value)
    {
        $attrib = $this->nextLikeParam('chain');
        $this->_where .= " LIKE :$attrib ";
        $this->parameters[$attrib] = $value . '%';

        return $this;
    }

    public function groupBy($critere)
    {
        $this->_group_by .= " " . $critere;
        return $this;
    }

    /**
     * ETAPE B.2 (v7.1) : parametree - les bornes etaient interpolees
     * entre guillemets simples SANS echappement (injection SQL pour toute
     * valeur contenant une apostrophe, ex. l'operateur Lazyloading "btw").
     */
    public function between($value1, $value2)
    {
        $attrib1 = $this->nextInParam('between_from');
        $attrib2 = $this->nextInParam('between_to');
        $this->_where .= " BETWEEN :$attrib1 AND :$attrib2";
        $this->parameters[$attrib1] = $value1;
        $this->parameters[$attrib2] = $value2;
        return $this;
    }

    /**
     * ETAPE B.2 (v7.1) : meme correctif que between().
     */
    public function whereBetween($column, $values)
    {
        $attrib1 = $this->nextInParam($column . '_from');
        $attrib2 = $this->nextInParam($column . '_to');
        $column = $this->qualifyIfAmbiguous($column);
        $this->_where .= " AND $column BETWEEN :$attrib1 AND :$attrib2";
        $this->parameters[$attrib1] = $values[0];
        $this->parameters[$attrib2] = $values[1];
        return $this;
    }

    public function orderBy($critere, $order = "")
    {
        if (empty($this->_order_by))
            $this->_order_by = " ORDER BY " . $critere . " " . $order;
        else
            $this->_order_by .= ", " . $critere . " " . $order;
        return $this;
    }

    public function rand()
    {
        $this->_order_by .= " ORDER BY RAND() ";
        return $this;
    }

    public function limit($start = 1, $max = null)
    {
        if ($start < 0) {
            $qb = $this;
            $i = (int)$start;
            $nbel = $qb->count();
            if ($nbel + $i > 0) {

                //$i += $nbel;
                $this->_limit = " LIMIT " . ($nbel + $i) . ", " . abs($nbel);
                return $this;
            }
            $start = 0;
            $max = $nbel;
        }

        if ($max)
            $this->_limit = " LIMIT " . $start . ", " . $max;
        else
            $this->_limit = " LIMIT " . $start;
        // $this->_where .= $this->_limit;
        return $this;
    }

    private function initquery($columns)
    {
        if ($this->tablecollection)
            return " select " . $columns . " from  {$this->_from} " .
                $this->tablecollection . " ";

        return " select " . $columns . " from {$this->_from} ";
    }

    protected function querysanitize($sql)
    {
        return str_replace("this.", "{$this->_from}.", $sql);
    }

    public function getSqlQuery($column = "*")
    {
        if (!$this->_set) {
            $this->initSelect();
            $this->sequensization();
        }
        $query = $this->query;

        foreach ($this->parameters as $search => $value) {
            $query = str_replace(":" . $search . "", $value, $query);
        }
        return ["query" => $query, "sql" => $this->query, "parameters" => $this->parameters];
    }

    public function exec($action = 0)
    {
        if (in_array($action, [DBAL::$FETCH, DBAL::$FETCHALL]))
            if ($this->sequensized)
                $sql = $this->query;
            else
                $sql = $this->querysanitize($this->initquery($this->columns) . $this->defaultjoin . $this->query . $this->_where);

        if ($this->sequensized)
            $sql = $this->query;
        else
            $sql = $this->querysanitize($this->query . $this->_where . $this->endquery);

        return $this->executeDbal($sql, $this->parameters, $action);
    }


    public function first($id_lang = null, $nullable = false)
    {

        $this->limit_iteration = true;

        if ($id_lang)
            $this->setLang($id_lang);

        return $this->getInstance("*", $nullable);
    }

    /**
     * @param bool $recursif
     * @param array $collect
     * @return type|null
     * @deprecated uses  firstOrNull
     */
    public function __firstOrNull()
    {
        $model = $this->first($this->id_lang, true);

        if ($model->getId())
            return $model;

        return null;
    }

    public function firstOrNull($id_lang = null)
    {
        if (!$id_lang)
            $id_lang = $this->id_lang;

        $model = $this->first($id_lang, true);

        if (is_null($model))
            return null;

        // todo: implement the primary key validation
        //        if (property_exists($model, "id")) {
        //            if ($model->getId())
        return $model;
        /* } else
             return $model;*/
    }


    public function lastOrNull($id_lang = null)
    {
        if (!$id_lang)
            $id_lang = $this->id_lang;

        $model = $this->getLast($id_lang, true);

        if (is_null($model))
            return null;

        // todo: implement the primary key validation
        //        if (property_exists($model, "id")) {
        //            if ($model->getId())
        return $model;
        /* } else
             return $model;*/
    }

    /**
     * @param $callback
     * @param bool $recursif
     * @param array $collect
     * @return type|null
     * @deprecated  use firstOr
     */
    public function __firstOr($callback, $recursif = true, $collect = [])
    {
        $this->firstOr($callback, $recursif);
    }

    public function firstOr($callback, $recursif = true, $collect = [])
    {

        $model = $this->getInstance($recursif, $collect);

        //        if(is_bool($model))
        //            dv_dump($model, $this->getSqlQuery());

        if ($model)
            return $model;

        if (is_callable($callback))
            return $callback();

        dv_dump("callback is not callable");
    }

    /**
     * @param $constraint
     * @param $data
     * @param $id_lang
     * @return array|Object
     */
    public function firstOrCreate($constraint, $data = [], $id_lang = null)
    {

        $qbclone = clone $this;
        $model = $this->setLang($id_lang)->where($constraint)->firstOrNull();
        if ($model)
            return $model;

        if (!$data)
            $data = $constraint;

        // $data += $constraint;

        $id = DBAL::_createDbal($this->table, $data);

        return $qbclone->setLang($id_lang)->where("this.id", $id)->first();
    }

    /**
     * @param $constraint
     * @param $data
     * @param $id_lang
     * @return array|Object|type
     */
    public function firstOrNew($constraint, $data = [], $id_lang = null)
    {

        $model = $this->setLang($id_lang)->where($constraint)->firstOrNull();
        if ($model)
            return $model;

        if (!$data)
            $data = $constraint;

        $data += $constraint;
        foreach ($data as $key => $value)
            $this->object->{$key} = $value;

        return $this->object;
    }

    public function getLast($id_lang = null, $nullable = false)
    {
        return $this->setLang($id_lang)
            ->orderBy($this->table . ".id DESC ")
            ->limit(1)
            ->getInstance("*", $nullable);
    }

    public function getLastOrNull($id_lang = null)
    {
        return $this->setLang($id_lang)
            ->orderBy($this->table . ".id DESC ")
            ->limit(1)
            ->getInstance("*", true);
    }

    /**
     * return the index of a specific entity within a list
     * @param $index
     * @param $id_lang
     * @return array|mixed|Object|type|null
     */
    public function getIndex($index, $id_lang = null)
    {
        //        if (is_numeric($recursif))
        //            $this->limit_iteration = $recursif;

        if (!$this->id_lang)
            $this->setLang($id_lang);
        // $this->setCollect($collect);
        $i = (int)$index;
        if ($i < 0) {
            $nbel = $this->count();
            if ($nbel == 1)
                return $this->object;

            $i += $nbel;
        }

        return $this->limit($i - 1, $i)->getInstance();
    }

    /**
     * retrieve entity from a specific index
     * @param $index
     * @param $id_lang
     * @return array|mixed|Object|type|null
     */
    public function getFromIndex($index, $id_lang = null)
    {
        //        if (is_numeric($recursif))
        //            $this->limit_iteration = $recursif;

        if (!$this->id_lang)
            $this->setLang($id_lang);
        // $this->setCollect($collect);
        $i = (int)$index;
        if ($i < 0) {
            $nbel = $this->count();
            if ($nbel == 1)
                return $this->object;

            $i += $nbel;
        }

        return $this->limit($i - 1, $i)->getInstance();
    }

    public function __exportAllRow($callback)
    {
        return $this->__findAllRow($this->querysanitize($this->initquery($this->columns) . $this->query . $this->_where), $this->parameters, $callback);
    }

    /**
     * @return array
     * @deprecated use get
     */
    public function __getAllRow()
    {
        return $this->get();
    }

    public function initBulk($aggregation = false)
    {

        return $this;
    }
    public function append($entity)
    {
        //        $fullkeys = $this->fieldNames + $this->associations;
        //        dv_dump($this->fieldNames, $this->associations);
        $suffix = count($this->_query_values);
        $keys = $entity->entityKeyInsert($this->fieldNames, $this->associations, $suffix);

        $this->_query_params = array_merge($this->_query_params, $keys);
        $this->_query_values[] = '(:' . implode(", :", array_keys($keys)) . ')';
        //        $this->_query_params[] = '(:' . implode(", :", array_keys($keys)).')';
        //        $this->en
        return $this;
    }

    protected function initSelect($aggregation = false)
    {

        if ($this->_with)
            foreach ($this->_with as $entity => $columns) {
                if (is_string($columns) || is_callable($columns))
                    continue;

                if (strpos($entity, '\\')) {
                    $entity = explode('\\', $entity)[1];
                    // $this->addColumn($entity . ".id", $entity . "_id");
                }

                foreach ($columns as $column) {
                    if (is_array($column)) {
                        $col_entity = array_keys($column)[0];
                        if (strpos($col_entity, '\\')) {
                            $columns = $column[$col_entity];
                            $d = explode('\\', $col_entity);
                            $col_entity = $d[1];
                            $alias = $entity . '_' . $d[1];
                            if (!in_array($alias, $this->_join_table))
                                $this->leftJoinOn($d[0], $alias, " $alias.id = $entity.$d[1]" . "_id ");
                        } else {
                            $alias = $entity . '_' . $col_entity;

                            if (!in_array($alias, $this->_join_table))
                                $this->leftJoinOn($col_entity, $alias, " $alias.id = $entity.$col_entity" . "_id ");

                            $columns = $column[$col_entity];
                        }
                        foreach ($columns as $column) {
                            if (strpos($column, '.') !== false) {
                                if (isset($this->_with_lang[$entity . "_" . $col_entity]))
                                    $this->_with_lang[$entity . "_" . $col_entity] = [];

                                $this->_with_lang[$entity . "_" . $col_entity][] = str_replace('l.', '', $column);
                                //                        $this->addColumn(/, $entity . "_" . $column, true);
                            } else
                                $this->addColumn($alias . "." . $column, $entity . "_" . $col_entity . "_" . $column, true);
                        }
                    } elseif (strpos($column, '.') !== false) {
                        if (isset($this->_with_lang[$entity]))
                            $this->_with_lang[$entity] = [];

                        $this->_with_lang[$entity][] = str_replace('l.', '', $column);
                        //                        $this->addColumn(/, $entity . "_" . $column, true);
                    } else
                        $this->addColumn($entity . "." . $column, $entity . "_" . $column, true);
                }
            }


        if ($this->custom_columns_array)
            $this->custom_columns = implode(", ", $this->custom_columns_array);

        $this->query = " SELECT {$this->_select_option} ";
        if ($this->custom_columns != "" && !$aggregation)
            $this->query .= ", {$this->custom_columns}";

        if ($this->object->dvtranslate && !$aggregation) {
            $this->custom_columns_array = [
                ...$this->custom_columns_array,
                ...$this->object->dvtranslated_columns
            ];
            $thislang = $this->table . "_lang";
            if ($this->id_lang) {
                $this->query .= ", `" . $thislang . "`.`" . implode("`, $thislang.`", $this->object->dvtranslated_columns) . "`";
                $this->_join .= " left join $thislang on ($thislang.{$this->table}_id = `{$this->table}`.id  and $thislang.lang_id = {$this->id_lang}) ";
            } else {
                $groupment = [];
                foreach ($this->object->dvtranslated_columns as $col)
                    $groupment[] = "  GROUP_CONCAT( CONCAT('[', l.iso_code, ']', $thislang.$col) SEPARATOR '' ) AS $col ";
                $this->query .= ", " . implode(", ", $groupment) . " ";
                $this->_join .= " left join $thislang on ($thislang.{$this->table}_id = `{$this->table}`.id ) ";
                // ETAPE C.2 (v7.1) - D15 : cette jointure etait un INNER
                // JOIN ("join" simple) - une ligne SANS aucune ligne
                // "{$this->table}_lang" produit lang_id = NULL (jointure
                // precedente), et "NULL = l.id" n'est VRAI pour aucune
                // ligne de dvups_lang : l'INNER JOIN eliminait alors la
                // ligne de base ENTIERE (pas seulement ses colonnes
                // traduites). LEFT JOIN : la ligne de base reste, le
                // GROUP_CONCAT devient simplement NULL/vide (jamais une
                // ligne perdue ni une erreur).
                $this->_join .= " left join dvups_lang l on ($thislang.lang_id = l.id ) ";
                $this->_group_by .= ("{$this->table}.id");
            }
        }

        $this->query .= " FROM " . $this->_from;

        if ($this->_join)
            $this->query .= " {$this->_join} ";

        $this->query .= $this->tablecollection;

        //todo : directly add the query to get the additional attribute here

    }

    public function getInstanceAtIndex($index, $recursif = true, $collect = [])
    {
        $this->setCollect($collect);
        $i = (int)$index;
        return $this->limit($i - 1, $i)->getInstance($recursif);
    }

    public function count($column = "*", $as = "")
    {
        if ($as)
            $as = " AS " . $as;

        $this->_select = "";
        $this->custom_columns = "";
        $this->_select_option = " COUNT(" . $column . ") $as ";
        return $this->getValue();
    }

    public function sum($column, $as = "")
    {
        if ($as)
            $as = " AS " . $as;

        $this->_select_option = " SUM($column) $as ";
        return $this->getValue();
    }

    public function avg($column, $as = "")
    {
        if ($as)
            $as = " AS " . $as;

        $this->_select_option = " AVG(" . $column . ") $as ";
        return $this->getValue();
    }

    public function max($column, $as = "")
    {
        if ($as)
            $as = " AS " . $as;

        $this->_select_option = " MAX(" . $column . ") $as ";
        return $this->getValue();
    }

    public function min($column, $as = "")
    {
        if ($as)
            $as = " AS " . $as;

        $this->_select_option = " MIN(" . $column . ") $as ";
        return $this->getValue();
    }

    /**
     * @param int $index
     * @param bool $id_lang
     * @return type|null
     */
    public function index($columnOrder, $alias)
    {

        $this->_select_option = " ROW_NUMBER() OVER ( $columnOrder ) AS $alias ";
        return $this->getValue();
        return $this->getIndex($index, $id_lang);
    }

    /**
     * @param int $index
     * @param bool $id_lang
     * @return $this
     */
    public function addIndex($columnOrder, $alias)
    {
        $this->custom_columns = " ROW_NUMBER() OVER ( ORDER BY $columnOrder ) AS $alias ";
        return $this;
    }

    public function distinct($column)
    {
        return $this->get(" DISTINCT " . $column);
    }


    /**
     * @param bool $recursif
     * @param array $collect
     * @return array
     * @deprecated use get
     */
    public function __getAll($column = true, $collect = [])
    {
        return $this->get($column);
    }

    /**
     * @param $callback
     * @param $return_data
     * @return array|false|null
     */
    public function cursor($callback, $return_data = false, $cache = null)
    {

        if ($cache) {
            $this->cache_key = $cache;

            global $devups_cache;

            if (isset($devups_cache[$this->cache_key])) {

                $arg = \DClass\lib\Util::get_func_argNames($callback);
                foreach ($devups_cache[$this->cache_key] as $i => $row) {
                    if (count($arg) > 1)
                        $callback($row, $i);
                    else
                        $callback($row);
                }
                if ($return_data)
                    return $devups_cache[$this->cache_key];

                return null;
            } else
                $devups_cache[$this->cache_key] = [];
        }

        $this->initSelect();
        $this->sequensization();

        return $this->__cursor($this->query, $this->parameters, $callback, $return_data);
    }

    /**
     * @return Object
     * @deprecated use row
     */
    public function __getOneRow()
    {
        return $this->getInstance();
    }

    /**
     * @param bool $recursif
     * @param array $collect
     * @return mixed|null
     * @deprecated use getInstance
     */
    public function __getOne($recursif = true, $collect = [])
    {
        return $this->getInstance();
    }

    /**
     * @param bool $recursif
     * @param false $defaultjoin
     * @return mixed
     * @deprecated use count
     */
    public function __countEl($recursif = true, $defaultjoin = false)
    {
        //$this->setCollect($collect);
        if ($defaultjoin):
            $this->initdefaultjoin();
        endif;

        return $this->__count($this->querysanitize($this->initquery($this->columnscount) . $this->defaultjoin . $this->query . $this->_where), $this->parameters, false, $recursif);
    }

    /**
     *
     * @param string $order
     * @return \dclass\devups\Datatable\Lazyloading | $this
     */
    public function lazyloading($callback = null, $debug = false)
    {

        $ll = new \dclass\devups\Datatable\Lazyloading($this->object);
        $ll->debug = $debug;
        //$ll->start($this->object);

        $ll->lazyloading($this->object, $this);
        if (is_callable($callback))
            $callback($ll);

        //        if ($limit)
        //            $ll->setPerPage($limit);
        return $ll;
    }

    /**
     *
     * @param string $order
     * @return \dclass\devups\Datatable\Lazyloading
     */
    public function perPage($perpage = 10)
    {

        $ll = new \dclass\devups\Datatable\Lazyloading($this->object, $this);

        return $ll->setPerPage($perpage);
    }

    /**
     *
     * @param string $order
     * @return \dclass\devups\Datatable\Lazyloading
     */
    public function nextPage($page = 1)
    {

        $ll = new \dclass\devups\Datatable\Lazyloading($this->object, $this);

        return $ll->setNext($page);
    }

    /**
     *
     * @param string $page
     * @param string $per_page
     * @return $this
     */
    public function skip($page = 1, $per_page = 10)
    {
        if ($page > 0) {
            $next = ($page - 1) * $per_page;
        } else {
            $next = 1;
        }

        return $this->limit($next, $per_page);
    }

    public function row()
    {

        $this->initSelect();
        $this->query .= $this->_join;
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        return $this->__findOneRow($this->query, $this->parameters);
    }

    /*public function getRows($callbackexport = null)
    {
        $this->initSelect();
        $this->query .= $this->_join;
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        return $this->__findAllRow($this->query, $this->parameters, $callbackexport);
    }*/

    public function getInstance($column = "*", $nullable = false)
    {

        $this->select($column);

        $this->initSelect();
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        if ($nullable)
            return $this->__findOneRow($this->query, $this->parameters);

        return $this->__findOneRow($this->query, $this->parameters) ?? new $this->objectName;
    }

    /**
     * ETAPE C.3 (v7.1) - D3 : setLang() devait etre appele AVANT
     * initSelect() (mesure le 2026-11-04 : initSelect() lit
     * $this->id_lang pour choisir entre la jointure mono-langue et le
     * GROUP_CONCAT bilingue - appele apres, $id_lang ne servait jamais a
     * RIEN ici, la requete etait TOUJOURS construite en mode bilingue,
     * meme avec une langue explicitement demandee). Contourne jusqu'ici
     * par plusieurs appelants (Status::buildCacheDataFor(),
     * Tree_item::getmainmenu()) via ->setLang($id_lang)->get() explicite
     * avant cet appel - toujours valide, setLang() est idempotente.
     */
    public function get($column = "*", $id_lang = null)
    {
        $this->select($column);

        if (!$this->id_lang)
            $this->setLang($id_lang);

        $this->initSelect();
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        return $this->__findAll($this->query, $this->parameters);
    }

    public function trashed($column = "*", $id_lang = null)
    {
        $this->dvtrashed = true;
        $this->select($column);
        $this->initSelect();
        $this->sequensization();

        if (!$this->id_lang)
            $this->setLang($id_lang);

        if (self::$debug)
            return $this->getSqlQuery();

        return $this->__findAll($this->query, $this->parameters);
    }

    public function getRows($column = "*", $callback = null)
    {
        $this->select($column);
        $this->initSelect();
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        //        if ($asobject)
        //            return (object)$this->__findAllRow($this->query, $this->parameters, $callback);

        return $this->__findAllRow($this->query, $this->parameters, $callback);
    }

    public function getValue()
    {
        // todo: put select() here
        $this->initSelect(true);
        $this->sequensization(true);

        if (self::$debug)
            return $this->getSqlQuery();

        $value = $this->executeDbal($this->query, $this->parameters, DBAL::$FETCH);
        if (is_array($value))
            return $value[0];

        return $value;
    }

    public function getValues()
    {
        // todo: put select() here
        $this->initSelect();
        $this->sequensization();

        if (self::$debug)
            return $this->getSqlQuery();

        return $this->executeDbal($this->query, $this->parameters, DBAL::$FETCH);
    }
}
