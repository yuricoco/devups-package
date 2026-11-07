<?php 


use dclass\devups\Controller\Controller;

class Dvups_privilegeController extends Controller{

    public function listView(){

        $this->datatable = Dvups_privilegeTable::init(new Dvups_privilege())->buildindextable();

        self::$jsfiles[] = Dvups_privilege::classpath('Resource/js/dvups_privilegeCtrl.js');

        $this->entitytarget = 'Dvups_privilege';
        $this->title = "Manage Dvups_privilege";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => Dvups_privilegeTable::init(new Dvups_privilege())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $dvups_privilege = new Dvups_privilege();
        $action = __env.("admin/api/dvups_privilege/create");
        if ($id) {
            $action = __env.("admin/api/dvups_privilege/update?id=" . $id);
            $dvups_privilege = Dvups_privilege::find($id);
        }

        return ['success' => true,
            'form' => Dvups_privilegeForm::init($dvups_privilege, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($dvups_privilege_form = null ){
        extract($_POST);

        $dvups_privilege = $this->form_fillingentity(new Dvups_privilege(), $dvups_privilege_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_privilege' => $dvups_privilege,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $dvups_privilege->__insert();
        return 	array(	'success' => true,
                        'dvups_privilege' => $dvups_privilege,
                        'tablerow' => Dvups_privilegeTable::init()->router()->getSingleRowRest($dvups_privilege),
                        'detail' => '');

    }

    public function updateAction($id, $dvups_privilege_form = null){
        extract($_POST);
            
        $dvups_privilege = $this->form_fillingentity(new Dvups_privilege($id), $dvups_privilege_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_privilege' => $dvups_privilege,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $dvups_privilege->__update();
        return 	array(	'success' => true,
                        'dvups_privilege' => $dvups_privilege,
                        'tablerow' => Dvups_privilegeTable::init()->router()->getSingleRowRest($dvups_privilege),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Dvups_privilege';
        $this->title = "Detail Dvups_privilege";

        $dvups_privilege = Dvups_privilege::find($id);

        $this->renderDetailView(
            Dvups_privilegeTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_privilege)
        );

    }
    
    public function deleteAction($id){
    
        $dvups_privilege = Dvups_privilege::find($id);
        $dvups_privilege->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Dvups_privilege::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
