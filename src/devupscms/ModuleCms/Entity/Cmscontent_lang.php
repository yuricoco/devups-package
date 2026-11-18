<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "cmscontent_lang")]
class Cmscontent_lang extends Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "title", type: "string", length: 255, nullable: true)]
    protected $title;
    /**
     * @var text
     */
    #[ORM\Column(name: "content", type: "text", nullable: true)]
    protected $content;
    /**
     * @var text
     */
    #[ORM\Column(name: "sommary", type: "text", nullable: true)]
    protected $sommary = "";
    /**
     * @var \Cmscontent
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Cmscontent")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $cmscontent;

}
