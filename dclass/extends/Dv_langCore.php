<?php

namespace dclass\devups\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Colonnes heritees par les entites qui etendent cette classe.
 */
#[ORM\MappedSuperclass]
class Dv_langCore extends \stdClass
{

    use \ModelTrait;

    /**
     * @var \Dvups_lang
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "\Dvups_lang")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $lang;

    public static function langvalues($entityname, $id, $key){
        $sql = "SELECT $key FROM {$entityname}_lang WHERE {$entityname}_id = $id ";
        return (new \DBAL())->executeDbal($sql, [], \DBAL::$NOTHING);
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dv_langCore::class, 'Dv_langCore');