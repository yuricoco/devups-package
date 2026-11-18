<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "country_lang")]
class Country_lang extends Dv_langCore
{

    /**
     * @var string
     */
    #[ORM\Column(name: "nicename", type: "string", length: 100)]
    protected $nicename;

    /**
     * @var \Country
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Country")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $country;



}
