<?php

/*
namespace devupscms\ModuleNotification\Controller;

use dclass\devups\Controller\Controller;
use Notification;
use NotificationForm;
use NotificationTable;
use Request;*/

/**
 * @Api(name='/notification')
 */
class NotificationFrontController extends \dclass\devups\Controller\FrontController
{

    /**
     * @GET
     */
    public function index()
    {
        $qb = Notification::initQb();

        return $qb->lazyloading(function (\dclass\devups\Datatable\Lazyloading $ll) {
            $ll->append('nbunread', Notification::where("user_id", Auth::$user_id)
                ->where("status", "<", 1)
                ->count());
        });

    }

    /**
     * @Auth(authorized=1)
     * @PUT(path='/notified')
     * @return array
     */
    public function notified()
    {

//        $ids = Request::post("ids");
        return Notification::where("user_id", Auth::$user_id)
            ->where('this.status', "=", -1)
//        where("this.id")
//            ->in($ids)
            ->update([
                "status" => '0',
                "viewedat" => date('Y-m-d H:i:s'),
            ]);

    }

    /**
     * @GET(path='/alert')
     */
    public function alert()
    {

        $notifications = Notification::where("user_id", Auth::$user_id)
            ->where("status", -1)
            ->orderBy('this.created_at', 'desc')
            ->get();
        $unreaded = Notification::where("user_id", Auth::$user_id)
            ->where("status", "=", 0)
            ->count();

        /*if (count($notifications))
            Notification::where("user_id", Auth::$user_id)
                //->where("ping", 1)
                ->update([
                    "ping" => 0
                ]);*/

        return array('success' => true,
            'notifications' => $notifications,
            'unreaded' => $unreaded,
            'detail' => '');

    }

}
