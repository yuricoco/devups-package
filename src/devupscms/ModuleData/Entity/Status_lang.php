<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "status_lang")]
class Status_lang extends Dv_langCore
{

    /**
     * @var \Status
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Status")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $status;
    /**
     * @var integer
     */
    #[ORM\Column(name: "label", type: "string", length: 55)]
    protected $label;


}
