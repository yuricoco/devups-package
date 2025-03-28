<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class BlacklistTable extends Datatable{
    

    public function __construct($blacklist = null, $datatablemodel = [])
    {
        parent::__construct($blacklist, $datatablemodel);
    }

    public static function init(\compagnons\ModuleMember\Entity\Blacklist $blacklist = null){
    
        $dt = new \compagnons\ModuleMember\Datatable\BlacklistTable($blacklist);
        $dt->entity = $blacklist;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'comment' => ['header' => t('Comment'),], 
'subject' => ['header' => t('Subject'),], 
'user.firstname' => ['header' => t('User') , ]
];

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('comment'), 'value' => 'comment'], 
['label' => t('subject'), 'value' => 'subject'], 
['label' => t('user'), 'value' => 'User.firstname']
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
