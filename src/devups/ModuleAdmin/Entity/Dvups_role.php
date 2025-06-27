<?php

/**
 * @Entity @Table(name="dvups_role")
 * */
class Dvups_role extends Model implements JsonSerializable
{

    static $SELLER = "seller";

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     * */
    protected $name;
    /**
     * @Column(name="alias", type="string" , length=255 )
     * @var string
     * */
    protected $alias;
    /**
     * @Column(name="configs", type="json" , nullable=true )
     * @var string
     * */
    public $configs;
    /**
     * @Column(name="components", type="text" , nullable=true )
     * @var string
     * */
    public $components;
    /**
     * @Column(name="modules", type="text" , nullable=true )
     * @var string
     * */
    public $modules;
    /**
     * @Column(name="entities", type="text" , nullable=true )
     * @var string
     * */
    public $entities;

    /**
     * @var \Dvups_right
     */
    public $dvups_right;

    public $dv_collection = [ "dvups_right",];

    public function array_rigth()
    {
        $array_rigth = [];

        foreach ($this->rigth as $rigth) {
            $array_rigth[] = strtolower($rigth->getNom());
        }
        return $array_rigth;
    }

    public function __construct($id = null)
    {

        if ($id)
            $this->id = $id;

        $this->dvups_right = [];

    }

    function collectDvups_right()
    {
        $this->dvups_right = $this->__hasmany('dvups_right', true, false, 1);
        return $this->dvups_right;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getName()
    {
        return $this->name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *  manyToMany
     * @return \Dvups_right
     */
    function setDvups_right($rigth)
    {
        $this->dvups_right = $rigth;
    }

    function setModules($dvups_module)
    {
        $this->modules = implode(",", $dvups_module);;
    }

    function setComponents($dvups_module)
    {
        $this->components = implode(",", $dvups_module);
    }

    function setEntities($dvups_entity)
    {
        $this->entities = implode(",", $dvups_entity);
    }

    function getAttribute($attrib)
    {
        return explode(",", $this->{$attrib});
    }

    function updateConfigs()
    {
        $comps = $this->getAttribute('components');
        $mods = $this->getAttribute('modules');
        $ents = $this->getAttribute('entities');
        $globalconfig = require ROOT.'config/dvups_configurations.php';
//        dv_dump($globalconfig);
        $global_navigation = Core::buildOriginCore(function ($core, $type) use ($globalconfig, $comps, $mods, $ents){
            if ($type == 'component'){
                return in_array($core, $comps);
            }elseif ($type == 'module'){
                return in_array($core, $mods);
            }elseif ($type == 'entity'){
//                dv_dump($core, $ents, in_array($core, $ents));
                if(in_array(strtolower($core), $ents))
                    return $globalconfig[''.ucfirst($core)];
            }else
                return false;
        });

//        dv_dump($global_navigation);
        $this->configs = json_encode($global_navigation);
        return $global_navigation;

    }

    function getConfigs(){
        return json_decode($this->configs, true) ?? [];
    }

    /**
     *  manyToMany
     * @return \Dvups_right
     */
    function getDvups_right()
    {
        return $this->dvups_right;
    }

    function addDvups_right(\Dvups_right $dvups_right)
    {
        $this->dvups_right[] = $dvups_right;
    }

    function dropDvups_rightCollection()
    {
        $this->dvups_right = EntityCollection::entity_collection('dvups_right');
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alias' => $this->alias,
            'dvups_right' => $this->dvups_right,
        ];
    }

    public function is($role)
    {
        if ($this->name == $role)
            return true;

        return false;
    }

    public function isIn($roles)
    {
        if (in_array($this->name, $roles))
            return true;

        return false;
    }

    public static function updateprivilegeAction()
    {

        $admin = getadmin();
        if ($admin->dvups_role->hydrate()->is("admin"))
            return '<button onclick="model.updateprivilege(this)"  class=\'btn btn-info\'> ' . t("Update Privilege") . ' </button>';

    }

}
