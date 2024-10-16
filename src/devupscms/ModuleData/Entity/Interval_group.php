<?php
// user \dclass\devups\model\Model;

namespace devupscms\ModuleData\Entity;
use JsonSerializable;
use stdClass;
use Tree_item;

/**
 * @Entity @Table(name="interval_group")
 * */
class Interval_group extends stdClass implements JsonSerializable
{

    use \ModelTrait;

    /**
     * @Id @ManyToOne(targetEntity="\Interval")
     * @JoinColumn(onDelete="cascade")
     * @var \Interval
     */
    public $interval;

    /**
     * @Id @ManyToOne(targetEntity="\Tree_item")
     * @JoinColumn(onDelete="cascade")
     * @var \Tree_item
     */
    public $barem;


    public function __construct()
    {

        $this->interval = new \Interval();
        $this->barem = new Tree_item();
    }


    public function jsonSerialize()
    {
        return [
            'interval' => $this->interval,
            'barem' => $this->barem,
        ];
    }

}
