<?php


use dclass\devups\Datatable\Datatable as Datatable;

class Push_subscriptionTable extends Datatable
{


    public function __construct($push_subscription = null, $datatablemodel = [])
    {
        parent::__construct($push_subscription, $datatablemodel);
    }

    public static function init(\Push_subscription $push_subscription = null)
    {

        $dt = new Push_subscriptionTable($push_subscription);
        $dt->entity = $push_subscription;

        return $dt;
    }

    public function buildindextable()
    {

        $this->groupaction = false;
        $this->order_by = 'this.id desc';
        $this->base_url = __env . "admin/";
        $this->datatablemodel = [
            'id' => ['header' => t('#'),],
// 'subscription_type' => ['header' => t('Subscription_type'),],
            'user_id' => ['header' => t('Subscription_id'),],
            'status' => ['header' => t('Status'),],
//'public_key' => ['header' => t('Public_key'),],
            'auth_token' => ['header' => t('Auth_token'),],
//'content_type' => ['header' => t('Content_type'),]
        ];

        $this->addcustomaction(function ($item) {
            return "<button class='btn btn-info btn-block' type='button' onclick='testPush($item->id,this)' >Test</button>";
        });
        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('subscription_type'), 'value' => 'subscription_type'],
            ['label' => t('subscription_id'), 'value' => 'subscription_id'],
            ['label' => t('endpoint'), 'value' => 'endpoint'],
            ['label' => t('public_key'), 'value' => 'public_key'],
            ['label' => t('auth_token'), 'value' => 'auth_token'],
            ['label' => t('content_type'), 'value' => 'content_type']
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
