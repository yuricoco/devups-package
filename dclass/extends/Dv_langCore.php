<?php


/**
 * @MappedSuperclass
 * Colonnes heritees par les entites qui etendent cette classe.
 */
class Dv_langCore extends \stdClass
{

    use ModelTrait;

    /**
     * @Id @ManyToOne(targetEntity="\Dvups_lang")
     * @JoinColumn(onDelete="cascade")
     * @var \Dvups_lang
     */
    public $lang;

    public static function langvalues($entityname, $id, $key){
        $sql = "SELECT $key FROM {$entityname}_lang WHERE {$entityname}_id = $id ";
        return (new DBAL())->executeDbal($sql, [], DBAL::$NOTHING);
    }

}