<?php


use compagnons\ModuleMember\Datatable\BlacklistTable;
use compagnons\ModuleMember\Entity\Blacklist;
use compagnons\ModuleMember\Form\BlacklistForm;
use dclass\devups\Controller\Controller;

class BlacklistController extends Controller{

    public function listView(){

        $this->datatable = BlacklistTable::init(new Blacklist())->buildindextable();

        self::$jsfiles[] = Blacklist::classpath('Resource/js/blacklistCtrl.js');

        $this->entitytarget = 'Blacklist';
        $this->title = "Manage Blacklist";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => BlacklistTable::init(new Blacklist())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $blacklist = new Blacklist();
        $action = __env.("admin/api/blacklist/create");
        if ($id) {
            $action = __env.("admin/api/blacklist/update?id=" . $id);
            $blacklist = Blacklist::find($id);
        }

        return ['success' => true,
            'form' => BlacklistForm::init($blacklist, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($blacklist_form = null ){
        extract($_POST);

        $blacklist = $this->form_fillingentity(new Blacklist(), $blacklist_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'blacklist' => $blacklist,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $blacklist->__insert();
        return 	array(	'success' => true,
                        'blacklist' => $blacklist,
                        'tablerow' => BlacklistTable::init()->router()->getSingleRowRest($blacklist),
                        'detail' => '');

    }

    public function updateAction($id, $blacklist_form = null){
        extract($_POST);
            
        $blacklist = $this->form_fillingentity(new Blacklist($id), $blacklist_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'blacklist' => $blacklist,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $blacklist->__update();
        return 	array(	'success' => true,
                        'blacklist' => $blacklist,
                        'tablerow' => BlacklistTable::init()->router()->getSingleRowRest($blacklist),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Blacklist';
        $this->title = "Detail Blacklist";

        $blacklist = Blacklist::find($id);

        $this->renderDetailView(
            BlacklistTable::init()
                ->builddetailtable()
                ->renderentitydata($blacklist)
        );

    }
    
    public function deleteAction($id){
    
        $blacklist = Blacklist::find($id);
        $blacklist->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Blacklist::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

}
