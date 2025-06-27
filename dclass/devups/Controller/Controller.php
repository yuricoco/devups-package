<?php

namespace dclass\devups\Controller;

use Auth;
use dclass\devups\Datatable\Lazyloading;
use Dvups_lang;
use Exception;
use Genesis as g;
use Philo\Blade\Blade;
use phpDocumentor\Reflection\Types\Self_;
use QueryBuilder;
use ReflectionAnnotatedClass;
use ReflectionClass;
use Request;

/**
 * class Controller 1.0
 *
 * @OA\Info(title="DEVUPS API", version="1.0")
 * @author yuri coco
 */
class Controller
{

    public static $sidebar = true;
    public static $tablename = "";
    public static $formname = "";
    public static $ctrlname = "";
    public static $entityname = "";
    public static $classMagic = "";

    protected $error = [];
    protected $nopersist = [];
    protected $error_exist = false;
    protected $entity = null;
    public $indexView = "default.index";

    public function setEntity($entity){
        $this->entity = $entity;
    }

    /**
     * this method is used by devups from the admin/services.php file to manage router as we use to do at the front App.php
     * @return false|int|mixed
     */
    public static function serve($route, $entity)
    {

        global $viewdir;

        $viewdir[] = ROOT.'src/'.$entity['path'] . 'Resource/views';
        ///$viewdir[] = $entity->dvups_module->hydrate()->moduleRoot() . 'Resource/views';

        //dv_dump($viewdir);
        $ctrl = ucfirst($entity['name']) . "Controller";
        return Request::Controller($route, Request::get('path'), $ctrl);
    }

    /**
     * this method is used by devups from the admin/services.php file to manage router as we use to do at the front App.php
     * @return false|int|mixed
     */
    public static function views($route, $entity)
    {

        global $viewdir, $moduledata;

        $viewdir[] = ROOT.'src/'.$entity['path'] . 'Resource/views';
        //$moduledata = $entity->dvups_module;
        $ms = require ROOT.'config/module_configurations.php';
        $moduledata = $ms[$entity['module']];

//        $admin = getadmin();
//        $moduledata->dvups_entity = $admin->dvups_role->collectDvups_entityOfModule($moduledata);

        //dv_dump($viewdir);
        $ctrl = ucfirst($entity['name']) . "Controller";
        return Request::Controller( $route , Request::get('path'), $ctrl);

    }
    /**
     * this method is used by devups from the admin/services.php file to manage router as we use to do at the front App.php
     * @return false|int|mixed
     */
    public static function viewsModule($route, $module)
    {

        global $viewdir, $moduledata;

        $viewdir[] = ROOT.'src/'.$module['path'] . 'Resource/views';
        $moduledata = $module;

        $ctrl = ucfirst($module['name']) . "";
        return Request::Module( $route , Request::get('path'), $ctrl);

    }

    public function __construct()
    {

        self::$classname = ucfirst(self::$entityname);
        self::$tablename = self::$classname . "Table";
        self::$formname = self::$classname . "Form";
        self::$ctrlname = self::$classname . "Ctrl";

    }

