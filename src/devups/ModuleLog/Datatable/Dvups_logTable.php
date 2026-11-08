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
'level' => ['header' => t('Level'),],
'entity_class' => ['header' => t('Entity_class'),],
'file' => ['header' => t('File'),],
'line' => ['header' => t('Line'),],
'message' => ['header' => t('Message'),],
'occurrences' => ['header' => t('Occurrences'),],
'first_at' => ['header' => t('First_at'),],
'last_at' => ['header' => t('Last_at'),],
];

        // Filtres back-office (lecture seule) : level exact, plage de dates
        // sur last_at, recherche libre sur entity_class/file/message (sert
        // aussi de filtre "par module" via le chemin du fichier source).
        $qb = Dvups_log::select();
        if ($level = \Request::get('level'))
            $qb = $qb->where('this.level', $level);
        if ($from = \Request::get('from'))
            $qb = $qb->andwhere('this.last_at', '>=', $from . ' 00:00:00');
        if ($to = \Request::get('to'))
            $qb = $qb->andwhere('this.last_at', '<=', $to . ' 23:59:59');
        if ($q = \Request::get('q')) {
            $like = '%' . $q . '%';
            $qb = $qb->andwhere(function ($w) use ($like) {
                $w->where('this.entity_class', 'like', $like)
                    ->orwhere('this.file', 'like', $like)
                    ->orwhere('this.message', 'like', $like);
            });
        }
        $this->qbcustom = $qb;
        $this->order_by = 'dvups_log.last_at desc';

        return $this;
    }
    
    public function builddetailtable()
    {
        $this->datatablemodel = [
['label' => t('fingerprint'), 'value' => 'fingerprint'], 
['label' => t('level'), 'value' => 'level'], 
['label' => t('entity_class'), 'value' => 'entity_class'], 
['label' => t('file'), 'value' => 'file'], 
['label' => t('line'), 'value' => 'line'], 
['label' => t('message'), 'value' => 'message'], 
['label' => t('occurrences'), 'value' => 'occurrences'], 
['label' => t('first_at'), 'value' => 'first_at'], 
['label' => t('last_at'), 'value' => 'last_at'], 
['label' => t('context'), 'value' => 'context'], 
['label' => t('log_file'), 'value' => 'log_file']
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
