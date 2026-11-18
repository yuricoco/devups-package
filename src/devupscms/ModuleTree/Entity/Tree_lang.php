<?php

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
        $this->lang = new Dvups_lang();
    }

}