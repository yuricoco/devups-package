<?php 

            
use dclass\devups\Controller\Controller;
use devupsfront\ModuleUser\Entity\Oauth_client;

class Oauth_clientController extends Controller{

    public function listView(){

        $this->datatable = Oauth_clientTable::init(new Oauth_client())->buildindextable();

        self::$jsfiles[] = Oauth_client::classpath('Resource/js/oauth_clientCtrl.js');

        $this->entitytarget = 'Oauth_client';
        $this->title = "Manage Oauth_client";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => Oauth_clientTable::init(new Oauth_client())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $oauth_client = new Oauth_client();
        $action = __env.("admin/api/oauth_client/create");
        if ($id) {
            $action = __env.("admin/api/oauth_client/update?id=" . $id);
            $oauth_client = Oauth_client::find($id);
        }

        return ['success' => true,
            'form' => Oauth_clientForm::init($oauth_client, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($oauth_client_form = null ){
        extract($_POST);

        $oauth_client = $this->form_fillingentity(new Oauth_client(), $oauth_client_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'oauth_client' => $oauth_client,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $oauth_client->__insert();
        return 	array(	'success' => true,
                        'oauth_client' => $oauth_client,
                        'tablerow' => Oauth_clientTable::init()->router()->getSingleRowRest($oauth_client),
                        'detail' => '');

    }

    public function updateAction($id, $oauth_client_form = null){
        extract($_POST);
            
        $oauth_client = $this->form_fillingentity(new Oauth_client($id), $oauth_client_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'oauth_client' => $oauth_client,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $oauth_client->__update();
        return 	array(	'success' => true,
                        'oauth_client' => $oauth_client,
                        'tablerow' => Oauth_clientTable::init()->router()->getSingleRowRest($oauth_client),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Oauth_client';
        $this->title = "Detail Oauth_client";

        $oauth_client = Oauth_client::find($id);

        $this->renderDetailView(
            Oauth_clientTable::init()
                ->builddetailtable()
                ->renderentitydata($oauth_client)
        );

    }
    
    public function deleteAction($id){
    
        $oauth_client = Oauth_client::find($id);
        $oauth_client->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Oauth_client::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
