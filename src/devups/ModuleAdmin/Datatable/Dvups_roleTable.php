<?php


use dclass\devups\Datatable\Datatable as Datatable;

class Dvups_roleTable extends Datatable
{


    public function __construct($dvups_role = null, $datatablemodel = [])
    {
        parent::__construct($dvups_role, $datatablemodel);
    }

    public static function init(\Dvups_role $dvups_role = null)
    {

        $dt = new Dvups_roleTable($dvups_role);
        $dt->entity = $dvups_role;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'name' => ['header' => t('Name'),],
            'alias' => ['header' => t('Alias'),]
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('name'), 'value' => 'name'],
            ['label' => t('alias'), 'value' => 'alias']
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
