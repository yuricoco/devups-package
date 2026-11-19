<?php

namespace devupscms\ModuleLocation\Controller;

class CountryFrontController extends CountryController{

    public function ll($next = 1, $per_page = 10){
        
            $ll = new \Lazyloading();
            $ll->lazyloading(new \Country());
            return $ll;

    }

    public function createAction($country_form = null ){
        $rawdata = \Request::raw();
        $country = $this->hydrateWithJson(new \Country(), $rawdata["country"]);
 

        
        $id = $country->__insert();
        return 	array(	'success' => true,
                        'country' => $country,
                        'detail' => '');

    }

    public function updateAction($id, $country_form = null){
        $rawdata = \Request::raw();
            
        $country = $this->hydrateWithJson(new \Country($id), $rawdata["country"]);

                  
        
        $country->__update();
        return 	array(	'success' => true,
                        'country' => $country,
                        'detail' => '');
                        
    }
    

    public function detailAction($id)
    {

        $country = \Country::find($id);

        return 	array(	'success' => true,
                        'country' => $country,
                        'detail' => '');

}


}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(CountryFrontController::class, 'CountryFrontController');
