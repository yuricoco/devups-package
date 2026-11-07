<?php 

            
use dclass\devups\Controller\Controller;

class Dvups_logController extends Controller{

    public function listView(){

        $this->datatable = Dvups_logTable::init(new Dvups_log())->buildindextable();

        self::$jsfiles[] = Dvups_log::classpath('Resource/js/dvups_logCtrl.js');

        $this->entitytarget = 'Dvups_log';
        $this->title = "Manage Dvups_log";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => Dvups_logTable::init(new Dvups_log())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $dvups_log = new Dvups_log();
        $action = __env.("admin/api/dvups_log/create");
        if ($id) {
            $action = __env.("admin/api/dvups_log/update?id=" . $id);
            $dvups_log = Dvups_log::find($id);
        }

        return ['success' => true,
            'form' => Dvups_logForm::init($dvups_log, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($dvups_log_form = null ){
        extract($_POST);

        $dvups_log = $this->form_fillingentity(new Dvups_log(), $dvups_log_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_log' => $dvups_log,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $dvups_log->__insert();
        return 	array(	'success' => true,
                        'dvups_log' => $dvups_log,
                        'tablerow' => Dvups_logTable::init()->router()->getSingleRowRest($dvups_log),
                        'detail' => '');

    }

    public function updateAction($id, $dvups_log_form = null){
        extract($_POST);
            
        $dvups_log = $this->form_fillingentity(new Dvups_log($id), $dvups_log_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'dvups_log' => $dvups_log,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $dvups_log->__update();
        return 	array(	'success' => true,
                        'dvups_log' => $dvups_log,
                        'tablerow' => Dvups_logTable::init()->router()->getSingleRowRest($dvups_log),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Dvups_log';
        $this->title = "Detail Dvups_log";

        $dvups_log = Dvups_log::find($id);

        $this->renderDetailView(
            Dvups_logTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_log)
        );

    }
    
    public function deleteAction($id){
    
        $dvups_log = Dvups_log::find($id);
        $dvups_log->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Dvups_log::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
