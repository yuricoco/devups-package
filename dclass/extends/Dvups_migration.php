<?php

/**
 * @Entity @Table(name="dvups_migration")
 * Trace des fichiers de database/migration deja joues par
 * `php devups migrate` (MigrationRunner) - jamais modifiee a la main.
 */
class Dvups_migration extends Model implements JsonSerializable
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="migration", type="string", length=255)
     * @var string
     */
    protected $migration;

    /**
     * @Column(name="batch", type="integer")
     * @var int
     */
    protected $batch;

    /**
     * @Column(name="executed_at", type="datetime")
     * @var datetime
     */
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
