<?php


namespace dclass\devups\Controller;


use Auth;
//use Dvups_entity;
use Dvups_lang;
use Exception;
use ReflectionAnnotatedClass;
use Request;
use Router;
use setasign\Fpdi\functional\LinkHandling\FpdiTest;

class FrontController extends Controller
{

    protected $layout;
    protected $meta = [];
    protected $breakconf;

    public static $pagetitle = "";

    public function __construct()
    {

        if (isset(Request::$uri_get_param["dclass"]))
            self::$entityname = Request::$uri_get_param["dclass"];
    }

    public function listView($next = 1, $per_page = 10)
    {
        // TODO: Implement listView() method.
    }

    public $jwt;

    /**
     * @throws Exception
     */
    public function createCore($persist = true)
    {
        $classname = self::$entityname;
        // $newclass = ucfirst($classname);
        $newclass = self::$classMagic;

        $entity = new $newclass;
        $entities = [];
        $rawdata = \Request::raw();
        if (is_null($rawdata)) {
            if (!isset($_POST[$classname . "_form"]))
                return array('success' => false,
                    $classname => $entity,
                    'detail' => $classname . "_form is missing in your form_data. ex: entity_form[attribute] ");
            $entity = $this->form_fillingentity($entity, $_POST[$classname . "_form"]);
        } else {
            if (isset($rawdata[$classname . "_bulk"])) {
                $datacollection = [];
                foreach ($rawdata[$classname . "_bulk"] as $rawentity) {
                    $this->error = [];
                    $entity_item = $this->hydrateWithJson($entity, $rawentity);
                    $entity_item->id = null;
                    if ($this->error) {
                        $datacollection[] = array('success' => false,
                            $classname => $entity_item,
                            'error' => $this->error);
                        continue;
                    }

                    try {
                        $id = $entity_item->__insert();
                    }catch (Exception $e){
                        return [
                            'success' => false,
                            'detail' => $e->getMessage(),
                        ];
                    }

                    $datacollection[] = array('success' => true,
                        $classname => $entity_item,
                        'detail' => '');
                }
                return compact("datacollection");
            } else if (isset($rawdata[$classname]))
                $entity = $this->hydrateWithJson($entity, $rawdata[$classname]);
            else
                throw new Exception("Undefined array key '$classname' verify your submited data");
        }
        if (!$persist)
            return $entity;

        if (isset($_FILES[$classname . "_form"])) {
            foreach ($_FILES[$classname . "_form"]['name'] as $key_form => $value_form) {
                self::addEventListenerAfterCreate(get_class($this->entity), 'upload' . ucfirst($key_form));
            }
        }

        if ($this->error) {
            return array('success' => false,
                $classname => $entity,
                'error' => $this->error);
        }

        try {
            $id = $entity->__insert();
        }catch (Exception $e){
            return [
                'success' => false,
                'detail' => $e->getMessage(),
            ];
        }

        if (Request::get("tablemodel")) {
            $table = $classname . "Table";
            return [
                'success' => true,
                $classname => $entity,
                'tablerow' => $table::init()->router()->getSingleRowRest($entity),
                'detail' => ''
            ];
        } elseif (Request::get("dview")) {

            $dalias = Request::get("dalias");
            if (!$dalias)
                $dalias = $classname;
            return [
                'success' => true,
                $classname => $entity,
                'view' => \Genesis::getView(Request::get("dview"), [$dalias => $entity]),
                'detail' => ''
            ];
        }

        return array('success' => true,
            $classname => $entity,
            'detail' => '');

    }

    public function uploadCore($id)
    {

        //$classname = self::getclassname();
        $classname = self::$entityname;

        $newclass = ucfirst($classname);
        $entity = $newclass::find($id, false);

        if (isset($_FILES[$classname . "_form"])) {
            foreach ($_FILES[$classname . "_form"]['name'] as $key_form => $value_form) {
                $method = 'upload' . ucfirst($key_form);
                $entity->{$method}();
            }
            if ($id)
                $entity->__update();
            return array('success' => true,
                $classname => $entity->__show(true),
                'detail' => 'file uploaded with success');
        }

        return array('success' => false,
            'detail' => 'no file founded');

    }

    /**
     * @throws Exception
     */
    public function authorization($classname, $method)
    {

        $Auth = new Auth();

        if (isset(Auth::$restrictions[$classname]) && !in_array($method, Auth::$restrictions[$classname])) {
            $result = $Auth->authorize($this);
            if (is_array($result) && $result['success'] == false) {
                throw new Exception($result['detail']);
            }

            Request::$uri_get_param['user_id'] = $this->jwt->userId;

        }else{
            $result = $Auth->execute($this);
            if (is_array($result) && $result['success'] == true)
                Request::$uri_get_param['user_id'] = $this->jwt->userId;

        }
    }

