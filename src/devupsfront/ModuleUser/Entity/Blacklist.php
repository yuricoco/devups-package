<?php
// user \dclass\devups\model\Model;

namespace devupsfront\ModuleUser\Entity;
use JsonSerializable;
use Model;
use User;

/**
 * @Entity @Table(name="blacklist")
 * */
class Blacklist extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="comment", type="integer"  )
     * @var integer
     **/
    protected $comment;
    /* enum */
    public static $subjects = ['beneficiary', 'help', 'lending'];
    /**
     * @Column(name="subject", type="string" , length=150 )
     * @var string
     **/
    protected $subject;

    /**
     * @ManyToOne(targetEntity="\User")
     * @JoinColumn(onDelete="cascade")
     * @var \User
     */
    public $user;

    /**
     * @ManyToOne(targetEntity="\User")
     * @JoinColumn(onDelete="cascade")
     * @var \User
     */
    public $accuse;


    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

        $this->user = new User();
        $this->accuse = new User();
    }

    public function getId()
    {
        return $this->id;
    }

    // getter and setter

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'subject' => $this->subject,
            'user' => $this->user,
            'accuse' => $this->accuse,
        ];
    }

    public static function exist($userid, $accuseid)
    {

        return self::where_str(" (this.user_id = $userid AND this.accuse_id = $accuseid) ")
            ->where_str(" (this.user_id = $accuseid AND this.accuse_id = $userid) ", " OR ")
            //todo exclude those whose type = "g"
            ->count();
    }

}
