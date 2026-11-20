<?php

namespace devupscms\ModuleReporting\Entity;

use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "push_email")]
class Push_email extends \Model implements \JsonSerializable, \DatatableOverwrite
{

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var date
     */
    #[ORM\Column(name: "date_start", type: "date", nullable: true)]
    protected $date_start;
    /**
     * @var date
     */
    #[ORM\Column(name: "date_end", type: "date", nullable: true)]
    protected $date_end;
    /**
     * @var date
     */
    #[ORM\Column(name: "last_call", type: "date", nullable: true)]
    protected $last_call;
    /**
     * @var date
     */
    #[ORM\Column(name: "next_call", type: "date", nullable: true)]
    protected $next_call;
    /**
     * @var date
     */
    #[ORM\Column(name: "status", type: "integer", nullable: true)]
    protected $status = 1;
    /**
     * @var integer
     */
    #[ORM\Column(name: "interval", type: "integer", nullable: true)]
    protected $interval;
    /**
     * @var integer
     */
    #[ORM\Column(name: "reference", type: "string", length: 255, nullable: true)]
    protected $reference;
    /**
     * @var integer
     */
    #[ORM\Column(name: "description", type: "text", nullable: true)]
    protected $description;
    /**
     * @var Reportingmodel
     */
    #[ORM\ManyToOne(targetEntity: "\Reportingmodel")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $reportingmodel;
    /**
     * @var \Notificationtype
     */
    #[ORM\ManyToOne(targetEntity: "\Notificationtype")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $notificationtype;

    /**
     * @var integer
     */
    #[ORM\Column(name: "constraint", type: "text", nullable: true)]
    protected $constraint;


    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

        $this->reportingmodel = new Reportingmodel();
        $this->notificationtype = new \Notificationtype();

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


    #[\ReturnTypeWillChange]
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

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Push_email::class, 'Push_email');
