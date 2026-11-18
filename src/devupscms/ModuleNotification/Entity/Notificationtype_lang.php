<?php

namespace devupscms\ModuleNotification\Entity;

use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "notificationtype_lang")]
class Notificationtype_lang extends \Dv_langCore
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

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Notificationtype_lang::class, 'Notificationtype_lang');
