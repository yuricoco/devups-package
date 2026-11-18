<?php

namespace devups\ModuleConfig\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "dvups_entity")]
class Dvups_entity extends \Dvups_config_item implements \JsonSerializable
{

    // NB (v8.0, LOT 1.2) : $id est deja declare par la classe parente
    // Dvups_config_item (MappedSuperclass) - une redeclaration ici, tolere
    // sous l'ancien AnnotationDriver, devient un conflit "duplicate column"
    // sous l'AttributeDriver (Doctrine ORM 3). Aucun changement de schema :
    // la colonne id reste celle definie par Dvups_config_item.

    /**
     * @var string
     */
    #[ORM\Column(name: "namespace", type: "string", length: 250, options: ["default" => ""])]
    protected $namespace = "";
    /**
     * @var string
     */
    #[ORM\Column(name: "enablenotification", type: "integer")]
    protected $enablenotification = 0;

    /**
     * @var string
     */
    #[ORM\Column(name: "multi_lang", type: "integer", nullable: true)]
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
    #[\ReturnTypeWillChange]
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
        // ETAPE M.2 : Notificationbroadcasted (ancien nom de l'entite
        // Notification, jamais nettoye lors du renommage - cette methode
        // n'etait d'ailleurs jamais requise par le module, donc toujours
        // en erreur fatale "classe introuvable" si appelee) -> Notification::of().
        if ($this->enablenotification == 1)
            return \Notification::of($this->name);
    }

    public static function describe($table)
    {
        return (new \DBAL())->executeDbal("DESCRIBE `$table`; ", [], \DBAL::$FETCHALL);
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dvups_entity::class, 'Dvups_entity');
