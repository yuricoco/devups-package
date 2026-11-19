<?php

namespace devupscms\ModuleTree\Datatable;

use dclass\devups\Datatable\Datatable as Datatable;

class TreeTable extends Datatable{
    

    public function __construct($lazyloading = null, $datatablemodel = [])
    {
        parent::__construct($lazyloading, $datatablemodel);
    }

    public static function init(\Tree $tree = null){

        // ETAPE V : meme bug que CountryTable avant son correctif - passer
        // "new TreeTable()" SANS entite laisse Datatable::__construct()
        // sans $this->class ($this->qbcustom non plus), donc le bouton
        // "Creer" genere "onclick=...('')" -> GET /admin/api/null/form.
        $dt = new TreeTable($tree ?: new \Tree());
        $dt->entity = $tree;

        return $dt;
    }

    public function buildindextable(){

        $this->datatablemodel = [
['header' => t('tree.id', '#'), 'value' => 'id'], 
['header' => t('tree.name', 'Name'), 'value' => 'name'], 
['header' => t('tree.description', 'Description'), 'value' => 'description']
];

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('tree.name'), 'value' => 'name'], 
['label' => t('tree.description'), 'value' => 'description']
];
        // TODO: overwrite datatable attribute for this view
        return $this;
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(TreeTable::class, 'TreeTable');
