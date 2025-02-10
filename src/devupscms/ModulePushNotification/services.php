<?php
            //ModulePushNotification
		
        require '../../../admin/header.php';
        
// verification token
//

        use Genesis as g;
        use Request as R;
        
        header("Access-Control-Allow-Origin: *");
                

		$push_subscriptionCtrl = new Push_subscriptionController();
		
     (new Request('hello'));

     switch (R::get('path')) {
                
        case 'push_subscription._new':
                g::json_encode($push_subscriptionCtrl->formView());
                break;
        case 'push_subscription.create':
                g::json_encode($push_subscriptionCtrl->createAction());
                break;
        case 'push_subscription._edit':
                g::json_encode($push_subscriptionCtrl->formView(R::get("id")));
                break;
        case 'push_subscription.update':
                g::json_encode($push_subscriptionCtrl->updateAction(R::get("id")));
                break;
        case 'push_subscription._show':
                $push_subscriptionCtrl->detailView(R::get("id"));
                break;
        case 'push_subscription._delete':
                g::json_encode($push_subscriptionCtrl->deleteAction(R::get("id")));
                break;
        case 'push_subscription._deletegroup':
                g::json_encode($push_subscriptionCtrl->deletegroupAction(R::get("ids")));
                break;
        case 'push_subscription.datatable':
                g::json_encode($push_subscriptionCtrl->datatable(R::get('next'), R::get('per_page')));
                break;

	
        default:
            g::json_encode(['success' => false, 'error' => ['message' => "404 : action note found", 'route' => R::get('path')]]);
            break;
     }

