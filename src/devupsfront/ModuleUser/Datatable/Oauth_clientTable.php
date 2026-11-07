<?php


use dclass\devups\Datatable\Datatable as Datatable;
use devupsfront\ModuleUser\Entity\Oauth_client;

class Oauth_clientTable extends Datatable
{


    public function __construct($oauth_client = null, $datatablemodel = [])
    {
        parent::__construct($oauth_client, $datatablemodel);
    }

    public static function init(Oauth_client $oauth_client = null)
    {

        $dt = new Oauth_clientTable($oauth_client);
        $dt->entity = $oauth_client;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        $this->order_by = "id desc";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
            'client' => ['header' => t('Client'),],
            'name' => ['header' => t('Name'),],
            'user_ext_id' => ['header' => t('User_ext_id'),],
            'user_id' => ['header' => t('User_id'), 'value' => 'user_id', 'get' => function ($item) {
                if ($item->user_id)
                    return "<a href='" . User::classview('user/detail?id=' . $item->user_id) . "'>Detail user {$item->user_id}</a>";
                return '-';
            }],
            'created_at' => ['header' => t('Date'),],
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('id'), 'value' => 'id'],
            ['label' => t('client'), 'value' => 'client'],
            ['label' => t('name'), 'value' => 'name'],
            ['label' => t('user_ext_id'), 'value' => 'user_ext_id'],
            ['label' => t('user_id'), 'value' => 'user_id']
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
