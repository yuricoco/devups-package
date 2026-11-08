<?php


use dclass\devups\Datatable\Datatable as Datatable;

class CmsresourceTable extends Datatable
{

    public function __construct($lazyloading = null, $datatablemodel = [])
    {
        parent::__construct($lazyloading, $datatablemodel);
    }

    public static function init(\Cmsresource $cmsresource = null)
    {
        $dt = new CmsresourceTable($cmsresource);
        $dt->entity = $cmsresource;

        return $dt;
    }

    public function buildindextable()
    {

        $this->datatablemodel = [
            'image' => ['header' => t('image'), 'value' => 'src:image'],
        ];

        $this->enablepagination = false;
        $this->per_page = "all";
        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('width'), 'value' => 'width'],
            ['label' => t('height'), 'value' => 'height'],
        ];
        return $this;
    }

}
