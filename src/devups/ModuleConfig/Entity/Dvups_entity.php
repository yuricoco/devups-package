<?php

/**
 * @Entity @Table(name="dvups_entity")
 * */
class Dvups_entity extends Dvups_config_item implements JsonSerializable, DvupsTranslation
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;

    /**
     * @Column(name="namespace", type="string" , length=250 , options={"default" : ""} )
     * @var string
     **/
    protected $namespace = "";
    /**
     * @Column(name="enablenotification", type="integer")
     * @var string
     **/
    protected $enablenotification = 0;

    /**
     * @Column(name="multi_lang", type="integer", nullable=true)
     * @var string
     **/
    protected $multi_lang = 0;

    /**
     * @ManyToOne(targetEntity="\Dvups_module")
     * @JoinColumn(onDelete="cascade")
     * @var \Dvups_module
     */
    public $dvups_module;

    /**
     * @var \Dvups_right
     */
    public $dvups_right;

    public static function getRigthOf($action)
    {
        $entity = Dvups_entity::select()->where('this.name', $action)->getInstance();
        $drigths = $entity->__hasmany(Dvups_right::class);
        $rights = [];

        foreach ($drigths as $right) {
            $rights[] = $right->getName();
        }

        return $rights;
    }

    public function exist(){
        if(class_exists($this->name))
            return true;

        //parent::__delete();
        return  false;
    }

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->dvups_module = new Dvups_module();
        $this->dvups_right = EntityCollection::entity_collection('dvups_right');
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *  manyToOne
     * @return \Dvups_module
     */
    function getDvups_module()
    {
        return $this->dvups_module;
    }

    function setDvups_module($dvups_module)
    {
        $this->dvups_module = $dvups_module;
    }

    function setDvups_right($dvups_right)
    {
        $this->dvups_right = $dvups_right;
    }

    /**
     *  manyToMany
     * @return \Dvups_right
     */
    function getDvups_right()
    {
        return $this->dvups_right;
    }

    function collectDvups_right()
    {
        $this->dvups_right = $this->__hasmany('dvups_right');
        return $this->dvups_right;
    }

    function availableright()
    {
        $this->dvups_right = $this->__hasmany('dvups_right');
        if ($this->dvups_right) {
            foreach ($this->dvups_right as $right) {
                $rights[] = $right->getName();
            }
            return $rights;
        }

        return [];
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
            'label' => $this->label,
            'dvups_module' => $this->dvups_module,
            //'dvups_right' => $this->dvups_right,
        ];
    }

    public function getLinkname()
    {
        return "<a href='". route('src/' . strtolower($this->dvups_module->project) . '/'
                . $this->dvups_module->name . '/' . $this->getUrl()) . '/list'."' >
<i class=\"metismenu-icon\"></i> ".$this->getLabel()."| manage</a>";
    }

    public function __delete($exec = true)
    {
        return parent::__delete($exec); // TODO: Change the autogenerated stub
    }


    public function route($path = "/list"){

        return route('admin/' .strtolower($this->dvups_module->project) . '/' . $this->dvups_module->name . '/' . $this->url . $path);
        //return route('src/' . strtolower($this->dvups_module->project) . '/' . $this->dvups_module->name . '/' . $this->url . $path);
    }

    public function dvupsTranslate()
    {
        // TODO: Implement dvupsTranslate() method.
    }

    public function truncate()
    {

        $sql = "TRUNCATE `".$this->name."` ";
        $dbal = new DBAL();
        $dbal->executeDbal($sql);

    }

    public function countRow()
    {
        if(!$this->exist())
            return "deleted";

        return ucfirst($this->getLabel())::count();
    }

    public static function menu()
    {
        return self::where("name", "menu");
    }

    public function alert()
    {
        if ($this->enablenotification == 1)
            return Notificationbroadcasted::of($this->name);
    }

    public static function describe($table)
    {
        return (new DBAL())->executeDbal("DESCRIBE `$table`; ", [], DBAL::$FETCHALL);
    }

}
