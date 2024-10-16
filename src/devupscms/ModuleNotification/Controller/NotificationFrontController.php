<?php

/*
namespace devupscms\ModuleNotification\Controller;

use dclass\devups\Controller\Controller;
use Notification;
use NotificationForm;
use NotificationTable;
use Request;*/

class NotificationFrontController extends NotificationController
{

    /**
     * @Auth(authorized=1)
     * @return array
     */
    public function notified()
    {

        return parent::notified();

    }

    public function alert( )
    {

        $notifications = Notification::where("this.created_at", ">", Request::get("date", date('Y-m-d')))
            ->where("user_id", Auth::$user_id)
            ->where("ping", 1)
            ->get();
        $unreaded = Notification::where("user_id", Auth::$user_id)
            ->where("status", "=", 0)
            ->count();

        if (count($notifications))
            Notification::where("user_id", Auth::$user_id)
                //->where("ping", 1)
                ->update([
                    "ping" => 0
                ]);

        return array('success' => true,
            'notifications' => $notifications,
            'unreaded' => $unreaded,
            'detail' => '');

    }

}
