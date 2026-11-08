<?php 

            
use dclass\devups\Controller\Controller;

class ConfigurationController extends Controller{

    public function listView(){

        $this->datatable = ConfigurationTable::init(new Configuration())->buildindextable();

        self::$jsfiles[] = Configuration::classpath('Resource/js/configurationCtrl.js');

        $this->entitytarget = 'Configuration';
        $this->title = "Manage Configuration";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => ConfigurationTable::init(new Configuration())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $configuration = new Configuration();
        $action = __env.("admin/api/configuration/create");
        if ($id) {
            $action = __env.("admin/api/configuration/update?id=" . $id);
            $configuration = Configuration::find($id);
        }

        return ['success' => true,
            'form' => ConfigurationForm::init($configuration, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($configuration_form = null ){
        extract($_POST);

        $configuration = $this->form_fillingentity(new Configuration(), $configuration_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'configuration' => $configuration,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $configuration->__insert();
        return 	array(	'success' => true,
                        'configuration' => $configuration,
                        'tablerow' => ConfigurationTable::init()->router()->getSingleRowRest($configuration),
                        'detail' => '');

    }

    public function updateAction($id, $configuration_form = null){
        extract($_POST);
            
        $configuration = $this->form_fillingentity(new Configuration($id), $configuration_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'configuration' => $configuration,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $configuration->__update();
        return 	array(	'success' => true,
                        'configuration' => $configuration,
                        'tablerow' => ConfigurationTable::init()->router()->getSingleRowRest($configuration),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Configuration';
        $this->title = "Detail Configuration";

        $configuration = Configuration::find($id);

        $this->renderDetailView(
            ConfigurationTable::init()
                ->builddetailtable()
                ->renderentitydata($configuration)
        );

    }
    
    public function deleteAction($id){
    
        $configuration = Configuration::find($id);
        $configuration->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Configuration::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

    public function buildAction()
    {
        Configuration::buildConfig();
        return  Response::$data;
    }

}
