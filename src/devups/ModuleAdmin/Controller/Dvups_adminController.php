<?php


use dclass\devups\Controller\Controller;

class Dvups_adminController extends Controller
{

    /**
     * @var bool
     */
    private $err;

    function __construct()
    {
        $this->err = array();
    }

    public function resetcredential($id)
    {

        $dvups_admin = Dvups_admin::find($id);
        $password = $dvups_admin->generatePassword();
        $dvups_admin->setPassword(sha1($password));
//        $dvups_admin->setLogin();
        $dvups_admin->generateLogin();

        $dvups_admin->__save();
        redirect('/admin/devups/ModuleAdmin/dvups-admin/added?login=' . $dvups_admin->getLogin() . "&password=" . $password);

    }

    public function deconnexionAction()
    {

        $_SESSION[ADMIN] = array();
        unset($_SESSION[LANG]);
        unset($_SESSION[ADMIN]);
        unset($_SESSION[dv_role_permission]);
        unset($_SESSION[dv_role_navigation]);
        //session_destroy();

        if (isset($_COOKIE[ADMIN])) {
            \DClass\lib\Util::clearcookie(ADMIN);
            \DClass\lib\Util::clearcookie(ADMIN."_login");
            \DClass\lib\Util::clearcookie(ADMIN."_pwd");
        }

        redirect( route('admin/login.php'));
    }

    public static function initSession(\User $user, $remember_me = null){

    }

    static function restartsessionAction()
    {

        if (!isset($_COOKIE[ADMIN]) || !isset($_COOKIE[ADMIN."_login"])  || !isset($_COOKIE[ADMIN."_pwd"]) )
            return 0;

        if(isset($_SESSION[ADMIN]))
            return 0;

        //$dbal = new DBAL(new User());
        //$user = $dbal->findOneElementWhereXisY(['user.email', 'user.devupspwd'], [$_COOKIE[USERMAIL], $_COOKIE[USERPASS]]);

        $admin = Dvups_admin::where("this.login", $_COOKIE[ADMIN."_login"])
            ->andwhere('this.password', $_COOKIE[ADMIN."_pwd"])
            ->first();
        if ($admin->getId()) {

//            Dvups_roleController::getNavigationAction($admin);
            $_SESSION[ADMIN] = serialize($admin);
            return 1;
            //header("location: " . __env ."/admin". $url);
        } else {
            redirect(route("admin/"));
        }
        die;
    }

    public function connexionAction()
    {
        if (!isset($_POST['login']) and $_POST['login'] != '' and !isset($_POST['password'])) {
            redirect( __env . "admin/login.php?error=Entré le login et le mot de passe.");
        }
        extract($_POST);

        $admin = Dvups_admin::select()->where('login', $login)->andwhere('password', sha1($password))->first();

        if (!$admin->getId())
            redirect( __env . "admin/login.php?err=" . 'Login ou mot de passe incorrect.');

//        Dvups_roleController::getNavigationAction($admin);
        $_SESSION[ADMIN] = serialize($admin);
        //Local_contentController::buildlocalcachesinglelang($_POST['lang']);

        $admin->setLastloginAt(date("Y-m-d H:i:s"));
        $admin->__update(["lastlogin_at" => date("Y-m-d H:i:s")]);

        if ($admin->getFirstconnexion()) {
            redirect(Dvups_admin::classview("dvups-admin/complete-registration?id=" . $admin->getId()));
            return;
        }

        if (isset($remember_me)) {
            //set cookie
            \DClass\lib\Util::setcookie(ADMIN, 1);
            \DClass\lib\Util::setcookie(ADMIN."_login", $admin->getLogin());
            \DClass\lib\Util::setcookie(ADMIN."_pwd", $admin->getPassword());
        }

        //return array('success' => false, "err" => 'Login ou mot de passe incorrect.');

        //$admin->collectDvups_role();

        $_SESSION[CSRFTOKEN] = serialize($admin);
        //$_SESSION[LANG] = $_POST['lang'];
        //dv_dump($login, $password, $admin);
        redirect(__env . "admin/dashboard");

//        return array('success' => true,
//            'url' => 'index.php',
//            'detail' => 'detail de l\'action.');
    }


    public function createAction()
    {
        extract($_POST);
        $this->err = array();

        $dvups_admin = $this->form_generat(new Dvups_admin(), $dvups_admin_form);

        if ($this->error) {
            return array('success' => false,
                'dvups_admin' => $dvups_admin,
                'action_form' => 'create',
                'error' => $this->error);
        }

        $password = $dvups_admin->generatePassword();
        $dvups_admin->setPassword(sha1($password));
        $dvups_admin->setLogin($dvups_admin->generateLogin());

        $id = $dvups_admin->__insert();

        // create curl resource
        // $this->sendmail($password, $dvups_admin);

        return array('success' => true, // pour le restservice
            'dvups_admin' => $dvups_admin,
            'tablerow' => Dvups_adminTable::init()->buildindextable()->getSingleRowRest($dvups_admin),
            'redirect' => '/admin/devups/ModuleAdmin/dvups-admin/added?login=' . $dvups_admin->getLogin() . "&password=" . $password, // pour le web service
            'detail' => ''); //Detail de l'action ou message d'erreur ou de succes

    }

