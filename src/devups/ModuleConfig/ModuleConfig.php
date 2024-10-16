<?php




use dclass\devups\Controller\ModuleController;
/*use Dvups_admin;
use Genesis;*/

class ModuleConfig extends ModuleController
{

    public function __construct($route)
    {
        parent::__construct($route);
    }

    public function layoutView()
    {
        Genesis::renderView("admin.overview");
    }

    /**
     * @MethodView(path="dvups-admin/complete-registration")
     */
    public function completeRegistrationView($id)
    {
        Genesis::renderView('admin.dvups_admin.complete_registration', (new \Dvups_adminController())->completeRegistration($id));
    }

    public function completeView($id)
    {
         (new \Dvups_adminController())->completeRegistrationAction($id);
    }
    public function credentialView( )
    {
        Genesis::renderView('admin.dvups_admin.credential', ["admin" => Dvups_admin::find(getadmin()->getId())], "profile");
    }

    public function changepasswordView( )
    {
        self::renderView('admin.dvups_admin.changepwd', (new \Dvups_adminController())->changepwAction(), true);
    }
    public function editpasswordView( )
    {
        self::renderView('admin.dvups_admin.changepwd', ["detail" => ""], 'list');
    }
    public function resetcredentialView($id)
    {
        self::renderView('admin.dvups_admin.index', (new \Dvups_adminController())->resetcredential($id),  true);
    }
    public function addedView()
    {
        Genesis::renderView('admin.dvups_admin.added');
    }

    public function web($path = "")
    {
        // TODO: Implement web() method.
    }

    public function services($path = "")
    {
        // TODO: Implement services() method.
    }

    public function webservices($path = "")
    {
        // TODO: Implement webservices() method.
    }
}