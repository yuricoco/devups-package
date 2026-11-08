<?php 


use dclass\devups\Controller\Controller;

class Dvups_actionController extends Controller{

    public function listView(){

        $this->datatable = Dvups_actionTable::init(new Dvups_action())->buildindextable();

        self::$jsfiles[] = Dvups_action::classpath('Resource/js/dvups_actionCtrl.js');

        $this->entitytarget = 'Dvups_action';
        $this->title = "Manage Dvups_action";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => Dvups_actionTable::init(new Dvups_action())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $dvups_action = new Dvups_action();
        $action = __env.("admin/api/dvups_action/create");
        if ($id) {
            $action = __env.("admin/api/dvups_action/update?id=" . $id);
            $dvups_action = Dvups_action::find($id);
        }

        return ['success' => true,
            'form' => Dvups_actionForm::init($dvups_action, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($dvups_action_form = null ){
        extract($_POST);

        $dvups_action = $this->form_fillingentity(new Dvups_action(), $dvups_action_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_action' => $dvups_action,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $dvups_action->__insert();
        return 	array(	'success' => true,
                        'dvups_action' => $dvups_action,
                        'tablerow' => Dvups_actionTable::init()->router()->getSingleRowRest($dvups_action),
                        'detail' => '');

    }

    public function updateAction($id, $dvups_action_form = null){
        extract($_POST);
            
        $dvups_action = $this->form_fillingentity(new Dvups_action($id), $dvups_action_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_action' => $dvups_action,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $dvups_action->__update();
        return 	array(	'success' => true,
                        'dvups_action' => $dvups_action,
                        'tablerow' => Dvups_actionTable::init()->router()->getSingleRowRest($dvups_action),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Dvups_action';
        $this->title = "Detail Dvups_action";

        $dvups_action = Dvups_action::find($id);

        $this->renderDetailView(
            Dvups_actionTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_action)
        );

    }
    
    public function deleteAction($id){
    
        $dvups_action = Dvups_action::find($id);
        $dvups_action->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Dvups_action::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
