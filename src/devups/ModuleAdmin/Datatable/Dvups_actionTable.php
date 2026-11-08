<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class Dvups_actionTable extends Datatable{
    

    public function __construct($dvups_action = null, $datatablemodel = [])
    {
        parent::__construct($dvups_action, $datatablemodel);
    }

    public static function init(\Dvups_action $dvups_action = null){
    
        $dt = new Dvups_actionTable($dvups_action);
        $dt->entity = $dvups_action;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'entity' => ['header' => t('Entity'),], 
'action' => ['header' => t('Action'),], 
'label' => ['header' => t('Label'),]
];

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('entity'), 'value' => 'entity'], 
['label' => t('action'), 'value' => 'action'], 
['label' => t('label'), 'value' => 'label']
];
        // TODO: overwrite datatable attribute for this view
        return $this;
    }

    public function router()
    {
        $tablemodel = Request::get("tablemodel", null);
        if ($tablemodel && method_exists($this, "build" . $tablemodel . "table") && $result = call_user_func(array($this, "build" . $tablemodel . "table"))) {
            return $result;
        } else
            switch ($tablemodel) {
                // case "": return this->
                default:
                    return $this->buildindextable();
            }

    }

}
