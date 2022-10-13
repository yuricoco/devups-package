<?php


use dclass\devups\Datatable\Datatable as Datatable;

class Push_emailTable extends Datatable
{


    public function __construct($push_email = null, $datatablemodel = [])
    {
        parent::__construct($push_email, $datatablemodel);
    }

    public static function init(\Push_email $push_email = null)
    {

        $dt = new Push_emailTable($push_email);
        $dt->entity = $push_email;

        return $dt;
    }

    public function buildindextable()
    {

        $this->groupaction = false;
        $this->base_url = __env . "admin/";
        $this->datatablemodel = [
            //'id' => ['header' => t('#'),'search'=>false],
            'status' => ['header' => t('Status'),],
            'last_call' => ['header' => t('Dernier appel'),],
            'interval' => ['header' => t('Interval'),],
            'description' => ['header' => t('Description'),],
            'notificationtype._key' => ['header' => t('Notfication type'),]
        ];

        $this->actionDropdown = false;
        $this->addcustomaction(function ($item){
                return "<button class='btn btn-primary btn-block' onclick='model.sendmail(this, ".$item->getId().")'>Envoyer les mails</button>";
        });
        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('date_end'), 'value' => 'date_end'],
            ['label' => t('interval'), 'value' => 'interval'],
            ['label' => t('constraint'), 'value' => 'constraint'],
            ['label' => t('reportingmodel'), 'value' => 'Reportingmodel.name']
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
