<?php

namespace devups\ModuleConfig\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "dvups_entity_lang")]
class Dvups_entity_lang extends \Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "label", type: "string", length: 100)]
    protected $label;

    /**
     * @var \Dvups_entity
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Dvups_entity")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $dvups_entity;



}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dvups_entity_lang::class, 'Dvups_entity_lang');
