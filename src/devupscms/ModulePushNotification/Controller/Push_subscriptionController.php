<?php


use dclass\devups\Controller\Controller;
use Minishlink\WebPush\VAPID;

class Push_subscriptionController extends Controller
{

    public function listView()
    {

        $this->datatable = Push_subscriptionTable::init(new Push_subscription())->buildindextable();

        self::$jsfiles[] = Push_subscription::classpath('Resource/js/push_subscriptionCtrl.js');

        $this->entitytarget = 'Push_subscription';
        $this->title = "Manage Push_subscription";

        $this->renderListView();
    }

    public function datatable()
    {

        return [
            'success' => true,
            'datatable' => Push_subscriptionTable::init(new Push_subscription())->router()->getTableRest(),
        ];
    }

    public function formView($id = null)
    {
        $push_subscription = new Push_subscription();
        $action = __env . ("admin/api/push_subscription/create");
        if ($id) {
            $action = __env . ("admin/api/push_subscription/update?id=" . $id);
            $push_subscription = Push_subscription::find($id);
        }

        return ['success' => true,
            'form' => Push_subscriptionForm::init($push_subscription, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($push_subscription_form = null)
    {
        extract($_POST);

        $push_subscription = $this->form_fillingentity(new Push_subscription(), $push_subscription_form);
        if ($this->error) {
            return array(
                'success' => false,
                'push_subscription' => $push_subscription,
                'action' => 'create',
                'error' => $this->error
            );
        }


        $id = $push_subscription->__insert();
        return array(
            'success' => true,
            'push_subscription' => $push_subscription,
            'tablerow' => Push_subscriptionTable::init()->router()->getSingleRowRest($push_subscription),
            'detail' => ''
        );
    }

    public function updateAction($id, $push_subscription_form = null)
    {
        extract($_POST);

        $push_subscription = $this->form_fillingentity(new Push_subscription($id), $push_subscription_form);

        if ($this->error) {
            return array(
                'success' => false,
                'push_subscription' => $push_subscription,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error
            );
        }

        $push_subscription->__update();
        return array(
            'success' => true,
            'push_subscription' => $push_subscription,
            'tablerow' => Push_subscriptionTable::init()->router()->getSingleRowRest($push_subscription),
            'detail' => ''
        );
    }


    public function deleteAction($id)
    {

        Push_subscription::where('this.id', $id)->delete(true);

        return array(
            'success' => true,
            'detail' => ''
        );
    }


    public function deletegroupAction($ids)
    {

        Push_subscription::where("id")->in($ids)->delete();

        return array(
            'success' => true,
            'detail' => ''
        );
    }

    /**
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function generateKey()
    {
        $data = VAPID::createVapidKeys();

        Configuration::set('VAPID_PUBLIC_KEY', $data['publicKey']);
        Configuration::set('VAPID_PRIVATE_KEY', $data['privateKey']);
        return [
            'key' => $data['publicKey'],
        ];
    }

    /**
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function key()
    {
        return [
            'key' => Configuration::get('VAPID_PUBLIC_KEY'),
            //'key'=>'BIbPxqOqlFtjC4vHDXWWMRDtXEAg2_llqsYj2uprShJBbbIXsPksaKjAojNJzTyezeMeP3xaFDu6o_G5QVZAmvw'
        ];
    }

}
