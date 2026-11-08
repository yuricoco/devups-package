<?php
// user \dclass\devups\model\Model;
namespace devupscms\ModulePushNotification\Entity;
use JsonSerializable;
use Model;

/**
 * @Entity @Table(name="workers")
 * */
class Workers extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="queue", type="text"   )
     * @var string
     **/
    public $queue;
    /**
     * @Column(name="payload", type="text", nullable=true   )
     * @var integer
     **/
    protected $payload;
    /**
     * @Column(name="type", type="string" , length=55 )
     * @var string
     **/
    protected $type;
    /**
     * @Column(name="callback", type="text" , nullable=true )
     * @var string
     **/
    protected $callback;
    /**
     * @Column(name="log", type="string" , length=255, nullable=true )
     * @var string
     **/
    protected $log;
    /**
     * @Column(name="timer", type="integer" , nullable=true )
     * @var string
     **/
    protected $timer = 5;


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

    // getter and setter


}