    /**
     * @throws Exception
     */
    public function updateCore($id)
    {

        //$classname = self::getclassname();
        $classname = self::$entityname;

        $newclass = ucfirst($classname);
        $entity = new $newclass($id);

        $rawdata = \Request::raw();
        if (is_null($rawdata)) {
            if (!isset($_POST[$classname . "_form"]))
                return array('success' => false,
                    $classname => $entity,
                    'detail' => $classname . "_form is missing in your form_data. ex: entity_form[attribute] ");
            $entity = $this->form_fillingentity($entity, $_POST[$classname . "_form"]);
        } else
            $entity = $this->hydrateWithJson($entity, $rawdata[$classname]);

        if ($this->error) {
            return array('success' => false,
                $classname => $entity,
                'error' => $this->error);
        }

        try {
            $entity->__update();
        }catch (Exception $e){
            return [
                'success' => false,
                'detail' => $e->getMessage(),
            ];
        }

        if (Request::get("tablemodel")) {
            $table = $classname . "Table";
            return [
                'success' => true,
                $classname => $entity,
                'tablerow' => $table::init()->router()->getSingleRowRest($entity),
                'detail' => ''
            ];
        } elseif (Request::get("dview")) {

            $dalias = Request::get("dalias");
            if (!$dalias)
                $dalias = $classname;
            return [
                'success' => true,
                $classname => $entity,
                'view' => \Genesis::getView(Request::get("dview"), [$dalias => $entity]),
                'detail' => ''
            ];
        }

        return array('success' => true,
            $classname => $entity,
            'detail' => '');

    }

    public function deleteCore($id)
    {

        $classname = self::$entityname;

        $newclass = self::$classMagic;
        //$newclass = ucfirst($classname);

        try {
            $newclass::find($id, false)->__delete();
        }catch (Exception $e){
            return [
                'success' => false,
                'detail' => $e->getMessage(),
            ];
        }

        return array('success' => true,
            'detail' => '');

    }

    public function detailCore($id)
    {

        $classname = self::$entityname;
        $newclass = ucfirst($classname);

        if ($iso = Request::get('dlang'))
            return array('success' => true,
                $classname => $newclass::find($id, Dvups_lang::getByIsoCode($iso)->id),
                'detail' => '');

        return array('success' => true,
            $classname => $newclass::find($id, false),
            'detail' => '');

    }

    public function dcollection()
    {
        $rawdata = Request::raw();
        $result = [];
        foreach ($rawdata as $entityaction => $filter) {
            $option = explode(".", $entityaction);
            if (!isset($option[1])) {
                $result[$entityaction] = [
                    "success" => false,
                    "detail" => t("action " . $entityaction . " not supported. available option are: lazyloadin or detail"),
                ];
                continue;
            }
            $entity = $option[1];
            if ($option[0] == "lazyloading") {
                Request::$uri_get_param['dclass'] = $entity;
                Request::collectUrlParam($filter);
                $result[$entity . "_ll"] = $this->ll();
            } elseif ($option[0] == "detail") {
                Request::$uri_get_param['dclass'] = $entity;
                $result[$entity] = $this->detailCore($filter);
            } else {
                $result[$entityaction] = [
                    "success" => false,
                    "detail" => t("action " . $entityaction . " not supported. available option are: lazyloadin or detail"),
                ];
            }
            // we reset static variable with the one in the url  so that next time
            // default filter set in the url can be apply to other lazyloadin.
            Request::$uri_get_param = [];
            (new Request("hello"));
        }
        $result["success"] = true;
        return $result;
    }

    /**
     * @throws Exception
     */
    public function ll()
    {

        //$classname = str_replace('-', '_', Request::get('dclass'));

        // this has already been done in Controller@getclassname
        /*$dventity = \Dvups_entity::getbyattribut("this.name", $classname);
        if (!$dventity->getId())
            return [
                "success" => false,
                "message" => "entity " . $classname . " not found",
                "available" => \Dvups_entity::all(),
            ];*/

        try {
            $this->authorization(self::$classname, 'lazyloading');
        } catch (Exception $e) {
            throw $e;
        }

        // $newclass = ucfirst($classname);
        $newclass = self::$classMagic;

        // new paradigm to override the default method. usefull while doing lazylaoding api call's
        return $newclass::lazyloading();

    }

    /**
     * this method is used by devups from the admin/services.php file to manage router as we use to do at the front App.php
     * @return false|int|mixed
     */
    public static function frontServe($classname, $route)
    {

        try {
            self::getclassname($route->public_access);
        }catch (Exception $e){
            throw $e;
        }

        /*$entity = Dvups_entity::where("this.url", $classname)
            ->orwhere("this.name", $classname)->firstOrNull();

        $ctrl = ucfirst($entity->name) . "FrontController";*/
        $ctrl = ucfirst($classname) . "FrontController";

        if (!class_exists($ctrl))
            return ['success' => false, 'detail' => "the class $ctrl doesn't exist "];

        try {
            Request::$dv_entity = $classname;
            return Request::FrontController($route, Request::get('path'), $ctrl);
        } catch (Exception $e) {
            return ['success' => false, 'detail' => $e->getMessage()];
        }

    }

}