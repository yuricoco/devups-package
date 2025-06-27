<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class WorkersTable extends Datatable{
    

    public function __construct($workers = null, $datatablemodel = [])
    {
        parent::__construct($workers, $datatablemodel);
    }

    public static function init(\Workers $workers = null){
    
        $dt = new WorkersTable($workers);
        $dt->entity = $workers;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'queue' => ['header' => t('Queue'),], 
'payload' => ['header' => t('Payload'),], 
'type' => ['header' => t('Type'),], 
'callback' => ['header' => t('Callback'),], 
'log' => ['header' => t('Log'),]
];

        $this->addcustomaction(function ($item) {
            return "<button class='btn btn-info btn-block' type='button' onclick='testPush($item->id,this)' >Test</button>";
        });
        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('queue'), 'value' => 'queue'], 
['label' => t('payload'), 'value' => 'payload'], 
['label' => t('type'), 'value' => 'type'], 
['label' => t('callback'), 'value' => 'callback'], 
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
