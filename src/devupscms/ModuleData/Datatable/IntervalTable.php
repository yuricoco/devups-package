<?php 


use dclass\devups\Datatable\Datatable as Datatable;

class IntervalTable extends Datatable{
    

    public function __construct($interval = null, $datatablemodel = [])
    {
        parent::__construct($interval, $datatablemodel);
    }

    public static function init(\Interval $interval = null){
    
        $dt = new IntervalTable($interval);
        $dt->entity = $interval;
        
        return $dt;
    }

    public function buildindextable(){

        $this->base_url = __env."admin/";
        $action = Interval::classview('interval/config');
        $this->topactions[] = '<a href="'.$action.'" class="btn btn-info">Config</a>';

        $this->datatablemodel = [
'id' => ['header' => t('#'),], 
'_min' => ['header' => t('_min'),], 
'_max' => ['header' => t('_max'),], 
'label' => ['header' => t('Label'),], 
'_value' => ['header' => t('_value'),]
];

        return $this;
    }

    public function buildconfigtable()
    {

        $this->base_url = __env . "admin/";

        $this->enabletopaction = false;
        $this->groupaction = false;
        // $this->isRadio = true;
        //$this->dynamicpagination = true;
        //$this->searchaction = false;
        $idgroup = Request::get('groupid');
        if ($idgroup)
            $this->qbcustom
                ->addColumns(" (SELECT COUNT(*) FROM interval_group WHERE interval_id = this.id AND barem_id = $idgroup) AS in_group ");

        $this->disableDefaultaction();
        $this->actionDropdown = false;
        // $this->
        $this->datatablemodel = [
            '_min' => ['header' => t('_min'),],
            '_max' => ['header' => t('_max'),],
            'label' => ['header' => t('Label'),],
            '_value' => ['header' => t('_value'),],
            'id' => ['header' => t('#'),'value'=>function(Interval $item){
                if ($item->in_group)
                    return '<input checked type="checkbox" onclick="model.toggleFundGroup(this, '.$item->id.')" />';
                return '<input type="checkbox" onclick="model.toggleFundGroup(this, '.$item->id.')" />';
            }],
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('_min'), 'value' => '_min'], 
['label' => t('_max'), 'value' => '_max'], 
['label' => t('label'), 'value' => 'label'], 
['label' => t('_value'), 'value' => '_value']
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
