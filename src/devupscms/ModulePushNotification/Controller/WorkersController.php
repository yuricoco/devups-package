<?php


use dclass\devups\Controller\Controller;

class WorkersController extends Controller
{

    public function listView()
    {

        $this->datatable = WorkersTable::init(new Workers())->buildindextable();

        self::$jsfiles[] = Workers::classpath('Resource/js/workersCtrl.js');

        $this->entitytarget = 'Workers';
        $this->title = "Manage Workers";

        $this->renderListView();

    }

    public function datatable()
    {

        return ['success' => true,
            'datatable' => WorkersTable::init(new Workers())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $workers = new Workers();
        $action = __env . ("admin/api/workers/create");
        if ($id) {
            $action = __env . ("admin/api/workers/update?id=" . $id);
            $workers = Workers::find($id);
        }

        return ['success' => true,
            'form' => WorkersForm::init($workers, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($workers_form = null)
    {
        extract($_POST);

        $workers = $this->form_fillingentity(new Workers(), $workers_form);
        if ($this->error) {
            return array('success' => false,
                'workers' => $workers,
                'action' => 'create',
                'error' => $this->error);
        }


        $id = $workers->__insert();
        return array('success' => true,
            'workers' => $workers,
            'tablerow' => WorkersTable::init()->router()->getSingleRowRest($workers),
            'detail' => '');

    }

    public function updateAction($id, $workers_form = null)
    {
        extract($_POST);

        $workers = $this->form_fillingentity(new Workers($id), $workers_form);

        if ($this->error) {
            return array('success' => false,
                'workers' => $workers,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $workers->__update();
        return array('success' => true,
            'workers' => $workers,
            'tablerow' => WorkersTable::init()->router()->getSingleRowRest($workers),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Workers';
        $this->title = "Detail Workers";

        $workers = Workers::find($id);

        $this->renderDetailView(
            WorkersTable::init()
                ->builddetailtable()
                ->renderentitydata($workers)
        );

    }

    public function deleteAction($id)
    {

        $workers = Workers::find($id);
        $workers->__delete();

        return array('success' => true,
            'detail' => t('Item deleted successfully'));

    }


    public function deletegroupAction($ids)
    {

        Workers::where("this.id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

    public function testPushNotif($id)
    {

        if (Workers::initQb()
            ->whereId($id)
            ->where("this.type", 'push_notification')->count())
            Push_subscription::initPusher();

        Workers::initQb()
            ->whereId($id)
//            ->where("this.type", 'push_notification')
            ->cursor(function (\Workers $worker) {
                if ($worker->type === 'push_notification') {
                    $pusher = Push_subscription::find($worker->queue);
                    $payload = json_decode($worker->payload, true);
                    $result = $pusher->fcmPushNotification($payload['message'], $payload['data']);
                }

//                if ($result)
//                else
//                    $worker->__update(['log' => $result]);

            });

    }

    public function work()
    {

        if (Workers::initQb()
            ->where("this.type", 'push_notification')->count())
            Push_subscription::initPusher();

        Workers::initQb()
//            ->whereNull("this.log")
//            ->where("this.type", 'push_notification')
            ->limit(300)
            ->cursor(function (\Workers $worker) {
                if ($worker->type === 'push_notification') {
                    $pusher = Push_subscription::find($worker->queue);
                    $payload = json_decode($worker->payload, true);
                    $result = $pusher->fcmPushNotification($payload['message'], $payload['data']);
                }

                $worker->__delete();

            });

    }

}
