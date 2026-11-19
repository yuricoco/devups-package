<?php

namespace devupscms\ModuleTree\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "tree_lang")]
class Tree_lang extends \Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "label", type: "string", length: 255)]
    protected $label;

    /**
     * @var \Tree
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Tree")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $tree;

    public function __construct($id = null){
        $this->tree = new Tree();
        $this->lang = new \Dvups_lang();
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Tree_lang::class, 'Tree_lang');