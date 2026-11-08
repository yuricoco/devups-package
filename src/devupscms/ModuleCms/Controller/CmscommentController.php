<?php


use dclass\devups\Controller\Controller;

class CmscommentController extends Controller
{

    public function listView()
    {

        $this->datatable = CmscommentTable::init(new Cmscomment())->buildindextable();

        $this->entitytarget = 'Cmscomment';
        $this->title = "Manage Cmscomment";

        $this->renderListView();

    }

    public function datatable()
    {

        return ['success' => true,
            'datatable' => CmscommentTable::init(new Cmscomment())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $cmscomment = new Cmscomment();
        $action = __env . ("admin/api/cmscomment/create");
        if ($id) {
            $action = __env . ("admin/api/cmscomment/update?id=" . $id);
            $cmscomment = Cmscomment::find($id);
        }

        return ['success' => true,
            'form' => CmscommentForm::init($cmscomment, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($cmscomment_form = null)
    {
        extract($_POST);

        $cmscomment = $this->form_fillingentity(new Cmscomment(), $cmscomment_form);
        if ($this->error) {
            return array('success' => false,
                'cmscomment' => $cmscomment,
                'action' => 'create',
                'error' => $this->error);
        }

        $id = $cmscomment->__insert();
        return array('success' => true,
            'cmscomment' => $cmscomment,
            'tablerow' => CmscommentTable::init(new Cmscomment())->router()->getSingleRowRest(Cmscomment::find($id, 1)),
            'detail' => '');

    }

    public function updateAction($id, $cmscomment_form = null)
    {
        extract($_POST);

        $cmscomment = $this->form_fillingentity(new Cmscomment($id), $cmscomment_form);

        if ($this->error) {
            return array('success' => false,
                'cmscomment' => $cmscomment,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $cmscomment->__update();
        return array('success' => true,
            'cmscomment' => $cmscomment,
            'tablerow' => CmscommentTable::init(new Cmscomment())->router()->getSingleRowRest(Cmscomment::find($id, 1)),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Cmscomment';
        $this->title = "Detail Cmscomment";

        $cmscomment = Cmscomment::find($id);

        $this->renderDetailView(
            CmscommentTable::init()
                ->builddetailtable()
                ->renderentitydata($cmscomment)
        );

    }

    public function deleteAction($id)
    {

        Cmscomment::delete($id);

        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Cmscomment::where("id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

}
