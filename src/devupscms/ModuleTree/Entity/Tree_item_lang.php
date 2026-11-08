<?php

/**
 * @Entity @Table(name="tree_item_lang")
 * */
class Tree_item_lang extends \Dv_langCore
{

    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     **/
    protected $name;
    /**
     * @Column(name="content", type="text"  , nullable=true)
     * @var text
     **/
    protected $content;

    /**
     * @Id @ManyToOne(targetEntity="\Tree_item")
     * @JoinColumn(onDelete="cascade")
     * @var \Tree_item
     */
    public $tree_item;

    public function __construct($id = null){
        $this->tree_item = new Tree_item();
        $this->lang = new Dvups_lang();
    }

}