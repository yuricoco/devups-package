<?php

namespace devupscms\ModuleTree\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "tree_item_lang")]
class Tree_item_lang extends \Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "name", type: "string", length: 255)]
    protected $name;
    /**
     * @var text
     */
    #[ORM\Column(name: "content", type: "text", nullable: true)]
    protected $content;

    /**
     * @var \Tree_item
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Tree_item")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $tree_item;

    public function __construct($id = null){
        $this->tree_item = new Tree_item();
        $this->lang = new \Dvups_lang();
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Tree_item_lang::class, 'Tree_item_lang');