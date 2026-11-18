<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "notificationtype_lang")]
class Notificationtype_lang extends Dv_langCore
{

    /**
     * @var \Notificationtype
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Notificationtype")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $notificationtype;
    /**
     * @var integer
     */
    #[ORM\Column(name: "content", type: "string", length: 255)]
    protected $content;


}
