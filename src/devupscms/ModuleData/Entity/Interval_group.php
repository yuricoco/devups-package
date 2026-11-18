<?php
// user \dclass\devups\model\Model;

namespace devupscms\ModuleData\Entity;
use JsonSerializable;
use stdClass;
use Tree_item;

use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: "interval_group")]
class Interval_group extends stdClass implements JsonSerializable
{

    use \ModelTrait;

    /**
     * @var \Interval
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Interval")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $interval;

    /**
     * @var \Tree_item
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Tree_item")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $barem;


    public function __construct()
    {

        $this->interval = new \Interval();
        $this->barem = new Tree_item();
    }


    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'interval' => $this->interval,
            'barem' => $this->barem,
        ];
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2". Cette
// entite etait deja namespacee avant ce chantier (migration partielle
// anterieure non documentee, aucun site vivant ne la referencait par son
// nom court jusqu'ici - seul IntervalController.php l'importe deja par sa
// FQCN) - alias ajoute par coherence avec le reste du module.
class_alias(Interval_group::class, 'Interval_group');
