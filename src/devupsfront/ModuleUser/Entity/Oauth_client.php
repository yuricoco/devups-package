<?php

namespace devupsfront\ModuleUser\Entity;
use JsonSerializable;
use Model;

use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: "oauth_client")]
class Oauth_client extends Model implements JsonSerializable
{

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var string
     */
    #[ORM\Column(name: "client", type: "string", length: 55, nullable: true)]
    protected $client;
    /**
     * @var string
     */
    #[ORM\Column(name: "name", type: "string", length: 155, nullable: true)]
    protected $name;
    /**
     * @var string
     */
    #[ORM\Column(name: "user_ext_id", type: "string", length: 55)]
    protected $user_ext_id;
    /**
     * @var string
     */
    #[ORM\Column(name: "user_id", type: "integer")]
    protected $user_id;

    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

    }

    public function getId()
    {
        return $this->id;
    }


}
