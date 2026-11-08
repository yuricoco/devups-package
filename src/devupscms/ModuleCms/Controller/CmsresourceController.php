<?php


use dclass\devups\Controller\Controller;

class CmsresourceController extends Controller
{

    public function listView($next = 1, $per_page = 10)
    {

        $this->datatable = CmsresourceTable::init(new Cmsresource())->buildindextable();

        self::$jsfiles[] = Cmsresource::classpath('Resource/js/cmsresourceCtrl.js');

        $this->entitytarget = 'Cmsresource';
        $this->title = "Manage Cmsresource";

        $this->renderListView();

    }

    public function datatable($next, $per_page)
    {

        return ['success' => true,
            'datatable' => CmsresourceTable::init(new Cmsresource())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $cmsresource = new Cmsresource();
        $action = __env . ("admin/api/cmsresource/create");
        if ($id) {
            $action = __env . ("admin/api/cmsresource/update?id=" . $id);
            $cmsresource = Cmsresource::find($id);
        }

        return ['success' => true,
            'form' => CmsresourceForm::init($cmsresource, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($cmsresource_form = null)
    {
        extract($_POST);

        $cmsresource = $this->form_fillingentity(new Cmsresource(), $cmsresource_form);
        if ($this->error) {
            return array('success' => false,
                'action' => 'create',
                'error' => $this->error);
        }

        $cmsresource->uploadImage();
        $id = $cmsresource->__insert();
        return array('success' => true,
            'cmsresource' => $cmsresource,
            'tablerow' => CmsresourceTable::init()->buildindextable()->getSingleRowRest($cmsresource),
            'detail' => '');

    }

    public function updateAction($id, $cmsresource_form = null)
    {
        extract($_POST);

        $cmsresource = $this->form_fillingentity(new Cmsresource($id), $cmsresource_form);

        if ($this->error) {
            return array('success' => false,
                'cmsresource' => $cmsresource,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $cmsresource->__update();
        return array('success' => true,
            'cmsresource' => $cmsresource,
            'tablerow' => CmsresourceTable::init()->buildindextable()->getSingleRowRest($cmsresource),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Cmsresource';
        $this->title = "Detail Cmsresource";

        $cmsresource = Cmsresource::find($id);

        $this->renderDetailView(
            CmsresourceTable::init()
                ->builddetailtable()
                ->renderentitydata($cmsresource)
        );

    }

    public function deleteAction($id)
    {

        $cr = Cmsresource::find($id);
        $cr->__delete();

        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Cmsresource::where("id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

}
