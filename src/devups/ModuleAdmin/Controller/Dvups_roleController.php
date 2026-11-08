<?php


use dclass\devups\Controller\Controller;

class Dvups_roleController extends Controller
{

    public function listView()
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_role')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $role = Dvups_role::find(2);
        $role->updateConfigs();

        $this->datatable = Dvups_roleTable::init(new Dvups_role())->buildindextable();

        self::$jsfiles[] = Dvups_role::classpath('Resource/js/dvups_roleCtrl.js');

        $this->entitytarget = 'Dvups_role';
        $this->title = "Manage Dvups_role";

        $this->renderListView();

    }

    public function datatable()
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        return ['success' => true,
            'datatable' => Dvups_roleTable::init(new Dvups_role())->router()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        $dvups_role = new Dvups_role();
        $action = __env . ("admin/api/dvups_role/create");
        if ($id) {
            $action = __env . ("admin/api/dvups_role/update?id=" . $id);
            $dvups_role = Dvups_role::find($id);
        }

        return ['success' => true,
            'form' => Dvups_roleForm::renderWidget($dvups_role, $action),
            /*'form' => Dvups_roleForm::init($dvups_role, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),*/
        ];
    }

    public function createAction($dvups_role_form = null)
    {
        if ($this->forbidIfCannotFor('create', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        extract($_POST);

        $dvups_role = $this->form_fillingentity(new Dvups_role(), $dvups_role_form);
        if ($this->error) {
            return array('success' => false,
                'dvups_role' => $dvups_role,
                'action' => 'create',
                'error' => $this->error);
        }

        $dvups_role->updateConfigs();
        $id = $dvups_role->__insert();
        if ($this->isMasterAdmin())
            self::syncPrivileges($id);
        return array('success' => true,
            'dvups_role' => $dvups_role,
            'tablerow' => Dvups_roleTable::init(new Dvups_role())->router()->getSingleRowRest($dvups_role),
            'detail' => '');

    }

    public function updateAction($id, $dvups_role_form = null)
    {
        if ($this->forbidIfCannotFor('update', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        extract($_POST);

        $dvups_role = $this->form_fillingentity(new Dvups_role($id), $dvups_role_form);

        if ($this->error) {
            return array('success' => false,
                'dvups_role' => $dvups_role,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $dvups_role->updateConfigs();
        $dvups_role->__update();
        // Seul le role maitre peut modifier les privileges d'un role, meme
        // si l'acteur a 'update' sur Dvups_role (ex: juste pour renommer les
        // roles) : sans cette garde, renommer un role effacerait aussi ses
        // privileges (syncPrivileges() part de $_POST['privileges'] ?? [],
        // absent si l'acteur n'a pas la grille dans son propre formulaire).
        if ($this->isMasterAdmin())
            self::syncPrivileges($id);
        return array('success' => true,
            'dvups_role' => $dvups_role,
            'tablerow' => Dvups_roleTable::init(new Dvups_role())->router()->getSingleRowRest($dvups_role),
            'detail' => '');

    }

    /**
     * Aligne les privileges du role sur les cases cochees du formulaire
     * (noms d'actions dvups_action.id dans $_POST['privileges']) : cree les
     * manquantes, retire celles decochees. Jamais appele a la creation d'un
     * role sans aucune case cochee (role vide, legitime). Invalide le cache
     * des droits du role pour que can() voie le changement immediatement.
     * N'est appele que pour un acteur maitre (voir createAction/updateAction).
     */
    private static function syncPrivileges(int $roleId): void
    {
        $wanted = array_map('intval', $_POST['privileges'] ?? []);

        $current = [];
        foreach (Dvups_privilege::where('dvups_role_id', $roleId)->get() as $dvups_privilege)
            $current[$dvups_privilege->dvups_action->getId()] = $dvups_privilege;

        foreach (array_diff($wanted, array_keys($current)) as $actionId)
            Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);

        foreach (array_diff(array_keys($current), $wanted) as $actionId)
            $current[$actionId]->__delete();

        Dvups_role::clearRightsCache($roleId);
    }


    public function detailView($id)
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_role')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $this->entitytarget = 'Dvups_role';
        $this->title = "Detail Dvups_role";

        $dvups_role = Dvups_role::find($id);

        $this->renderDetailView(
            Dvups_roleTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_role)
        );

    }

    public function deleteAction($id)
    {
        if ($this->forbidIfCannotFor('delete', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        $dvups_role = Dvups_role::find($id);
        $dvups_role->__delete();
        Dvups_role::clearRightsCache($id);

        return array('success' => true,
            'detail' => t('Item deleted successfully'));

    }


    public function deletegroupAction($ids)
    {
        if ($this->forbidIfCannotFor('delete', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        Dvups_role::where("this.id")->in($ids)->delete();
        foreach ($ids as $id)
            Dvups_role::clearRightsCache((int)$id);

        return array('success' => true,
            'detail' => '');

    }

    public function privilegeUpdate()
    {
        if ($this->forbidIfCannotFor('update', 'Dvups_role'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        $result = Core::updateDvupsTable();
        /*if ($result) {
            $admin = getadmin();
            self::getNavigationAction($admin);
            $_SESSION[ADMIN] = serialize($admin);

            $message = "Data admin updated with success";
        } else*/
            $message = "Data admin already uptodate";

        return array('success' => true,
            'message' => $message,
            'detail' => '');

    }

}
