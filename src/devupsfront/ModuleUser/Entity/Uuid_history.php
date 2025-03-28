<?php
// user \dclass\devups\model\Model;
namespace devupsfront\ModuleUser\Entity;
use JsonSerializable;
use Model;
use User;

/**
 * @Entity @Table(name="uuid_history")
 * */
class Uuid_history extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="uuid", type="string" , length=55 , nullable=true)
     * @var string
     **/
    protected $uuid;
    /**
     * @Column(name="username", type="string" , length=155 , nullable=true)
     * @var string
     **/
    protected $username;

    /**
     * @ManyToOne(targetEntity="\User")
     * @JoinColumn(onDelete="cascade")
     * @var \User
     */
    public $user;

    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

        $this->user = new User();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     *  manyToOne
     * @return \User
     */
    function getUser()
    {
        return $this->user;
    }

    function setUser(\User $user)
    {
        $this->user = $user;
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user' => $this->user,
            'username' => $this->username,
        ];
    }

}
