<?php

namespace devupscms\ModuleData\Entity;

use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "status_lang")]
class Status_lang extends \Dv_langCore
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

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Status_lang::class, 'Status_lang');
