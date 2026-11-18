<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "reportingmodel_lang")]
class Reportingmodel_lang extends Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "title", type: "string", length: 255, nullable: true)]
    protected $title;
    /**
     * @var string
     */
    #[ORM\Column(name: "object", type: "string", length: 255, nullable: true)]
    protected $object;
    /**
     * @var text
     */
    #[ORM\Column(name: "contenttext", type: "text", nullable: true)]
    protected $contenttext;
    /**
     * @var text
     */
    #[ORM\Column(name: "content", type: "text", nullable: true)]
    protected $content;
    /**
     * @var \Reportingmodel
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Reportingmodel")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $reportingmodel;

}
