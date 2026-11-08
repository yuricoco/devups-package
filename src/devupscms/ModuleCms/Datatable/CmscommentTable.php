<?php


use dclass\devups\Datatable\Datatable as Datatable;

class CmscommentTable extends Datatable
{


    public function __construct($cmscomment = null, $datatablemodel = [])
    {
        parent::__construct($cmscomment, $datatablemodel);
    }

    public static function init(\Cmscomment $cmscomment = null)
    {
        $dt = new CmscommentTable($cmscomment);
        $dt->entity = $cmscomment;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'pseudo' => ['header' => t('pseudo'),],
            'email' => ['header' => t('email'),],
            'seen' => ['header' => t('Statut'),],
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('pseudo'), 'value' => 'pseudo'],
            ['label' => t('email'), 'value' => 'email'],
            ['label' => t('comment'), 'value' => 'comment'],
            ['label' => t('Statut'), 'value' => 'seen'],
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
                default:
                    return $this->buildindextable();
            }

    }

}
