<?php


class CmsresourceFrontController extends CmsresourceController
{

    public function ll($next = 1, $per_page = 10)
    {

        $ll = new Lazyloading();
        $ll->lazyloading(new Cmsresource());
        return $ll;

    }

    public function detailAction($id)
    {

        $cmsresource = Cmsresource::find($id);

        return array('success' => true,
            'cmsresource' => $cmsresource,
            'detail' => '');

    }

}
