<?php


use dclass\devups\Controller\Controller;

class Push_emailController extends Controller
{

    public function listView()
    {

        // $this->datatable = Push_emailTable::init(new Push_email())->buildindextable();

        self::$jsfiles[] = Push_email::classpath('Resource/js/push_emailCtrl.js');

        $this->entitytarget = 'Push_email';
        $this->title = "Manage Push_email";

        Genesis::renderView('admin.push_mail');

    }

    public function datatable()
    {

        return ['success' => true,
            'datatable' => Push_emailTable::init(new Push_email())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $push_email = new Push_email();
        $action = __env . ("admin/api/push-email.create");
        if ($id) {
            $action = __env . ("admin/api/push-email.update?id=" . $id);
            $push_email = Push_email::find($id);
        }

        return ['success' => true,
            'form' => Push_emailForm::init($push_email, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($push_email_form = null)
    {
        extract($_POST);

        $push_email = $this->form_fillingentity(new Push_email(), $push_email_form);
        if ($this->error) {
            return array('success' => false,
                'push_email' => $push_email,
                'action' => 'create',
                'error' => $this->error);
        }


        $id = $push_email->__insert();
        return array('success' => true,
            'push_email' => $push_email,
            'tablerow' => Push_emailTable::init()->router()->getSingleRowRest($push_email),
            'detail' => '');

    }

    public function updateAction($id, $push_email_form = null)
    {
        extract($_POST);

        $push_email = $this->form_fillingentity(new Push_email($id), $push_email_form);

        if ($this->error) {
            return array('success' => false,
                'push_email' => $push_email,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $push_email->__update();
        return array('success' => true,
            'push_email' => $push_email,
            'tablerow' => Push_emailTable::init()->router()->getSingleRowRest($push_email),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Push_email';
        $this->title = "Detail Push_email";

        $push_email = Push_email::find($id);

        $this->renderDetailView(
            Push_emailTable::init()
                ->builddetailtable()
                ->renderentitydata($push_email)
        );

    }

    public function deleteAction($id)
    {

        Push_email::delete($id);

        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Push_email::where("id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

    public function sendnotificationAction($id)
    {

        $push_email = Push_email::find($id);
        return $this->sendnotification($push_email);

    }

    public function sendnotification(Push_email $push_email)
    {

        if ($id_user = Request::get('user_id')) {
            $user = User::find($id_user);

            $rm = Reportingmodel::getbyattribut("emailmodel", $push_email->notificationtype->emailmodel, Dvups_lang::getByIsoCode($user->lang)->id);
            //$rm = Reportingmodel::find($push_email->reportingmodel_id, Dvups_lang::getByIsoCode($user->lang));
            $rm->addReceiver($user->getEmail(), $user->getUsername());
            $rm->sendMail($user->notificationData());
        } else {
            $langs = Dvups_lang::all();
            foreach ($langs as $lang) {
                $users = User::select()->where_str($push_email->constraint)->where("lang", $lang->iso_code)->get();

                if (!count($users))
                    continue;


                //$tr->smsmodel = Smsmodel::first();
//        if ($push_email->push == 'global'){
//            $entity = new $push_email->_notificationtype->dvups_entity->name;
//        }elseif ($push_email->push == 'specific'){
                $repm = null;
                if ($push_email->notificationtype->emailmodel)
                    $repm = Reportingmodel::getbyattribut("this.name", $push_email->notificationtype->emailmodel, $lang->id);
                // $rm = Reportingmodel::find($push_email->reportingmodel_id, $lang->id);
                foreach ($users as $i => $user) {

                    //if ($push_email->reportingmodel_id && $user->email)
                    if (is_object($repm) && $user->email) {
                        $rm = clone $repm;
                        Reportingmodel::$emailreceiver = [];
                        $rm->addReceiver($user->getEmail(), $user->getUsername());
                        $rm->sendMail($user->notificationData());
                    }

                    //if ($push_email->notificationtype_id)
                    Notification::on($user, $push_email->notificationtype->_key)
                        ->send($user, $user->notificationData());

                    if ($i == 1)
                        break;
                }
                //if ($push_email->notificationtype->emailmodel)

                return $users;
//        }
            }
        }

        $push_email->last_call = date("Y-m-d");
        $push_email->next_call = date("Y-m-d H:i:s", strtotime("+" . $push_email->interval . " days", strtotime($push_email->last_call)));
        $push_email->__update();

        return [
            "success" => true,
            "detail" => "message envoye",
        ];

    }

    public function cronjobAction()
    {
        $reference = Request::get('reference');
        $date = date("Y-m-d");
        $response = [];
        if ($reference) {
            $push_email = Push_email::getbyattribut("reference", $reference);
            if ($push_email->id) {
                //  save log
                return [
                    "success" => false
                ];
            }
            if ($push_email->next_call > $date) {

                //  save log
                return [
                    "detail" => "the next call scheduled for the :" . $push_email->next_call,
                    "success" => false
                ];
            }
            $response = $this->sendnotification($push_email);
        } else {

            $trs = Push_email::where("next_call", "<=", $date)
                ->where("this.status", 1)
                ->whereNotNull("this.notificationtype_id")
//                ->where_str("  this.date_start = '" . $date . "' AND this.date_end IS NULL ", "AND")
                ->limit(200)
                ->get();

            foreach ($trs as $tr) {

                $response[] = $this->sendnotification($tr);

            }

        }
//        $index = (count($trs) < $perpage) ? 1: $index+1;
//        Dv_iterator::updateIndex("cronjob", $index);

        return [
            "detail" => "push completed",
            "response" => $response,
            "success" => true
        ];
    }

    /**
     * this method is call from a cron.
     * it changes the status of a push mail base on an interval availability
     * @return void
     */
    public function togglePushStatus()
    {
        $date = date("Y-m-d");
        $hasitem = Push_email::where("this.status", 1)
            ->where_str("  this.date_start = '" . $date . "' AND this.date_end IS NULL ", "AND")->count();

        if ($hasitem)
            Push_email::where("this.status", 1)
                ->where_str("  this.date_start = '" . $date . "' AND this.date_end IS NULL ", "AND")
                ->update([
                    "this.status" => 0
                ]);
    }

}
