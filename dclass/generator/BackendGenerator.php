<?php

class BackendGenerator
{

    public function entityGenerator($entity)
    {
        $attrib = "";

        $name = strtolower($entity->name);

        $antislash = str_replace(" ", "", " \ ");

        unset($entity->attribut[0]);

        $fichier = fopen('Entity/' . ucfirst($name) . '.php', 'w');

        fputs($fichier, "<?php 
        // user \dclass\devups\model\Model;
    /**
     * @Entity @Table(name=\"" . $name . "\")
     * */
    class " . ucfirst($name) . " extends Model implements JsonSerializable{\n");
        $method = "";

        $construt = "
        /**
         * @Id @GeneratedValue @Column(type=\"integer\")
         * @var int
         * */
        protected $" . "id;";
        $otherattrib = false;

//        if(isset($entity->attribut[1])){
//        var_dump($entity->attribut);
//        die;

        $attributlang = [];
        foreach ($entity->attribut as $attribut) {

            if ($attribut->name == "id")
                continue;

            if (isset($attribut->lang) && $attribut->lang) {
                $attributlang[] = $attribut->name;
                $construt .= "
                // $" . $attribut->name . " available in {$name}_lang class \n";
                continue;
            }

            $length = "";
            $nullable = "";

            if (in_array($attribut->formtype, ["radio", "checkbox", "select"])) {
                $staticenum = [];
                if (isset($attribut->enum)) {
                    foreach ($attribut->enum as $key => $enum) {
                        if (is_string($enum))
                            $staticenum[] = "'$enum'";
                        else
                            $staticenum[] = $enum;
                        //$staticenum[] = " '$key' => '$enum'";
                    }
                }

                $construt .= "
        /* enum */
        public static $" . $attribut->name . "s = [" . implode(",", $staticenum) . "];";

            }

            if ($attribut->datatype == "string") {
                $length = ', length=' . $attribut->size . '';
            }

            if ($attribut->nullable == 'default') {
                $nullable = ", nullable=true";
            }
            $defaultvalue = "";
            if (isset($attribut->defaultvalue) && !in_array($attribut->datatype, ['date', 'datetime', 'time'])) {
                $defaultvalue = " = " . $attribut->defaultvalue . ' ';
            }
            $construt .= "
        /**
         * @Column(name=\"" . $attribut->name . "\", type=\"" . $attribut->datatype . "\" $length $nullable)
         * @var " . $attribut->datatype . "
         **/
        protected $" . $attribut->name . "$defaultvalue;";
        }
        $otherattrib = true;
        $langconfig = "";
//        }
        if ($attributlang) {
            $langconfig = "\$this->dvtranslate = true;
            \$this->dvtranslated_columns = [\"" . implode(',', $attributlang) . "\"];";
        }
        $construteur = "
        public function __construct($" . "id = null){
            $langconfig
            if( $" . "id ) { $" . "this->id = $" . "id; }   
            ";

        $methodcollection = "";
        $jsonserializeRelation = "";
        if (!empty($entity->relation)) {
            $construteur .= "";
            foreach ($entity->relation as $relation) {
                $original = $relation->entity;
                $values = explode("\\", $relation->entity);
                $entitytype = ucfirst($values[0]);
                if (isset($values[1]))
//                    $entitytype = ucfirst($relation->entitytype);
                    $relation->entity = strtolower($values[1]);
                else
                    $relation->entity = strtolower($values[0]);

                $relation->ondelete = $relation->ondelete ?? 'cascade';
                if ($relation->cardinality == 'manyToMany') {

                    $manytomany = [
                        "name" => $name . "_" . $relation->entity,
                        "ref" => null,
                        "attribut" => [],
                        "relation" => [
                            [
                                "entity" => $relation->entity,
                                "cardinality" => "manyToOne",
                                "nullable" => "not",
                                "ondelete" => "cascade",
                                "onupdate" => "cascade"
                            ],
                            [
                                "entity" => $name,
                                "cardinality" => "manyToOne",
                                "nullable" => "not",
                                "ondelete" => "cascade",
                                "onupdate" => "cascade"
                            ],
                        ]
                    ];

                    $construteur .= "\n\t\t\t$" . "this->" . $relation->entity . " = [];";

                    $attrib .= "
        /**
         * " . $relation->cardinality . "
         * @var " . $antislash . ucfirst($relation->entity) . "
         */
        public $" . $relation->entity . ";\n";

                    $methodcollection .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . ucfirst($relation->entity) . "
         */
        function get" . ucfirst($relation->entity) . "() {
            return $" . "this->" . $relation->entity . ";
        }";
                    $methodcollection .= "
        function set" . ucfirst($relation->entity) . "($" . $relation->entity . "){
            $" . "this->" . $relation->entity . " = $" . $relation->entity . ";
        }
        
        function add" . ucfirst($relation->entity) . "(" . $antislash . ucfirst($relation->entity) . " $" . $relation->entity . "){
            $" . "this->" . $relation->entity . "[] = $" . $relation->entity . ";
        }
        
        function collect" . ucfirst($relation->entity) . "(){
            $" . "this->" . $relation->entity . " = $" . "this->__hasmany('" . $relation->entity . "');
            return $" . "this->" . $relation->entity . ";
        }
        
                        ";
                }
                elseif ($relation->cardinality == 'oneToOne') { // or $relation->nullable == 'DEFAULT'

                    $construteur .= "\n\t$" . "this->" . $relation->entity . " = new $entitytype();";

                    $attrib .= "
        /**
         * " . ucfirst($relation->cardinality) . "
         * @ManyToOne(targetEntity=\"" . $antislash . $entitytype . "\")
         * @JoinColumn(onDelete=\"" . ($relation->ondelete ) . "\")
         * @var " . $antislash . $entitytype . "
         */
        public $" . $relation->entity . ";\n";

                    $method .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . $entitytype . "
         */
        function get" . ucfirst($relation->entity) . "() {
            return $" . "this->" . $relation->entity . ";
        }";
                    $method .= "
        function set" . ucfirst($relation->entity) . "(" . $antislash . $entitytype . " $" . $relation->entity . " = null) {
            $" . "this->" . $relation->entity . " = $" . $relation->entity . ";
        }
                        ";
                } else {

                    $construteur .= "\n\t$" . "this->" . $relation->entity . " = new " . $entitytype . "();";

                    $attrib .= "
        /**
         * @" . ucfirst($relation->cardinality) . "(targetEntity=\"" . $antislash . $entitytype . "\")
         * @JoinColumn(onDelete=\"" . $relation->ondelete . "\")
         * @var " . $antislash . $entitytype . "
         */
        public $" . $relation->entity . ";\n";

                    $method .= "
        /**
         *  " . $relation->cardinality . "
         *	@return " . $antislash . $entitytype . "
         */
        function get" . ucfirst($relation->entity) . "() {
            return $" . "this->" . $relation->entity . ";
        }";
                    $method .= "
        function set" . ucfirst($relation->entity) . "(" . $antislash . $entitytype . " $" . $relation->entity . ") {
            $" . "this->" . $relation->entity . " = $" . $relation->entity . ";
        }
                        ";
                }

                $jsonserializeRelation .= "
                    '" . $relation->entity . "' => $" . "this->" . $relation->entity . ",";
               $relation->entity =  $original;
            }

        }

        $construteur .= "\n}\n";

        $construt .= " 
        " . $attrib . "

        " . $construteur . "
        public function getId() {
            return $" . "this->id;
        }";
        if ($otherattrib) {
            foreach ($entity->attribut as $attribut) {

                if ($attribut->name == "id")
                    continue;

                if (in_array($attribut->formtype, ['document', 'image', 'music', 'video'])) {
                    $construt .= "
                        
        public function upload" . ucfirst($attribut->name) . "($" . "file = '" . $attribut->name . "') {
            $" . "dfile = self::Dfile($" . "file);
            if(!$" . "dfile->errornofile){
            
                $" . "filedir = '" . $name . "/';
                $" . "url = $" . "dfile
                    ->sanitize()
                    ->moveto($" . "filedir);
    
                if (!$" . "url['success']) {
                    return 	array(	'success' => false,
                        'error' => $" . "url);
                }
    
                $" . "this->" . $attribut->name . " = $" . "url['file']['hashname'];            
            }
        }     
             
        public function src" . ucfirst($attribut->name) . "() {
            return Dfile::show($" . "this->" . $attribut->name . ", '" . $name . "');
        }
        public function show" . ucfirst($attribut->name) . "() {
            $" . "url = Dfile::show($" . "this->" . $attribut->name . ", '" . $name . "');
            return Dfile::fileadapter($" . "url, $" . "this->" . $attribut->name . ");
        }
        ";
                    /*
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
        }*/
                } elseif ($attribut->formtype == 'liste') {
                    $construt .= "
        public function get" . ucfirst($attribut->name) . "List() {
            return $" . "this->" . $attribut->name . ";
        }
		
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
        }
        ";
                } else {
                   /* $construt .= "
        public function get" . ucfirst($attribut->name) . "() {
            return $" . "this->" . $attribut->name . ";
        }

        public function set" . ucfirst($attribut->name) . "($" . $attribut->name . ") {
            $" . "this->" . $attribut->name . " = $" . $attribut->name . ";
        }
        ";*/
                }
            }
        }
        $construt .= /*$method .*/ "
        
        // getter and setter
        $methodcollection
        public function jsonSerialize() {
                return [
                    'id' => $" . "this->id,";
        foreach ($entity->attribut as $attribut) {
            $construt .= "
                    '" . $attribut->name . "' => $" . "this->" . $attribut->name . ",";
        }
        if (!empty($entity->relation)) {
            $construt .= $jsonserializeRelation;
            /*foreach ($entity->relation as $relation) {
                $construt .= "
                    '" . $relation->entity . "' => $" . "this->" . $relation->entity . ",";
            }*/
        }
        $construt .= "
                ];
        }
        ";

        fputs($fichier, $construt);
        fputs($fichier, "\n}\n");

        fclose($fichier);


        if (isset($manytomany)) {
            $entitycollection = (object)$manytomany;
            $entitycollection->relation[0] = (object)$entitycollection->relation[0];
            $entitycollection->relation[1] = (object)$entitycollection->relation[1];

            $this->entityGenerator($entitycollection);
        }

    }

    public function entityLangGenerator($entity)
    {

        if (!isset($entity->lang) || !$entity->lang)
            return 0;

        $name = strtolower($entity->name);

        unset($entity->attribut[0]);

        $fichier = fopen('Entity/' . ucfirst($name) . '_lang.php', 'w');

        fputs($fichier, "<?php 
/**
 * @Entity @Table(name=\"" . $name . "_lang\")
 * */
class " . ucfirst($name) . "_lang extends Dv_langCore {\n");
        $method = "";
        $construteur = " ";
        $attrib = "";

        $construt = "";
        $attributs = [];
        foreach ($entity->attribut as $attribut) {
            if (!isset($attribut->lang) || !$attribut->lang) {
                continue;
            }
            $length = "";
            $nullable = "";

            if ($attribut->datatype == "string") {
                $length = ', length=' . $attribut->size . '';
            }

            if ($attribut->nullable == 'default') {
                $nullable = ", nullable=true";
            }
            $defaultvalue = "";
            $attributs[]=$attribut->name;
            $construt .= "
    /**
     * @Column(name=\"" . $attribut->name . "\", type=\"" . $attribut->datatype . "\" $length $nullable)
     * @var " . $attribut->datatype . "
     **/
    protected $" . $attribut->name . "$defaultvalue;";

        }

        $construt .= "
        
    /**
     * @Id @ManyToOne(targetEntity=\"\\" . ucfirst($name) . "\")
     * @JoinColumn(onDelete=\"cascade\")
     * @var \\" . ucfirst($name) . "
     */
    public \$$name;
    ";

//        }

        $construt .= " 
        " . $attrib . "

        " . $construteur . "
        ";
        $attributs = implode(',', $attributs);
        $construt .= $method . " 
         
    /**
     * ==== HOW TO MIGRATE DATA FROM A PREVIOUS TABLE TO A MULTILANG TABLE ===
     *
     * INSERT INTO $name\\_lang (lang_id, $name\\_id, ".$attributs.")
     * SELECT 1 as lang_id, id, $attributs FROM `$name` WHERE 1
     *
     * INSERT INTO $name\\_lang (lang_id, $name\\_id, ".$attributs.")
     * SELECT 2 as lang_id, id, $attributs FROM `$name` WHERE 1
     */
     ";

        fputs($fichier, $construt);
        fputs($fichier, "\n}\n");

        fclose($fichier);

    }

    /* 	CREATION DU CONTROLLER 	 */
    private function defaultCtrlContent($name, $entity)
    {

        $contentono = "";
        $contentform = "";
        $mtm = [];
        $onoinsert = [];
        $iter = 0;

        if (!empty($entity->relation)) {
            //relation sera l'entité
            foreach ($entity->relation as $relation) {

                if ($relation->cardinality == "oneToOne") {
                    $contentform .= ", $" . $relation->entity . "_form = null";
                    $contentono .= "
                    
        $" . $relation->entity . " = $" . "this->form_fillingentity(new " . ucfirst($relation->entity) . "(), $" . $relation->entity . "_form);
        if ( $" . "this->error ) {
            return 	array(	'success' => false,
                            '" . $relation->entity . "' => $" . $relation->entity . ",
                            'error' => $" . "this->error);
        }";
                    $onoinsert[] = "
        $" . $relation->entity . "->__insert();
        $" . $name . "->" . ($relation->entity) . " = $" . $relation->entity . ";";
                }
            }
        }

        $onoinsert = "\n" . implode("", $onoinsert);
        $otherattrib = false;

        $contenu = "public function listView(){

        \$this->datatable = " . ucfirst($name) . "Table::init(new " . ucfirst($name) . "())->buildindextable();

        self::$" . "jsfiles[] = " . ucfirst($name) . "::classpath('Resource/js/" . $name . "Ctrl.js');

        $" . "this->entitytarget = '" . ucfirst($name) . "';
        $" . "this->title = \"Manage " . ucfirst($name) . "\";
        
        $" . "this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => " . ucfirst($name) . "Table::init(new " . ucfirst($name) . "())->router()->getTableRest(),
        ];
        
    }

    public function formView(\$id = null)
    {
        \$" . ($name) . " = new " . ucfirst($name) . "();
        \$action = __env.(\"admin/api/" . ($name) . "/create\");
        if (\$id) {
            \$action = __env.(\"admin/api/" . ($name) . "/update?id=\" . \$id);
            \$" . ($name) . " = " . ucfirst($name) . "::find(\$id);
        }

        return ['success' => true,
            'form' => " . ucfirst($name) . "Form::init(\$" . ($name) . ", \$action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($" . $name . "_form = null $contentform){
        extract($" . "_POST);

        $" . $name . " = $" . "this->form_fillingentity(new " . ucfirst($name) . "(), $" . $name . "_form);
        if ( $" . "this->error ) {
            return 	array(	'success' => false,
                            '" . $name . "' => $" . $name . ",
                            'action' => 'create', 
                            'error' => $" . "this->error);
        } ";

        $contenu .= $contentono;
        $contenu .= "
        $onoinsert
        $" . "id = $" . $name . "->__insert();
        return 	array(	'success' => true,
                        '" . $name . "' => $" . $name . ",
                        'tablerow' => " . ucfirst($name) . "Table::init()->router()->getSingleRowRest($" . $name . "),
                        'detail' => '');

    }

    public function updateAction($" . "id, $" . $name . "_form = null){
        extract($" . "_POST);
            
        $" . $name . " = $" . "this->form_fillingentity(new " . ucfirst($name) . "($" . "id), $" . $name . "_form);
     
        if ( $" . "this->error ) {
            return 	array(	'success' => false,
                            '" . $name . "' => $" . $name . ",
                            'action_form' => 'update&id='.$" . "id,
                            'error' => $" . "this->error);
        }
        
        $" . $name . "->__update();
        return 	array(	'success' => true,
                        '" . $name . "' => $" . $name . ",
                        'tablerow' => " . ucfirst($name) . "Table::init()->router()->getSingleRowRest($" . $name . "),
                        'detail' => '');
                        
    }
    

    public function detailView($" . "id)
    {

        $" . "this->entitytarget = '" . ucfirst($name) . "';
        $" . "this->title = \"Detail " . ucfirst($name) . "\";

        $" . $name . " = " . ucfirst($name) . "::find($" . "id);

        $" . "this->renderDetailView(
            " . ucfirst($name) . "Table::init()
                ->builddetailtable()
                ->renderentitydata($" . $name . ")
        );

    }
    
    public function deleteAction($" . "id){
    
        $" . $name . " = " . ucfirst($name) . "::find($" . "id);
        $" . $name . "->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($" . "ids)
    {

        " . ucfirst($name) . "::where(\"this.id\")->in($" . "ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }";

        return $contenu;

    }

    private function frontCtrlContent($name, $entity)
    {

        $contentform = "";

        $contenu = "
    /**
     * @GET
     * @return Lazyloading|QueryBuilder
     * @throws ReflectionException
     */
     public function index(){
        
            \$qb = " . ucfirst($name) . "::initQb();
            
            return \$qb->lazyloading();

    }

    /**
     * @POST
     */
    public function create(){
    
        return 	parent::createCore();

    }

    /**
     * @GET(path='/:id')
     */
    public function detail(\$id){
    
        $" . $name . " = " . ucfirst($name) . "::find($" . "id);

        return 	array(	'success' => true,
                        '" . $name . "' => $" . $name . ",
                        'detail' => '');
                        
    }

    /**
     * @PUT(path='/:id')
     */
    public function update($" . "id){
    
        return 	parent::updateCore(\$id);
                        
    }
    

    /**
     * @DELETE(path='/:id')
     */
    public function delete(\$id){

        $" . $name . " = " . ucfirst($name) . "::find($" . "id);
        $" . $name . "->__delete();
        
        return 	array(	'success' => true,
                        'detail' => '$name deleted successfully');
                        
    }
     
";

        return $contenu;

    }

    public function controllerGenerator($entity, $front = false)
    {
        //$datatablemodel = DvAdmin::buildindexdatatable($listmodule, $entity);
        $name = strtolower($entity->name);

        //if(__Generator::$ctrltype == 'front' || __Generator::$ctrltype == 'both'){
        if ($front) {

            $ctrlname = '' . ucfirst($name) . 'FrontController';
            $classController = fopen('Controller/' . $ctrlname . '.php', 'w');
            $extend = '\dclass\devups\Controller\FrontController';

            $content = $this->frontCtrlContent($name, $entity);

            $contenu = "<?php \n

/**
 * @Api(name='/$name')
 */
class " . $ctrlname . " extends $extend{

    $content

}\n";
            fputs($classController, $contenu);
            //fputs($classController, "\n}\n");
            fclose($classController);

            return 0;
        }
        //}

        //if(__Generator::$ctrltype == 'both'){
        $ctrlname = '' . ucfirst($name) . 'Controller';
        $classController = fopen('Controller/' . $ctrlname . '.php', 'w');
        $extend = 'Controller';
        $content = $this->defaultCtrlContent($name, $entity);

        $contenu = "<?php \n
            
use dclass\devups\Controller\Controller;

class " . $ctrlname . " extends $extend{

    $content

}\n";
        fputs($classController, $contenu);
        //fputs($classController, "\n}\n");
        fclose($classController);

        // }

    }

    /* 	CREATION DU CONTROLLER 	 */

    public function tableGenerator($entity, $listmodule)
    {
        $datatablemodel = DvAdmin::buildindexdatatable($listmodule, $entity);
        $detailview = DvAdmin::builddetaildatatable($entity, $listmodule);

        $name = strtolower($entity->name);

        $classController = fopen('Datatable/' . ucfirst($name) . 'Table.php', 'w');

        $contenu = "<?php \n

use dclass\devups\Datatable\Datatable as Datatable;

class " . ucfirst($name) . "Table extends Datatable{
    

    public function __construct(\$$name = null, $" . "datatablemodel = [])
    {
        parent::__construct(\$$name, $" . "datatablemodel);
    }

    public static function init(\\" . ucfirst($name) . " \$$name = null){
    
        $" . "dt = new " . ucfirst($name) . "Table(\$$name);
        \$dt->entity = \$$name;
        
        return $" . "dt;
    }

    public function buildindextable(){

        \$this->base_url = __env.\"admin/\";
        $" . "this->datatablemodel = $datatablemodel;

        return $" . "this;
    }
    
    public function builddetailtable()
    {
        $" . "this->datatablemodel = $detailview;
        // TODO: overwrite datatable attribute for this view
        return $" . "this;
    }

    public function router()
    {
        \$tablemodel = Request::get(\"tablemodel\", null);
        if (\$tablemodel && method_exists(\$this, \"build\" . \$tablemodel . \"table\") && \$result = call_user_func(array(\$this, \"build\" . \$tablemodel . \"table\"))) {
            return \$result;
        } else
            switch (\$tablemodel) {
                // case \"\": return this->
                default:
                    return \$this->buildindextable();
            }

    }
    
}\n";
        fputs($classController, $contenu);
        //fputs($classController, "\n}\n");

        fclose($classController);

    }


    /* CREATION OF CORE */

    public function coreGenerator($entitywithnamespace, $sync = false)
    {
        global $em;

        $ns = explode("\\", $entitywithnamespace);
        $classname = ($ns[count($ns)-1]);
        $name = strtolower($classname);
        /*echo "this command has to be rework";
        die;
        //before, verify if it has a record with it nameMagic (with namespace)
        if (!Dvups_entity::where("this.name", $name)->count())
            return false;
        $de = Dvups_entity::where("this.name", $name)->first();*/
        try {
            $classmetadata = (array) $em->getClassMetadata($entitywithnamespace);
        }catch (Exception $e){
            try {
                $classmetadata = (array) $em->getClassMetadata("\\$classname");
            }catch (Exception $exception){
                echo $e->getMessage()." AND ".$exception->getMessage();
                die();
            }
        }

        $classdevupsmetadata = [];

        $classdevupsmetadata["name"] = $name;
        foreach ($classmetadata["fieldMappings"] as $field) {
            if (in_array($field["fieldName"], ["created_at", "updated_at", "deleted_at",]))
                continue;

            $length = "";
            if ($field["length"])
                $length = $field["length"];

            $nullable = "not";
            if ($field["nullable"])
                $nullable = "default";

            $dvfield = [
                "name" => $field["fieldName"],
                "visibility" => $field["fieldName"],
                "datatype" => $field["type"],
                "size" => $length,
                "nullable" => $nullable,
                "formtype" => $field["fieldName"],
            ];
            $classdevupsmetadata["attribut"][] = $dvfield;
        }

        foreach ($classmetadata["associationMappings"] as $field) {
            $dvfield = [
                "entity" => $field["fieldName"],
                "cardinality" => "manyToOne",
                "nullable" => "not",
                "ondelete" => "cascade",
                "onupdate" => "cascade"
            ];
            $classdevupsmetadata["relation"][] = $dvfield;
        }

        $contenu = json_encode($classdevupsmetadata);
        if (!$sync) {
            if (!file_exists('Core')) {
                mkdir('Core', 0777);
            }

            $entitycore = fopen('Core/' . $name . 'Core.json', 'w+');
            fputs($entitycore, $contenu);

            fclose($entitycore);
        }
        if ($sync && false) {
            $curl = curl_init();

            $data = array(
                CURLOPT_URL => __toolrad_server . "entity.sync?project=" . __project_id . "&entity=$name&api_key=" . __toolrad_api_key,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                //CURLOPT_FAILONERROR => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST'
            );

            $data[CURLOPT_POSTFIELDS] = json_encode(["entity" => [
                "name" => $name,
                "jsondata" => $contenu,
            ]]);

            curl_setopt_array($curl, $data);
            //$response = curl_exec($curl);
            curl_close($curl);

            echo " $name > entity well synchronized\n ";
            //if($this->_log)
            //\DClass\lib\Util::log(date("Y-m-d H:i:s") . __LINE__ . $response, "log_sync_toolrad");

        }

    }


    /* CREATION OF POSTMANDOC */

    public function postmandocGenerator($entityname)
    {
        require ROOT . 'src/requires.php';
        global $em;

        $classmetadata = (array)$em->getClassMetadata("\\" . $entityname);

        $name = strtolower($entityname);

        $postmandoc = [
            "url" => "{{base_url}}create.$name",
            "lazyloading" => "{{base_url}}lazyloading.$name?dfilters=on&per_page=10&next=1",
            "raw" => [$name => []],
            "formdata" => [],
        ];
        $classdevupsmetadata["name"] = $name;

        foreach ($classmetadata["fieldMappings"] as $field) {
            if (in_array($field["fieldName"], ["created_at", "updated_at", "deleted_at",]))
                continue;

            $postmandoc["raw"][$name][$field["fieldName"]] = "";
            $postmandoc["formdata"][$name . "_form[{$field["fieldName"]}]"] = "";
        }

        foreach ($classmetadata["associationMappings"] as $field) {

            $postmandoc["raw"][$name][$field["fieldName"] . ".id"] = 1;
            $postmandoc["formdata"][$name . "_form[{$field["fieldName"]}.id]"] = 1;

        }
        if (!file_exists('PostmanDoc')) {
            mkdir('PostmanDoc', 0777);
        }

        $entitycore = fopen('PostmanDoc/' . $name . '_pmd.json', 'w+');
        $contenu = json_encode($postmandoc);
        fputs($entitycore, $contenu);

        fclose($entitycore);
    }

    /* CREATION DU FORM */

    public function formGenerator($entity, $listmodule)
    {

        $name = strtolower($entity->name);
        $traitement = new Traitement();

        /* if($name == 'utilisateur')
          return 0; */
        $field = '';
        unset($entity->attribut[0]);

        foreach ($entity->attribut as $attribut) {

            if ($attribut->formtype == "none")
                continue;

            $field .= "
            \$this->fields['" . $attribut->name . "'] = [
                \"label\" => t('" . $entity->name . "." . $attribut->name . "'), \n";

            if ($attribut->nullable == 'default') {
                $field .= "\t\t\tFH_REQUIRE => false,\n ";
            }

            if ($attribut->formtype == 'text' or $attribut->formtype == 'float') {
                $field .= "\t\t\t\"type\" => FORMTYPE_TEXT, 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'integer' or $attribut->formtype == 'number') {
                $field .= "\t\t\t\"type\" => FORMTYPE_NUMBER, 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ",  ";
            } elseif ($attribut->formtype == 'textarea') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'date') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'time') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'datetime') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'datepicker') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'radio') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", 
                \"options\" => " . ucfirst($name) . "::$" . $attribut->name . "s, 
                ";
            } elseif ($attribut->formtype == 'select') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", 
                \"options\" => " . ucfirst($name) . "::$" . $attribut->name . "s, 
                ";
            } elseif ($attribut->formtype == 'email') {
                $field .= "\t\t\t\"type\" => FORMTYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            } elseif ($attribut->formtype == 'document') {
                $field .= "\t\t\t\"type\" => FORMTYPE_FILE,
                FH_FILETYPE => FILETYPE_" . strtoupper($attribut->formtype) . ",  
                \"value\" => \$this->" . $name . "->" . $attribut->name . ",
                \"src\" => \$this->" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'video') {
                $field .= "\t\t\t\"type\" => FORMTYPE_FILE,
                \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ",
                \"src\" => \$this->" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'music') {
                $field .= "\"type\" => FORMTYPE_FILE,
                \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
                \"value\" => \$this->" . $name . "->" . $attribut->name . ",
                \"src\" => \$this->" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } elseif ($attribut->formtype == 'image') {
                $field .= "\t\t\"type\" => FORMTYPE_FILE,
            \"filetype\" => FILETYPE_" . strtoupper($attribut->formtype) . ", 
            \"value\" => \$this->" . $name . "->" . $attribut->name . ",
            \"src\" => \$this->" . $name . "->show" . ucfirst($attribut->name) . "(), ";
            } else {
                $field .= "\"type\" => FORMTYPE_TEXT,
            \"value\" => \$this->" . $name . "->" . $attribut->name . ", ";
            }

            if (isset($attribut->lang) && $attribut->lang) {
                $field .= "\n\"lang\" => true,\n";
            }

            $field .= "
        ];\n";
        }

        if (!empty($entity->relation)) {
            foreach ($entity->relation as $relation) {

                $entitylink = $traitement->relation($listmodule, $relation->entity);
                if (is_null($entitylink))
                    continue;

                $entrel = ucfirst(strtolower($relation->entity));
                $key = 0;
                $enititylinkattrname = "id";
                $entitylink->attribut = (array)$entitylink->attribut;

                if (isset($entitylink->attribut[1])) {
                    $key = 1;
                    $enititylinkattrname = $entitylink->attribut[$key]->name;
                }

                if ($relation->cardinality == 'manyToOne') {
                    $field .= "
        \$this->fields['" . $relation->entity . ".id'] = [
            \"type\" => FORMTYPE_SELECT, 
            \"value\" => \$this->" . $name . "->" . ($relation->entity) . "->id,
            \"label\" => t('" . $relation->entity . "'),
            \"options\" => FormManager::Options_Helper('" . $enititylinkattrname . "', " . ucfirst($relation->entity) . "::allrows()),
        ];\n";
                } elseif ($relation->cardinality == 'oneToOne') {
                    $field .= "
        \$this->fields['" . $relation->entity . ".id'] = [
            \"type\" => FORMTYPE_INJECTION, 
            FH_REQUIRE => true,
            \"label\" => t('entity." . $relation->entity . "'),
            \"imbricate\" => " . ucfirst($relation->entity) . "Form::init(\$this->$name->" . ($relation->entity) . ")->buildForm()->renderForm(),
        ];\n";
                } elseif ($relation->cardinality == 'manyToMany') {
                    $field .= "
        \$this->fields['" . $relation->entity . "::values'] = [
            \"type\" => FORMTYPE_CHECKBOX, 
            \"values\" => \$this->" . $name . "->inCollectionOf('" . ucfirst($relation->entity) . "'),
            \"label\" => t('" . $relation->entity . "'),
            \"options\" => FormManager::Options_Helper('" . $enititylinkattrname . "', " . ucfirst($relation->entity) . "::allrows()),
        ];\n";
                }
            }
        }
        $ucfirstname = ucfirst($name);
        $contenu = "<?php \n
        
use Genesis as g;

class " . ucfirst($name) . "Form extends FormManager{

    public \$$name;

    public static function init(\\$ucfirstname \$$name, \$action = \"\"){
        \$fb = new " . $ucfirstname . "Form(\$$name, \$action);
        \$fb->$name = \$$name;
        return \$fb;
    }

    public function buildForm()
    {
    
        " . $field . "
           
        return  \$this;
    
    }

    public static function renderWidget($" . "id = null, $" . "action = \"create\")
    {
        Genesis::renderView(\"" . $name . ".formWidget\", self::getFormData($" . "id, $" . "action));
    }
    
}
    ";
        $entityform = fopen('Form/' . ucfirst($name) . 'Form.php', 'w');
        fputs($entityform, $contenu);

        fclose($entityform);
    }

    /* CREATION DU FORM FIELD */

    private function formwidget($entity, $listmodule, $onetoone = true)
    {
        $field = '';
        $traitement = new Traitement();
        $name = strtolower($entity->name);

        foreach ($entity->attribut as $attribut) {

            if ($attribut->formtype == "none")
                continue;

            $field .= "<div class='form-group'>
                <label for='" . $attribut->name . "'>{{t('" . $entity->name . "." . $attribut->name . "')}}</label>
            ";

//            if ($attribut->nullable == 'default') {
//                $field .= "\tFH_REQUIRE => false,\n ";
//            }

            if ($attribut->formtype == 'text' or $attribut->formtype == 'float') {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'input' or $attribut->formtype == 'number') {
                $field .= "\t<?= Form::integer('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'textarea') {
                $field .= "\t<?= Form::textarea('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'date') {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'time') {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'datetime') {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'datepicker') {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'radio') {
                $field .= "\t<?= Form::radio('" . $attribut->name . "', " . ucfirst($name) . "::$" . $attribut->name . "s, $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'select') {
                $field .= "\t<?= Form::select('" . $attribut->name . "', " . ucfirst($name) . "::$" . $attribut->name . "s, $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']); ?>\n";
            } elseif ($attribut->formtype == 'email') {
                $field .= "\t<?= Form::email('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']) ?>\n";
            } elseif (in_array($attribut->formtype, ['document', 'image', 'musique', 'video'])) {

                $field .= "\t<?= Form::filepreview($" . $name . "->" . $attribut->name . ",
                $" . $name . "->show" . ucfirst($attribut->name) . "(),
                 ['class' => 'form-control'], 'image') ?>\n";

                if ($attribut->formtype == 'document') {
                    $field .= "\t<?= Form::file('" . $attribut->name . "', 
                $" . $name . "->" . $attribut->name . ",
                 ['class' => 'form-control'], 'document') ?>\n";
                } elseif ($attribut->formtype == 'video') {
                    $field .= "\t<?= Form::file('" . $attribut->name . "', 
                $" . $name . "->" . $attribut->name . ",
                 ['class' => 'form-control'], 'video') ?>\n";
                } elseif ($attribut->formtype == 'music') {
                    $field .= "\t<?= Form::file('" . $attribut->name . "', 
                $" . $name . "->" . $attribut->name . ",
                 ['class' => 'form-control'], 'audio') ?>\n";
                } elseif ($attribut->formtype == 'image') {
                    $field .= "\t<?= Form::file('" . $attribut->name . "', 
                $" . $name . "->" . $attribut->name . ",
                '',
                 ['class' => 'form-control'], 'image') ?>\n";

                }
            } else {
                $field .= "\t<?= Form::input('" . $attribut->name . "', $" . $name . "->" . $attribut->name . ", ['class' => 'form-control']) ?>\n";

            }

            $field .= " </div>\n";
        }

        if (!empty($entity->relation)) {
            foreach ($entity->relation as $relation) {

                $entitylink = $traitement->relation($listmodule, $relation->entity);

                $enititylinkattrname = "id";
                $entitylink->attribut = (array)$entitylink->attribut;

                if (isset($entitylink->attribut[1])) {
                    $key = 1;
                    $enititylinkattrname = $entitylink->attribut[$key]->name;
                }

                $field .= "<div class='form-group'><label for='" . $relation->entity . "'>" . ucfirst($relation->entity) . "</label>";

                if ($relation->cardinality == 'manyToOne') {
                    $field .= "
                    <?= Form::select('" . $relation->entity . "', 
                    FormManager::Options_Helper('" . $enititylinkattrname . "', " . ucfirst($relation->entity) . "::allrows()),
                    $" . $name . "->get" . ucfirst($relation->entity) . "()->getId(),
                    ['class' => 'form-control']); ?>\n";

                } elseif ($relation->cardinality == 'oneToOne' && $onetoone) {
                    $field .= "<?php $" . $relation->entity . " = $" . $name . "->get" . ucfirst($relation->entity) . "(); ?>";
                    $field .= "
                    <?= Form::imbricate($" . $relation->entity . ") ?>";
                    $field .= $this->formwidget($entitylink, $listmodule, false);
                    $field .= "<?= Form::closeimbricate() ?>\n";
                } elseif ($relation->cardinality == 'manyToMany') {
                    //FormManager::Options_ToCollect_Helper('name', new Dvups_right(), $dvups_role->getDvups_right()
                    $field .= "
                    <?= Form::checkbox('" . $relation->entity . "', 
                    FormManager::Options_Helper('" . $enititylinkattrname . "', " . ucfirst($relation->entity) . "::allrows()),
                    $" . $name . "->inCollectionOf('" . ucfirst($relation->entity) . "'),
                    ['class' => 'form-control']); ?>\n";
                }

                $field .= " </div>
            ";
            }
        }

        return $field;

    }


    /* CREATION DU FORM FIELD */

    private function detailwidget($entity, $listmodule, $onetoone = true, $mother = false)
    {

        $name = strtolower($entity->name);
        //$detailview = DvAdmin::builddetaildatatable($entity, $listmodule, $onetoone, $mother);
        //return $field;
        return "
        <div class=\"col-lg-12 col-md-12\">
                <?= " . ucfirst($name) . "Table::init()
                ->builddetailtable()
                ->renderentitydata($" . $name . "); ?>
        </div>
			";

    }

    public function detailWidgetGenerator($entity, $listmodule)
    {

        $name = strtolower($entity->name);

        /* if($name == 'utilisateur')
          return 0; */
        unset($entity->attribut[0]);
        $contenu = $this->detailwidget($entity, $listmodule);

        $entityform = fopen('Resource/views/' . $name . '/detail.blade.php', 'w');
        //$entityform = fopen('Form/' . ucfirst($name) . 'DetailWidget.php', 'w');
        fputs($entityform, $contenu);

        fclose($entityform);
    }

    public function formWidgetGenerator($entity, $listmodule)
    {

        $name = strtolower($entity->name);

        /* if($name == 'utilisateur')
          return 0; */
        unset($entity->attribut[0]);
        $field = $this->formwidget($entity, $listmodule);

        $contenu = "
    <?php //use dclass\devups\Form\Form; ?>
    <?php //Form::addcss(" . ucfirst($name) . " ::classpath('Resource/js/" . $name . "')) ?>
    
    <?= Form::open($" . $name . ", [\"action\"=> \"$" . "action\", \"method\"=> \"post\"]) ?>

     " . $field . "
       
    <?= Form::submitbtn(\"save\", ['class' => 'btn btn-success btn-block']) ?>
    
    <?= Form::close() ?>
    
    <?= Form::addDformjs() ?>    
    <?= Form::addjs(" . ucfirst($name) . "::classpath('Resource/js/" . $name . "Form')) ?>
    ";

        if (!file_exists('Resource/views/admin/' . $name . ''))
            mkdir('Resource/views/admin/' . $name . '', 777, true);

        $entityform = fopen('Resource/views/admin/' . $name . '/formWidget.blade.php', 'w');
        fputs($entityform, $contenu);

        fclose($entityform);
    }

    /* CREATION DU DAO */

}