    /**
     * @return $this
     * @throws \ReflectionException
     */
    public static function i()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return $reflection->newInstance();
    }

    public static $eventListners = [
        "after" => [
            "create" => []
        ]
    ];

    public static function addEventListener($position, $action, $classname)
    {
        if (isset(self::$eventListners[$position][$action][$classname]))
            self::$eventListners[$position][$action][$classname][] = $classname;
        else
            self::$eventListners[$position][$action][] = $classname;
    }

    public static function addEventListenerAfterCreate($classname, $method)
    {
        $classname = strtolower($classname);
        $eventcollector = self::$eventListners["after"]["create"];
        if (isset($eventcollector[$classname]))
            $eventcollector[$classname][] = $method;
        else
            $eventcollector[$classname] = [$method];

        self::$eventListners["after"]["create"] = $eventcollector;

    }

    public static function getclassname($public_access = [])
    {

        $classname = str_replace('-', '_', Request::get('dclass'));

        // we first check if the entity is in the public access list then try to shown all the available entity
        // this is the first level security for webservice. the secon one will be the auth with annotation.
        if (!isset($public_access[$classname])){
            /*echo json_encode([
                "success" => false,
                "message" => "entity " . $classname . " not found",
                "available" => \Dvups_entity::whereIn("this.name", $public_access)->get(),
            ]);
            die;*/
            throw new Exception("entity " . $classname . " has no public access or has not been found", 22 );
        }
        // if the entity is available for webservice we check if it is stored in the database
//        $exist = \Dvups_entity::where("this.name", $classname)->count();

        $global_config = require ROOT.'config/dvups_configurations.php';
        if (!isset($global_config["".ucfirst($classname)])) {
            /*echo json_encode([
                "success" => false,
                "message" => "entity " . $classname . " not found",
                "available" => $public_access ?
                    \Dvups_entity::whereIn("this.name", $public_access)->get()
                    : \Dvups_entity::all(),
            ]);
            die;*/
            header('HTTP/1.0 500 Server error');
            throw new Exception("entity " . $classname . " not found in \$global_config", 22 );
        }

        // if everything is OK we set the classname and start the next process.
        self::$entityname = $classname;
        self::$classMagic = $public_access[$classname];
        return $classname;
    }


    /**
     *
     * @param type $resultCtrl controller method
     */
    public static function renderTemplate($view, $data)
    {
        g::render($view, $data);
    }

    /**
     * 11/11/2017
     *
     * @param \stdClass $Dao
     * @param int $par_page
     * @param type $next
     * @return type
     */
    public static function lastpersistance($entity)
    {
        $classname = strtolower(get_class($entity));

        return array('success' => true, // pour le restservice
            'classname' => $classname,
            'listEntity' => [$entity],
            'detail' => '');
    }

    /**
     * Hydrate l'entité passé en parametre sur la base de la variable post ou dans le cas des requete
     * asynchrone, d'une chaine formater en json ou alors les arrays.
     *
     * @example $jsondata as \Array dans le cas de la persistance d'une
     * entité imbriqué dans celle courante
     *
     * @param stdClass $object l'instance de l'entité à hydrater
     * @param Mixed ( String or Array ) $jsondata optionnel
     * @return type
     * @throws InvalidArgumentException
     */
    public function form_generat($object, $data = null, $deeper = false)
    {
        return $this->form_fillingentity($object, $data, $deeper);
    }

    /**
     * @param $object
     * @param null $data
     * @param null $entityform
     * @param bool $deeper
     * @return mixed
     */
    public function form_fillingentity($object, $data = null)
    {
        $this->error = [];
        if (!is_object($object))
            throw new \InvalidArgumentException('$object must be an object.');

        $classname = self::$entityname;
        if (isset($_FILES[$classname . '_form'])) {
            if ($object->getId()) {
                $object = $object->hydrate();
            }
            foreach ($_FILES[$classname . "_form"]['name'] as $key_form => $value_form) {
                if (!method_exists($object, 'upload' . ucfirst($key_form))) {
                    $this->error[$key_form] = " You may create method " . 'upload' . ucfirst($key_form) . " in entity. ";
                    continue;
                }
                $object->{'upload' . ucfirst($key_form)}();
                //self::addEventListenerBeforeCreate(get_class($this->entity), 'upload' . ucfirst($key_form));
            }

            if (!$data) {
                return $object;
            }

        } elseif ($object->getId()) {
//            if($object->dvtranslate)
//                $object = $object->__show($deeper, \Dvups_lang::defaultLang()->getId());
//            else
            $object = $object->hydrate();
        }
        if (!$data) {
            return $object->hydrate();
        }

        global $_ENTITY_FORM;
        $_ENTITY_FORM = $data;

        if ($object->getId()) {
            //$object = $object->__show($deeper);
            $object->setUpdatedAt(date(\DClass\lib\Util::dateformat));
        } else
            $object->setCreatedAt(date(\DClass\lib\Util::dateformat));


//            if($jsondata){
//                $object_array = Controller::formWithJson ($object, $jsondata, $change_collection_adresse);
//            }else{
        $this->entity = $object;

        return $this->formWithPost($object);
//            }

    }

    /**
     * @param $object
     * @param $entityform
     * @return mixed
     * @throws \ReflectionException
     */
    private function formWithPost(\Model $object)
    {
        global $_ENTITY_FORM;
        global $_ENTITY_COLLECTION;
        global $__controller_traitment;

        $__controller_traitment = true;
        $_ENTITY_COLLECTION = [];
        /**
         * dans le cas où la variable $_POST serait vide on met un element pour pouvoir traiter
         * les collections d'objet. ceci n'influence en rien l'hydratation des autres proprietés
         */
        //$_ENTITY_FORM["devups_entitycollection"] = "empty";

        //$entitycore = new \stdClass();
        //$entitycore->field = json_decode($_POST["dvups_form"][strtolower(get_class($object))], true);
        //$entitycore->field = $object->entityKeyForm();

        global $em, $global_config;
        $classlang = get_class($object);
        $metadata = $em->getClassMetadata("\\" . $classlang);
        $fieldNames = array_keys($metadata->fieldNames);
        $fieldNames = array_merge($fieldNames, $object->dvtranslated_columns);
        $associationMappings = $metadata->associationMappings;

        foreach ($associationMappings as $key => $association){
            $key_form = $key.'.id';
            $ent = $global_config[$association['targetEntity']];
            if (ucfirst($association['fieldName']) != $ent['name']) {
                $key_form = strtolower($ent['name'].'\\'.$key . '.id');
            }

            if (!array_key_exists($key_form, $_ENTITY_FORM))
                continue;

            if (!class_exists($association['targetEntity']))
                continue;

            $value_form = $_ENTITY_FORM[$key_form];
            $currentfieldsetter = 'set' . ucfirst($association['fieldName']);
            if (!is_numeric($value_form)) {
                continue;
            }

            $value2 = new $association['targetEntity'];

            $value2->setId($value_form);

            if (!method_exists($object, $currentfieldsetter)) {
                $object->{$key} = $value2;
            } elseif ($error = call_user_func(array($object, $currentfieldsetter), $value2)) //$value2->__show(false)
                $this->error[$key] = $error;

            unset($_ENTITY_FORM[$key_form]);
        }

        foreach ($_ENTITY_FORM as $key_form => $value_form) {
            $result = explode(":", $key_form);
            $attrib = $result[0];
            $key = $result[0];
            if (isset($result[1])) {
                if ($result[1] == "upload") {
                    self::addEventListenerAfterCreate(get_class($this->entity), 'upload' . ucfirst($result[1]));
                    //self::$eventListners['after']['create'] = 'upload'.ucfirst($meta[1]);
                    continue;
                }
                $setter = 'set' . ucfirst($result[1]);
            } else
                $setter = 'set' . ucfirst($result[0]);

            //if ($key_form == $key) {

            if ($setter == 'setNull' || in_array($attrib, $this->nopersist)) {
                continue;
            }

            $currentfieldsetter = $setter;
            //var_dump($key_form, strpos($key_form, "::"));
            if (strpos($key_form, "::")) {

                $entitname = str_replace("::values", "", $key);

                if (strpos($entitname, "\\")) {
                    $classtyperst = explode("\\", $entitname);
                    $classtype = $classtyperst[0];
                    $entitname = $classtyperst[1];
                } else
                    $classtype = $entitname;

                $currentfieldsetter = 'set' . ucfirst($entitname);

                if (!class_exists($classtype)) {

                    if (!method_exists($object, $currentfieldsetter)) {
                        $this->error[$key] = " You may create method " . $currentfieldsetter . " in entity. ";
                    } elseif ($error = call_user_func(array($object, $currentfieldsetter), implode(",", $value_form)))
                        $this->error[$key] = $error;

                    continue;
                }

                if ($object->getId()) {
                    $values = $object->inCollectionOf($classtype);

                    $toadd = array_diff($value_form, $values);
                    $todrop = array_diff($values, $value_form);
                } else {
                    $toadd = $value_form;
                    $todrop = [];
                }

                $_ENTITY_COLLECTION[] = [
                    'owner' => $object->getId()
                ];

                $collection = [];
                $oldselection = [];

                // if ($_ENTITY_FORM[$key]) {
                if ($toadd) {
                    // foreach ($_ENTITY_FORM[$key] as $val) {
                    foreach ($toadd as $val) {

                        $reflect = new \ReflectionClass($classtype);
                        $value2 = $reflect->newInstance();
                        $value2->setId($val);
                        $collection[] = $value2;
                    }
                    $_ENTITY_COLLECTION[]["selection"] = true;
                } else {
                    $_ENTITY_COLLECTION[]["selection"] = false;
                }

                if ($todrop) {

                    foreach ($todrop as $val) {

                        $reflect = new \ReflectionClass($classtype);
                        $value2 = $reflect->newInstance();
                        $value2->setId($val);
                        $oldselection[] = $value2;
                    }
                }

                if ($collection)
                    $_ENTITY_COLLECTION[]["toadd"] = true;

                if ($todrop) {
                    $_ENTITY_COLLECTION[]["todrop"] = $oldselection;//array_values($todrop);
                }

                if (!method_exists($object, $currentfieldsetter)) {
                    $this->error[$key] = " You may create method " . $currentfieldsetter . " in entity. ";
                } elseif ($error = call_user_func(array($object, $currentfieldsetter), $collection))
                    $this->error[$key] = $error;

            }
            else {

                /*if (strpos($key_form, ".id")) {
                    // && is_object ($value['options'][0])

                    $entitname = str_replace(".id", "", $key_form);
                    if (strpos($entitname, "\\")) {
                        $classtyperst = explode("\\", $entitname);
                        $classtype = $classtyperst[0];
                        $entitname = $classtyperst[1];
                    } else
                        $classtype = $entitname;


                } else { */
                    if (!method_exists($object, $currentfieldsetter)) {
                        if (in_array($key, $fieldNames))
                            $object->{$key} = $_ENTITY_FORM[$key];
                        else
                            $this->error[$key] = " You may create method " . $currentfieldsetter . " in entity. ";
                    } elseif ($error = call_user_func(array($object, $currentfieldsetter), $_ENTITY_FORM[$key]))
                        $this->error[$key] = $error;
                    //}

//                }
            }

        }

        return $object;
    }


    /**
     * @param $object
     * @param $jsonform
     * @param bool $deeper
     * @return null
     */
    public function hydrateWithJson($object, $jsonform, $deeper = false)
    {

        if ($object->getId()) {
            $object = $object->hydrate();
        }

        $this->entity = $object;
        global $em;
        $classlang = get_class($object);
        $metadata = $em->getClassMetadata( $classlang);
        //dv_dump($metadata);
        $associationMappings = ($metadata->associationMappings);
        $fieldNames = array_keys($metadata->fieldNames);
        $fieldNames = array_merge($fieldNames, $object->dvtranslated_columns);

        foreach ($jsonform as $field => $value) {

            $meta = explode(":", $field);

            $imbricate = explode(".", $meta[0]);

            if (isset($meta[1])) {
                $setter = "set" . ucfirst($meta[1]);
            } else
                $setter = "set" . ucfirst($meta[0]);

            if (isset($imbricate[1])) {

                $entitname = str_replace(".id", "", $meta[0]);
                if (strpos($entitname, "\\")) {
                    $classtyperst = explode("\\", $entitname);
                    $classtype = $classtyperst[0];
                    $entitname = $classtyperst[1];
                    if (!isset($associationMappings[$entitname]))
                        continue;
                    $classtype = $associationMappings[$entitname]["targetEntity"];
                } else {
                    $classtype = $entitname;

                    if (!isset($associationMappings[$entitname]))
                        continue;
                    $classtype = $associationMappings[$classtype]["targetEntity"];
                }
                $currentfieldsetter = 'set' . ucfirst($entitname);
                if (!class_exists(ucfirst($classtype)))
                    continue;

                if (!is_numeric($value)) {
                    continue;
                }
                $reflect = new \ReflectionClass($classtype);
                $value2 = $reflect->newInstance();

                $value2->setId($value);

                if (!method_exists($this->entity, $currentfieldsetter)) {
                    $object->{$entitname} = $value2;
                    //$this->error[$field] = " You may create method " . $setter . " in entity ";
                } elseif ($error = call_user_func(array($this->entity, $currentfieldsetter), $value2->hydrate(false)))
                    $this->error[$field] = $error;

            }
            else {
                if (!method_exists($this->entity, $setter)) {
                    if (in_array($field, $fieldNames))
                        $this->entity->{$field} = $value;
//                    else
//                        $this->error[$field] = " You may create method " . $setter . " in entity. ";
                } elseif ($error = call_user_func(array($this->entity, $setter), $value))
                    $this->error[$field] = $error;
            }

        }

        return $this->entity;

    }

    private function hydrateEntity($field, $value, $meta, $imbricate)
    {

    }

    public static function renderController($view, $resultCtrl)
    {

        extract($resultCtrl);
        include __DIR__ . "/../../" . $view;
    }

    public $entitytarget = "";
    public $datatablemodel = [];
    public $title = "View Title";
    public static $cssfiles = [];
    public static $jsscript = "";
    public static $jsfiles = [];

    // public abstract function listView($next = 1, $per_page = 10);

    public function renderListView($data = [])
    {

        if (!$data) {
            foreach ($this as $key => $value) {
                \Response::set($key, $value);
            }
        }

        \Genesis::renderView($this->indexView,
            \Response::$data + $data
        );
        die;
    }

    public function renderDetailView($datatablehtml, $return = false, $datatablemodel = "")
    {

        if ($return)
            return array('success' => true, // pour le restservice
                'title' => $this->title, // pour le web service
                'entity' => $this->entitytarget, // pour le web service
                'datatabledetailhtml' => $datatablehtml, // pour le web service
                'datatablemodel' => $datatablemodel, // pour le web service
                'detail' => '');

        \Genesis::renderView('default.detail',
            array('success' => true, // pour le restservice
                'title' => $this->title, // pour le web service
                'entity' => $this->entitytarget, // pour le web service
                'datatabledetailhtml' => $datatablehtml,
                'detail' => '')
        );

    }

    public static function render($view, $data = [])
    {

        $compilate = [];
        if ($data) {
            if (key_exists(0, $data)) {
                foreach ($data as $el) {
                    foreach ($el as $key => $value) {
                        $compilate[$key] = $value;
                    }
                }
            } else {
                $compilate = $data;
            }
        }

        $blade = new Blade([web_dir . "views", admin_dir . 'views'], ROOT . "cache/views");
        echo $blade->view()->make($view, $compilate)->render();
        //die;
    }

    public static function renderView($view, $data = [], $redirect = false)
    {

        global $viewdir, $moduledata;

        if ($redirect && isset($data['redirect'])) {
            header('location: ' . $data['redirect']);
        }

        $data["moduledata"] = $moduledata;//Genesis::top_action($action, $classroot);

        $blade = new Blade($viewdir, ROOT . "cache/views");
        echo $blade->view()->make($view, $data)->render();
    }

    public function cloneAction($id)
    {

        $classname = self::getclassname();
        $newclass = ucfirst($classname);
        $newclasstable = $newclass . 'Table';
        $entity = $newclass::find($id);
        $entity->setId(null);
        $entity->__insert();
        return array('success' => true,
            $classname => $entity,
            'tablerow' => $newclasstable::init()->buildindextable()->getSingleRowRest($entity),
            'detail' => '');

    }

    public function lazyloading($entity, $qb, $sort = ''){

        $ll = new \dclass\devups\Datatable\Lazyloading($entity);
        $ll->start(new QueryBuilder($entity));

        return $ll->lazyloading($entity, $qb, $sort);
    }

    public static $classname;

}