    public function updateAction($id)
    {
        extract($_POST);
        $this->err = array();

        $dvups_admin = $this->form_generat(new Dvups_admin($id), $dvups_admin_form);

        if ($this->error) {
            return array('success' => false,
                'testentity' => $dvups_admin,
                'action_form' => 'create',
                'error' => $this->error);
        }

        $dvups_admin->__update();

        return array('success' => true, // pour le restservice
            'dvups_admin' => $dvups_admin,
            'tablerow' => Dvups_adminTable::init()->buildindextable()->getSingleRowRest($dvups_admin),
            'detail' => ''); //Detail de l'action ou message d'erreur ou de succes

    }

    public function formView($id = null)
    {
        $dvups_admin = new Dvups_admin();
        $action = Dvups_admin::classpath("services.php?path=dvups_admin.create");
        if ($id) {
            $action = Dvups_admin::classpath("services.php?path=dvups_admin.update&id=" . $id);
            $dvups_admin = Dvups_admin::find($id);
            //$dvups_admin->collectDvups_role();
        }

        return ['success' => true,
            'form' => Dvups_adminForm::__renderForm($dvups_admin, $action, true),
        ];
    }

    public function datatable($next, $per_page)
    {

        return ['success' => true,
            'datatable' => Dvups_adminTable::init(new Dvups_admin())->buildindextable()->getTableRest(),
        ];
    }

    public function listView($next = 1, $per_page = 10)
    {

        //self::$jsfiles[] = Client::classpath('Ressource/js/dvups_roleCtrl.js');

        $this->datatable = Dvups_adminTable::init(new Dvups_admin())->buildindextable();

        $this->entitytarget = 'dvups_admin';
        $this->title = "Manage Admin";

        $this->renderListView();

    }

    public function deleteAction($id)
    {
        $admin = Dvups_admin::find($id);
        $admin->__delete();
        return ["success" => true];
    }

    public function completeRegistration($id)
    {
        // if ()
        self::$sidebar = false;
        $admin = Dvups_admin::find($id);
        $action = "/admin/devups/ModuleAdmin/dvups-admin/complete?id=" . $id;
        //return compact("admin", "action");
        Genesis::renderView('admin.dvups_admin.complete_registration', compact("admin", "action"));

    }

    public function complete($id)
    {
        $admin = Dvups_admin::find($id);
        $error = "";
        extract($_POST);
        if ($newpwd == $confimnewpwd) {

            if (sha1($currentpwd) == $admin->getPassword()) {
                $admin->password = sha1($newpwd);
                $admin->firstconnexion = 0;
                $admin->__update();

                unset($_SESSION[ADMIN]);
                redirect(__env."admin/");

            } else {
                $error = 'Incorrect Current password!';
            }
        } else
            $error = 'Incorrect confirmation password! It must be the same as the new password';

        self::$sidebar = false;
        $action = "/admin/devups/ModuleAdmin/dvups-admin/complete?id=" . $id;
        Genesis::renderView('admin.dvups_admin.complete_registration', compact("admin", "action", "error"));
    }

    public function profile()
    {
        $admin = getadmin();
        Genesis::renderView('admin.dvups_admin.profile', ["admin" => $admin]);

    }

    public function added()
    {
        $admin = getadmin();
        Genesis::renderView('admin.dvups_admin.added');

    }
    public function credential()
    {
        Genesis::renderView('admin.dvups_admin.credential', ["admin" => Dvups_admin::find(getadmin()->getId())], "profile");
    }
    public function changepwd()
    {
        Genesis::renderView('admin.dvups_admin.changepwd', ["detail" => ""], 'list');

    }

    public function changepassword()
    {
//        $adminDao = new AdminDAO();
        $dvups_admin = Dvups_admin::find(getadmin()->getId());
        extract($_POST);
        if (sha1($oldpwd) == $dvups_admin->getPassword()) {
            $dvups_admin->__update("password", sha1($newpwd));
            $data = array('success' => true, // pour le restservice
                'redirect' => Dvups_admin::classpath() . 'dvups-admin/profile?detail=password updated successfully', // pour le web service
                'detail' => '');
        } else {
            $data = array('success' => false, // pour le restservice
                'detail' => 'mot de passe incorrect');
        }
        Genesis::renderView('admin.dvups_admin.changepwd', $data);
    }

}
