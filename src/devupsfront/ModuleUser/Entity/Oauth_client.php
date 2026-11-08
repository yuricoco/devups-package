<?php

namespace devupsfront\ModuleUser\Entity;
use JsonSerializable;
use Model;

/**
 * @Entity @Table(name="oauth_client")
 * */
class Oauth_client extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="client", type="string" , length=55 , nullable=true)
     * @var string
     **/
    protected $client;
    /**
     * @Column(name="name", type="string" , length=155 , nullable=true)
     * @var string
     **/
    protected $name;
    /**
     * @Column(name="user_ext_id", type="string" , length=55 )
     * @var string
     **/
    protected $user_ext_id;
    /**
     * @Column(name="user_id", type="integer" )
     * @var string
     **/
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
