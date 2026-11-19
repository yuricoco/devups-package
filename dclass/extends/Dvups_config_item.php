<?php

namespace dclass\devups\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * Colonnes heritees par les entites qui etendent cette classe.
 */
#[ORM\MappedSuperclass]
class Dvups_config_item extends \Model implements \JsonSerializable
{
    // NB (v8.0, LOT 1.2) : redeclaration EXPLICITE (pas un simple "use" du
    // trait, insuffisant - voir TODO.md ETAPE 1.2) de created_at/updated_at/
    // deleted_at (deja fournies par DateTimeTrait via Model, cf.
    // Model::__insert()). Sans ca, Doctrine ORM 3 (AttributeDriver) leve un
    // conflit "duplicate column" pour toute entite qui etend cette
    // MappedSuperclass : la colonne est fusionnee une premiere fois via
    // l'heritage de mapped-superclass (ou elle est enregistree comme
    // "declared" par Dvups_config_item), puis retrouvee une seconde fois
    // lors du scan direct de l'entite fille - ou, du point de vue de la
    // Reflection PHP, elle reste "declared" par Model tant que la propriete
    // n'est que heritee via le trait (reutiliser le meme trait ici ne suffit
    // PAS, verifie : PHP garde alors Model comme classe declarante). Une
    // redeclaration explicite fait de Dvups_config_item la veritable classe
    // declarante (elle masque la propriete de Model), ce qui rend les deux
    // etiquettes coherentes. Aucun changement de schema : meme colonnes,
    // memes attributs, les accesseurs de DateTimeTrait restent valables
    // (ils operent sur $this->created_at par son nom, pas par la classe qui
    // le declare).
    /** @var string */
    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected $created_at;
    /** @var string */
    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    protected $updated_at;
    /** @var string */
    #[ORM\Column(name: "deleted_at", type: "datetime", nullable: true)]
    protected $deleted_at;

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
    #[ORM\Column(name: "name", type: "string", length: 50)]
    protected $name;

    public $label;


    /**
     * @var string
     */
    #[ORM\Column(name: "url", type: "string", length: 25, nullable: true)]
    protected $url;

    public function __construct($id = null)
    { 
        $this->dvtranslate = true;
        $this->dvtranslated_columns = ['label'];
        if($id)
            $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (!$this->url)
            return strtolower(str_replace('_', '-', $this->name));

        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        if (!$url)
            $url = $this->name;

        $nameseo = str_replace('_', '-', $url); // supprime les autres caractères

        $this->url = strtolower($nameseo);

    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if (!$this->label)
            return $this->name;

        return $this->label;

    }

    function getLabelform()
    {

        $view = '<div style="width: 100%;"><span> ' . $this->label . ' </span>
<span  class="btn btn-default"  onclick="editlabel(this)" data-id="' . $this->id . ' ">edit</span></div>
<div hidden ><input id="input-' . $this->id . '" style="width: 100px;" name="label" value="' . $this->label . '" />
<span class="btn btn-default" onclick="updatelabel(' . $this->id . ')">update</span>
<!--span class="btn" onclick="cancelupdate()">cancel</span-->
</div>';
        return $view;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $lang_isos = \Dvups_lang::getLangIso();
        foreach ($lang_isos as $iso)
            $this->label[$iso] = $label;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
        ];
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Dvups_config_item::class, 'Dvups_config_item');