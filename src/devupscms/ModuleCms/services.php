<?php
//ModuleCms

require '../../../admin/header.php';

// verification token
//

use Genesis as g;
use Request as R;

header("Access-Control-Allow-Origin: *");

global $viewdir, $moduledata;
$viewdir[] = __DIR__ . '/Resource/views';


$cmscontentCtrl = new CmscontentController();
$cmsresourceCtrl = new CmsresourceController();

(new Request('hello'));

switch (R::get('path')) {

    case 'cmscontent._new':
        CmscontentForm::render();
        break;
    case 'cmscontent.create':
        g::json_encode($cmscontentCtrl->createAction());
        break;
    case 'cmscontent.form':
        CmscontentForm::render(R::get("id"));
        break;
    case 'cmscontent/update':
        g::json_encode($cmscontentCtrl->updateAction(R::get("id")));
        break;
    case 'cmscontent.update':
        g::json_encode($cmscontentCtrl->updateAction(R::get("id")));
        break;
    case 'cmscontent.uploadimage':
        g::json_encode($cmscontentCtrl->uploadAction());
        break;
    case 'cmscontent.loadimage':
        g::json_encode($cmscontentCtrl->loadAction());
        break;
    case 'cmscontent.deleteimage':
        g::json_encode($cmscontentCtrl->deleteimageAction());
        break;
    case 'cmscontent._show':
        $cmscontentCtrl->detailView(R::get("id"));
        break;
    case 'cmscontent._delete':
        g::json_encode($cmscontentCtrl->deleteAction(R::get("id")));
        break;
    case 'cmscontent._deletegroup':
        g::json_encode($cmscontentCtrl->deletegroupAction(R::get("ids")));
        break;
    case 'cmscontent.datatable':
        g::json_encode($cmscontentCtrl->datatable(R::get('next'), R::get('per_page')));
        break;

    case 'cmsresource._new':
        CmsresourceForm::renderWidget();
        break;
    case 'cmsresource.create':
        g::json_encode($cmsresourceCtrl->createAction());
        break;
    case 'cmsresource.form':
        CmsresourceForm::renderWidget(R::get("id"));
        break;
    case 'cmsresource.update':
        g::json_encode($cmsresourceCtrl->updateAction(R::get("id")));
        break;
    case 'cmsresource._show':
        $cmsresourceCtrl->detailView(R::get("id"));
        break;
    case 'cmsresource._delete':
        g::json_encode($cmsresourceCtrl->deleteAction(R::get("id")));
        break;
    case 'cmsresource._deletegroup':
        g::json_encode($cmsresourceCtrl->deletegroupAction(R::get("ids")));
        break;
    case 'cmsresource.datatable':
        g::json_encode($cmsresourceCtrl->datatable(R::get('next'), R::get('per_page')));
        break;


    default:
        g::json_encode(['success' => false, 'error' => ['message' => "404 : action note found", 'route' => R::get('path')]]);
        break;
}
