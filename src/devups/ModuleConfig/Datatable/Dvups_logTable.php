<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class Dvups_logTable extends Datatable{
    

    public function __construct($dvups_log = null, $datatablemodel = [])
    {
        parent::__construct($dvups_log, $datatablemodel);
    }

    public static function init(\Dvups_log $dvups_log = null){
    
        $dt = new Dvups_logTable($dvups_log);
        $dt->entity = $dvups_log;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'object' => ['header' => t('Object'),], 
'log' => ['header' => t('Log'),]
];

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('object'), 'value' => 'object'], 
['label' => t('log'), 'value' => 'log']
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
