<?php
// user \dclass\devups\model\Model;
namespace devupscms\ModulePushNotification\Entity;
use JsonSerializable;
use Model;

use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: "workers")]
class Workers extends Model implements JsonSerializable
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
    #[ORM\Column(name: "queue", type: "text")]
    public $queue;
    /**
     * @var integer
     */
    #[ORM\Column(name: "payload", type: "text", nullable: true)]
    protected $payload;
    /**
     * @var string
     */
    #[ORM\Column(name: "type", type: "string", length: 55)]
    protected $type;
    /**
     * @var string
     */
    #[ORM\Column(name: "callback", type: "text", nullable: true)]
    protected $callback;
    /**
     * @var string
     */
    #[ORM\Column(name: "log", type: "string", length: 255, nullable: true)]
    protected $log;
    /**
     * @var string
     */
    #[ORM\Column(name: "timer", type: "integer", nullable: true)]
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
