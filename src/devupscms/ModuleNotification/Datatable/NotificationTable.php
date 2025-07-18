<?php


use dclass\devups\Datatable\Datatable as Datatable;

class NotificationTable extends Datatable
{


    public function __construct($notification = null, $datatablemodel = [])
    {
        parent::__construct($notification, $datatablemodel);
    }

    public static function init(\Notification $notification = null)
    {

        $dt = new NotificationTable($notification);
        $dt->entity = $notification;

        return $dt;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        $this->order_by = "this.id desc";
        $this->qbcustom->with('user', ['username']);
        $this->datatablemodel = [
            ['header' => t('notification.id', '#'), 'value' => 'id'],
            ['header' => "sent to", 'value' => function ($item) {
                $note = " || status : {$item->status} ";
                if ($item->user_id) {
                    return $item->user->username.$note ;
                }
                return $item->entity.$note;
            }],
            ['header' => t('Entityid'), 'value' => function ($item) {
                return $item->entity . " / " . $item->entityid;
            }],
            ['header' => t('notification.content', 'Content'), 'value' => 'content']
        ];

        return $this;
    }

    public function buildconfigtable()
    {
        $this->base_url = __env . "admin/";
        $this->order_by = "this.id desc";
        $this->enabletopaction = false;
//        $this->qbcustom->with('user', ['username']);
        $this->datatablemodel = [
            //['header' => t('notification.id', '#'), 'value' => 'id'],
            ['header' => t('notification.entity', 'Entity'), 'value' => function($item){
                $note = " || user_id : {$item->user_id} / status : {$item->status} ";
                return $item->entity. " #". $item->entityid.$note;
            }],
            ['header' => t('notification.content', 'Content'), 'value' => function ($item) {
                return $item->content . '<br><small><i>' . $item->created_at . '</i></small>';
            }],
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('notification.entity'), 'value' => 'entity'],
            ['label' => t('notification.entityid'), 'value' => 'entityid'],
            ['label' => t('notification.creationdate'), 'value' => 'creationdate'],
            ['label' => t('notification.content'), 'value' => 'content']
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
