<?php

/**
 * @Entity @Table(name="tree_lang")
 * */
class Tree_lang extends \Dv_langCore
{

    /**
     * @Column(name="label", type="string" , length=255 )
     * @var string
     **/
    protected $label;

    /**
     * @Id @ManyToOne(targetEntity="\Tree")
     * @JoinColumn(onDelete="cascade")
     * @var \Tree
     */
    public $tree;

    public function __construct($id = null){
        $this->tree = new Tree();
        $this->lang = new Dvups_lang();
    }

}