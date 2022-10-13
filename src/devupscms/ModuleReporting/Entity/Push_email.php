<?php
// user \dclass\devups\model\Model;

/**
 * @Entity @Table(name="push_email")
 * */
class Push_email extends Model implements JsonSerializable, DatatableOverwrite
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="date_start", type="date" , nullable=true )
     * @var date
     **/
    protected $date_start;
    /**
     * @Column(name="date_end", type="date"  , nullable=true)
     * @var date
     **/
    protected $date_end;
    /**
     * @Column(name="last_call", type="date"  , nullable=true)
     * @var date
     **/
    protected $last_call;
    /**
     * @Column(name="next_call", type="date"  , nullable=true)
     * @var date
     **/
    protected $next_call;
    /**
     * @Column(name="status", type="integer"  , nullable=true)
     * @var date
     **/
    protected $status = 1;
    /**
     * @Column(name="interval", type="integer"  , nullable=true)
     * @var integer
     **/
    protected $interval;
    /**
     * @Column(name="reference", type="string", length=255 , nullable=true)
     * @var integer
     **/
    protected $reference;
    /**
     * @Column(name="description", type="text"  , nullable=true)
     * @var integer
     **/
    protected $description;
    /**
     * @ManyToOne(targetEntity="\Reportingmodel")
     * @JoinColumn(onDelete="cascade")
     * @var \Reportingmodel
     */
    public $reportingmodel;
    /**
     * @ManyToOne(targetEntity="\Notificationtype")
     * @JoinColumn(onDelete="cascade")
     * @var \Notificationtype
     */
    public $notificationtype;

    /**
     * @Column(name="constraint", type="text"  , nullable=true)
     * @var integer
     **/
    protected $constraint;


    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

        $this->reportingmodel = new Reportingmodel();
        $this->notificationtype = new Notificationtype();

    }

    public function getId()
    {
        return $this->id;
    }

    public function getDate_end()
    {
        return $this->date_end;
    }

    public function setDate_end($date_end)
    {
        $this->date_end = $date_end;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    public function getConstraint()
    {
        return $this->constraint;
    }

    public function setConstraint($constraint)
    {
        $this->constraint = $constraint;
    }
    public function setLast_call($constraint)
    {
        $this->last_call = $constraint;
    }
    public function setNext_call($constraint)
    {
        $this->next_call = $constraint;
    }

    /**
     *  manyToOne
     * @return \Notificationtype
     */
    function getNotificationtype()
    {
        return $this->notificationtype;
    }

    function setNotificationtype(\Notificationtype $notificationtype)
    {
        $this->notificationtype = $notificationtype;
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'date_end' => $this->date_end,
            'interval' => $this->interval,
            'constraint' => $this->constraint,
            'notificationtype' => $this->notificationtype,
        ];
    }

    public function editAction($btarray)
    {
        // TODO: Implement editAction() method.
    }

    public function showAction($btarray)
    {
        return " ";
    }

    public function deleteAction($btarray)
    {
        // TODO: Implement deleteAction() method.
    }
}
