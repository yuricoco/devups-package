<?php


use dclass\devups\Datatable\Datatable as Datatable;

class EmaillogTable extends Datatable
{


    public function __construct($emaillog = null, $datatablemodel = [])
    {
        parent::__construct($emaillog, $datatablemodel);
    }

    public static function init(\Emaillog $emaillog = null)
    {

        $dt = new EmaillogTable($emaillog);
        $dt->entity = $emaillog;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env."admin/";
        $this->order_by =" this.id desc ";
        $this->datatablemodel = [
            ['header' => t('#'), 'value' => 'id'],
            ['header' => t('Date'), 'value' => 'createdAt'],
            ['header' => t('Statut'), 'value' => 'statut'],
            ['header' => t('Destinataire'), 'value' => 'destinataire'],
            ['header' => t('Sujet'), 'value' => 'sujet'],
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('Date'), 'value' => 'createdAt'],
            ['label' => t('Statut'), 'value' => 'statut'],
            ['label' => t('Destinataire'), 'value' => 'destinataire'],
            ['label' => t('Sujet'), 'value' => 'sujet'],
            ['label' => t('Modele'), 'value' => 'modele'],
            ['label' => t('Erreur'), 'value' => 'erreur'],
        ];
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
