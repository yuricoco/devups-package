<?php
//ModuleLocation

require '../../../admin/header.php';

// verification token
//

use Genesis as g;
use Request as R;

header("Access-Control-Allow-Origin: *");


$countryCtrl = new CountryController();

(new Request('hello'));

switch (R::get('path')) {

    case 'country._new':
        g::json_encode($countryCtrl->formView());
        break;
    case 'country.create':
        g::json_encode($countryCtrl->createAction());
        break;
    case 'country.form':
        g::json_encode($countryCtrl->formView(R::get("id")));
        break;
    case 'country.update':
        g::json_encode($countryCtrl->updateAction(R::get("id")));
        break;
    case 'country._show':
        $countryCtrl->detailView(R::get("id"));
        break;
    case 'country._delete':
        g::json_encode($countryCtrl->deleteAction(R::get("id")));
        break;
    case 'country._deletegroup':
        g::json_encode($countryCtrl->deletegroupAction(R::get("ids")));
        break;
    case 'country.datatable':
        g::json_encode($countryCtrl->datatable(R::get('next'), R::get('per_page')));
        break;

    default:
        g::json_encode(['success' => false, 'error' => ['message' => "404 : action note found", 'route' => R::get('path')]]);
        break;
}
