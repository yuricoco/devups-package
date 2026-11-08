<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class Dvups_privilegeTable extends Datatable{
    

    public function __construct($dvups_privilege = null, $datatablemodel = [])
    {
        parent::__construct($dvups_privilege, $datatablemodel);
    }

    public static function init(\Dvups_privilege $dvups_privilege = null){
    
        $dt = new Dvups_privilegeTable($dvups_privilege);
        $dt->entity = $dvups_privilege;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'dvups_role.name' => ['header' => t('Dvups_role') , ], 
'dvups_action.entity' => ['header' => t('Dvups_action') , ]
];

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('dvups_role'), 'value' => 'Dvups_role.name'], 
['label' => t('dvups_action'), 'value' => 'Dvups_action.entity']
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
