<?php

/**
 * @Entity @Table(name="dvups_entity")
 * */
class Dvups_entity extends Dvups_config_item implements JsonSerializable
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

    public function exist(){
        if(class_exists($this->namespace.'\\'.ucfirst($this->name)))
            return true;

        //parent::__delete();
        return  false;
    }

    public function __construct($id = null)
    {
        parent::__construct($id);

    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
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
