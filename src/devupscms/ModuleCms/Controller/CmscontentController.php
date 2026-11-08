<?php 

            
use dclass\devups\Controller\Controller;

class CmscontentController extends Controller{

    public function listView(){

        $this->datatable = CmscontentTable::init(new Cmscontent())->buildindextable();

        self::$jsfiles[] = Cmscontent::classpath('Resource/js/cmscontentCtrl.js');

        $this->entitytarget = 'Cmscontent';
        $this->title = "Manage Cmscontent";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => CmscontentTable::init(new Cmscontent())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $cmscontent = new Cmscontent();
        $action = __env . ("admin/api/cmscontent/create");
        if ($id) {
            $action = __env . ("admin/api/cmscontent/update?id=" . $id);
            $cmscontent = Cmscontent::find($id);
        }

        return ['success' => true,
            'form' => CmscontentForm::init($cmscontent, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }
    public function edit($id = null)
    {
        $cmscontent = new Cmscontent();
        $action = __env.("admin/api/cmscontent/create");
        if ($id) {
            $action = __env.("admin/api/cmscontent/update?id=" . $id);
            $cmscontent = Cmscontent::find($id);
        }
        $langs = Dvups_lang::all();
        Genesis::renderView("cmscontent.form", compact("langs","cmscontent", "action"));
    }

    public function createAction($cmscontent_form = null ){
        extract($_POST);

        $cmscontent = $this->form_fillingentity(new Cmscontent(), $cmscontent_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'cmscontent' => $cmscontent,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $cmscontent->__insert();
        return 	array(	'success' => true,
                        'cmscontent' => $cmscontent,
                        'tablerow' => CmscontentTable::init(new Cmscontent())->router()->getSingleRowRest(Cmscontent::find($id, 1)),
                        'detail' => '');

    }

    public function newContent(){

        $ti = Tree_item::find(Request::get("tree_item"));

        $cmscontent = new Cmscontent();
        $cmscontent->tree_item = $ti;
        $cmscontent->title = $ti->name;
        $cmscontent->reference = $ti->slug;

        $id = $cmscontent->__insert();

        redirect(Cmscontent::classpath("cmscontent/edit?id=".$id));

    }

    public function updateAction($id, $cmscontent_form = null){
        extract($_POST);
            
        $cmscontent = $this->form_fillingentity(new Cmscontent($id), $cmscontent_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'cmscontent' => $cmscontent,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $cmscontent->__update();
        return 	array(	'success' => true,
                        'cmscontent' => $cmscontent,
                        'tablerow' => CmscontentTable::init(new Cmscontent())->router()->getSingleRowRest(Cmscontent::find($id, 1)),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Cmscontent';
        $this->title = "Detail Cmscontent";

        $cmscontent = Cmscontent::find($id);

        $this->renderDetailView(
            CmscontentTable::init()
                ->builddetailtable()
                ->renderentitydata($cmscontent)
        );

    }
    
    public function deleteAction($id){
    
        Cmscontent::delete($id);
        
        return 	array(	'success' => true, 
                        'detail' => ''); 
    }
    

    public function deletegroupAction($ids)
    {

        Cmscontent::where("id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
