<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "interval")]
class Interval extends Model implements JsonSerializable
{

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var float
     */
    #[ORM\Column(name: "_min", type: "float")]
    protected $_min;
    /**
     * @var float
     */
    #[ORM\Column(name: "_max", type: "float")]
    protected $_max;
    /**
     * @var string
     */
    #[ORM\Column(name: "label", type: "string", length: 150, nullable: true)]
    protected $label;
    /**
     * @var string
     */
    #[ORM\Column(name: "_value", type: "string", length: 120)]
    protected $_value;
    /**
     * @var string
     */
    #[ORM\Column(name: "description", type: "text", nullable: true)]
    protected $description;

    /**
     * @var integer
     */
    #[ORM\Column(name: "entity", type: "string", length: 55)]
    public $entity;

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

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            '_min' => $this->_min,
            '_max' => $this->_max,
            'label' => $this->label,
            '_value' => $this->_value,
        ];
    }

    public $in_group;

    public static function lazyloading($order = "", $qb = null, $debug = false)
    {
        $qb = self::select();
        if ($member_id = Request::get('member_id')) {
            $qb->addColumn(" select COUNT(*) from member_interval where interval_id = this.id AND member_id = $member_id ", 'checked');
        }

        return $qb->lazyloading();

    }

    public static function groupIn($groups, $member = null, $session = null)
    {
        $qb = self::select()
            ->leftJoinOn('interval_group', 'fg', 'fg.interval_id = this.id')
            ->leftJoinOn('tree_item', 'barem ', 'barem.id = fg.barem_id')
            ->whereIn('barem.slug', $groups)
            ->orderBy('this.name');
        if ($member)
            $qb->leftJoinOn("member_session_interval", 'msf',
                "msf.member_id = {$member->id} AND msf.interval_id = this.id AND msf.session_id = {$session->id}")
                ->addColumns("msf.amount AS member_contribution");

        return $qb;
    }

    public static function group($group, $member = null, $session = null)
    {
        $qb = self::select()
            ->leftJoinOn('interval_group', 'fg', 'fg.interval_id = this.id')
            ->leftJoinOn('tree_item', 'barem ', 'barem.id = fg.barem_id')
            ->where('barem.slug', $group)
            //->orderBy('this.name')
        ;
        if ($member)
            $qb->leftJoinOn("member_session_interval", 'msf',
                "msf.member_id = {$member->id} AND msf.interval_id = this.id AND msf.session_id = {$session->id}")
                ->addColumns("msf.amount AS member_contribution");

        return $qb;
    }

    public static function getGroup($group, $member = null, $session = null)
    {
        return self::group($group, $member, $session)->get();
    }
    
}
