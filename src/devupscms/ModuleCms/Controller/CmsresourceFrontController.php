<?php

namespace devupscms\ModuleCms\Controller;

class CmsresourceFrontController extends CmsresourceController
{

    public function ll($next = 1, $per_page = 10)
    {

        $ll = new \Lazyloading();
        $ll->lazyloading(new \Cmsresource());
        return $ll;

    }

    public function detailAction($id)
    {

        $cmsresource = \Cmsresource::find($id);

        return array('success' => true,
            'cmsresource' => $cmsresource,
            'detail' => '');

    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(CmsresourceFrontController::class, 'CmsresourceFrontController');
