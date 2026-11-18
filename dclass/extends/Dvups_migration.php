<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Trace des fichiers de database/migration deja joues par
 * `php devups migrate` (MigrationRunner) - jamais modifiee a la main.
 */
#[ORM\Entity]
#[ORM\Table(name: "dvups_migration")]
class Dvups_migration extends Model implements JsonSerializable
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
    #[ORM\Column(name: "migration", type: "string", length: 255)]
    protected $migration;

    /**
     * @var int
     */
    #[ORM\Column(name: "batch", type: "integer")]
    protected $batch;

    /**
     * @var datetime
     */
    #[ORM\Column(name: "executed_at", type: "datetime")]
    protected $executed_at;

    public function __construct($id = null)
    {
        if ($id)
            $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMigration()
    {
        return $this->migration;
    }

    public function setMigration($migration)
    {
        $this->migration = $migration;
    }

    public function getBatch()
    {
        return $this->batch;
    }

    public function setBatch($batch)
    {
        $this->batch = $batch;
    }

    public function getExecutedAt()
    {
        return $this->executed_at;
    }

    public function setExecutedAt($executed_at)
    {
        $this->executed_at = $executed_at;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'migration' => $this->migration,
            'batch' => $this->batch,
            'executed_at' => $this->executed_at,
        ];
    }
}
