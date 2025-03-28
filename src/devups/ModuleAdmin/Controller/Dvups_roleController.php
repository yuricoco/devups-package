<?php


use dclass\devups\Controller\Controller;

class Dvups_roleController extends Controller
{

    public function listView()
    {

        $role = Dvups_role::find(2);
        $role->updateConfigs();

        $this->datatable = Dvups_roleTable::init(new Dvups_role())->buildindextable();

        self::$jsfiles[] = Dvups_role::classpath('Resource/js/dvups_roleCtrl.js');

        $this->entitytarget = 'Dvups_role';
        $this->title = "Manage Dvups_role";

        $this->renderListView();

    }

    public function datatable()
    {

        return ['success' => true,
            'datatable' => Dvups_roleTable::init(new Dvups_role())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $dvups_role = new Dvups_role();
        $action = __env . ("admin/api/dvups_role/create");
        if ($id) {
            $action = __env . ("admin/api/dvups_role/update?id=" . $id);
            $dvups_role = Dvups_role::find($id);
        }

        return ['success' => true,
            'form' => Dvups_roleForm::renderWidget($dvups_role, $action),
            /*'form' => Dvups_roleForm::init($dvups_role, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),*/
        ];
    }

    public function createAction($dvups_role_form = null)
    {
        extract($_POST);

        $dvups_role = $this->form_fillingentity(new Dvups_role(), $dvups_role_form);
        if ($this->error) {
            return array('success' => false,
                'dvups_role' => $dvups_role,
                'action' => 'create',
                'error' => $this->error);
        }

        $dvups_role->updateConfigs();
        $id = $dvups_role->__insert();
        return array('success' => true,
            'dvups_role' => $dvups_role,
            'tablerow' => Dvups_roleTable::init()->router()->getSingleRowRest($dvups_role),
            'detail' => '');

    }

    public function updateAction($id, $dvups_role_form = null)
    {
        extract($_POST);

        $dvups_role = $this->form_fillingentity(new Dvups_role($id), $dvups_role_form);

        if ($this->error) {
            return array('success' => false,
                'dvups_role' => $dvups_role,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $dvups_role->updateConfigs();
        $dvups_role->__update();
        return array('success' => true,
            'dvups_role' => $dvups_role,
            'tablerow' => Dvups_roleTable::init()->router()->getSingleRowRest($dvups_role),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Dvups_role';
        $this->title = "Detail Dvups_role";

        $dvups_role = Dvups_role::find($id);

        $this->renderDetailView(
            Dvups_roleTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_role)
        );

    }

    public function deleteAction($id)
    {

        $dvups_role = Dvups_role::find($id);
        $dvups_role->__delete();

        return array('success' => true,
            'detail' => t('Item deleted successfully'));

    }


    public function deletegroupAction($ids)
    {

        Dvups_role::where("this.id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

    public function privilegeUpdate()
    {
        $result = Core::updateDvupsTable();
        /*if ($result) {
            $admin = getadmin();
            self::getNavigationAction($admin);
            $_SESSION[ADMIN] = serialize($admin);

            $message = "Data admin updated with success";
        } else*/
            $message = "Data admin already uptodate";

        return array('success' => true,
            'message' => $message,
            'detail' => '');

    }

}
