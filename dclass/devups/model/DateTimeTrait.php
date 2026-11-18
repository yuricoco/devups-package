<?php

namespace dclass\devups\model;
use Doctrine\ORM\Mapping as ORM;

trait DateTimeTrait
{

    /**
     * @var string
     */
    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected $created_at;
    /**
     * @var string
     */
    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    protected $updated_at;
    /**
     * @var string
     */
    #[ORM\Column(name: "deleted_at", type: "datetime", nullable: true)]
    protected $deleted_at;

    /**
     * @return string
     */
    public function getCreatedAt($format = "d/m/Y")
    {
        if (!$format)
            return $this->created_at;

        return date($format, strtotime($this->created_at));
    }
    /**
     * @return float
     */
    public function getTimeStamp($attribute = "created_at", $precision = 1000 )
    {
        return  (new \DateTime($this->{$attribute}))->getTimestamp() * $precision;
    }

    /**
     * @param string $created_at
     */
    public function setCreatedAt($created_at)
    {
        if ($created_at)
            $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getUpdatedAt($format = "")
    {
        if (!$format)
            return $this->updated_at;

        return date($format, strtotime($this->updated_at));
    }

    /**
     * @param string $updated_at
     */
    public function setUpdatedAt($updated_at)
    {
        if ($updated_at)
            $this->updated_at = $updated_at;
    }

    /**
     * @return string
     */
    public function getDeletedAt($format = "")
    {
        if (!$format)
            return $this->deleted_at;

        return date($format, strtotime($this->deleted_at));
    }

    /**
     * @param string $deleted_at
     */
    public function setDeletedAt($deleted_at)
    {
        $this->deleted_at = $deleted_at;
    }

}