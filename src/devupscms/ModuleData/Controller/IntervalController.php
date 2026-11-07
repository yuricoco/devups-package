<?php 

            
use dclass\devups\Controller\Controller;
use devupscms\ModuleData\Entity\Interval_group;

class IntervalController extends Controller{

    public function listView(){

        $this->datatable = IntervalTable::init(new Interval())->buildindextable();

        self::$jsfiles[] = Interval::classpath('Resource/js/intervalCtrl.js');

        $this->entitytarget = 'Interval';
        $this->title = "Manage Interval";
        
        $this->renderListView();

    }

    public function datatable() {
    
        return ['success' => true,
            'datatable' => IntervalTable::init(new Interval())->router()->getTableRest(),
        ];
        
    }

    public function formView($id = null)
    {
        $interval = new Interval();
        $action = __env.("admin/api/interval/create");
        if ($id) {
            $action = __env.("admin/api/interval/update?id=" . $id);
            $interval = Interval::find($id);
        }

        return ['success' => true,
            'form' => IntervalForm::init($interval, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($interval_form = null ){
        extract($_POST);

        $interval = $this->form_fillingentity(new Interval(), $interval_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'interval' => $interval,
                            'action' => 'create', 
                            'error' => $this->error);
        } 
        

        $id = $interval->__insert();
        return 	array(	'success' => true,
                        'interval' => $interval,
                        'tablerow' => IntervalTable::init()->router()->getSingleRowRest($interval),
                        'detail' => '');

    }

    public function updateAction($id, $interval_form = null){
        extract($_POST);
            
        $interval = $this->form_fillingentity(new Interval($id), $interval_form);
     
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'interval' => $interval,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }
        
        $interval->__update();
        return 	array(	'success' => true,
                        'interval' => $interval,
                        'tablerow' => IntervalTable::init()->router()->getSingleRowRest($interval),
                        'detail' => '');
                        
    }
    

    public function detailView($id)
    {

        $this->entitytarget = 'Interval';
        $this->title = "Detail Interval";

        $interval = Interval::find($id);

        $this->renderDetailView(
            IntervalTable::init()
                ->builddetailtable()
                ->renderentitydata($interval)
        );

    }
    
    public function deleteAction($id){
    
        $interval = Interval::find($id);
        $interval->__delete();
        
        return 	array(	'success' => true, 
                        'detail' => t('Item deleted successfully'));
                         
    }
    

    public function deletegroupAction($ids)
    {

        Interval::where("this.id")->in($ids)->delete();

        return array('success' => true,
                'detail' => ''); 

    }

    public function config()
    {

        Genesis::renderView('admin.interval.config');

    }


    public function toggleGroup()
    {

        $raw = Request::raw();
        if ($raw['checked'] == 1)
            Interval_group::create($raw['interval_group']);
        else
            Interval_group::where($raw['interval_group'])->delete();

        return [
            'success' => true
        ];

    }

}
