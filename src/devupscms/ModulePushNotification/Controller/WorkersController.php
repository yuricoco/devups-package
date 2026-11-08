<?php


use dclass\devups\Controller\Controller;
use devupscms\ModulePushNotification\Entity\Workers;

class WorkersController extends Controller
{

    public function listView()
    {

        $this->datatable = WorkersTable::init(new Workers())->buildindextable();

        self::$jsfiles[] = Workers::classpath('Resource/js/workersCtrl.js');

        $this->entitytarget = 'Workers';
        $this->title = "Manage Workers";

        $this->renderListView();
    }

    public function datatable()
    {

        return [
            'success' => true,
            'datatable' => WorkersTable::init(new Workers())->router()->getTableRest(),
        ];
    }

    public function formView($id = null)
    {
        $workers = new Workers();
        $action = __env . ("admin/api/workers/create");
        if ($id) {
            $action = __env . ("admin/api/workers/update?id=" . $id);
            $workers = Workers::find($id);
        }

        return [
            'success' => true,
            'form' => WorkersForm::init($workers, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($workers_form = null)
    {
        extract($_POST);

        $workers = $this->form_fillingentity(new Workers(), $workers_form);
        if ($this->error) {
            return array(
                'success' => false,
                'workers' => $workers,
                'action' => 'create',
                'error' => $this->error
            );
        }


        $id = $workers->__insert();
        return array(
            'success' => true,
            'workers' => $workers,
            'tablerow' => WorkersTable::init()->router()->getSingleRowRest($workers),
            'detail' => ''
        );
    }

    public function updateAction($id, $workers_form = null)
    {
        extract($_POST);

        $workers = $this->form_fillingentity(new Workers($id), $workers_form);

        if ($this->error) {
            return array(
                'success' => false,
                'workers' => $workers,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error
            );
        }

        $workers->__update();
        return array(
            'success' => true,
            'workers' => $workers,
            'tablerow' => WorkersTable::init()->router()->getSingleRowRest($workers),
            'detail' => ''
        );
    }


    public function detailView($id)
    {

        $this->entitytarget = 'Workers';
        $this->title = "Detail Workers";

        $workers = Workers::find($id);

        $this->renderDetailView(
            WorkersTable::init()
                ->builddetailtable()
                ->renderentitydata($workers)
        );
    }

    public function deleteAction($id)
    {

        $workers = Workers::find($id);
        $workers->__delete();

        return array(
            'success' => true,
            'detail' => t('Item deleted successfully')
        );
    }


    public function deletegroupAction($ids)
    {

        Workers::where("this.id")->in($ids)->delete();

        return array(
            'success' => true,
            'detail' => ''
        );
    }

    public function testPushNotif($id)
    {

        if (Workers::initQb()
            ->whereId($id)
            ->where("this.type", 'push_notification')->count()
        )
            Push_subscription::initPusher();

        Workers::initQb()
            ->whereId($id)
            //            ->where("this.type", 'push_notification')
            ->cursor(function (\devupscms\ModulePushNotification\Entity\Workers $worker) {
                if ($worker->type === 'push_notification') {
                    $pusher = Push_subscription::find($worker->queue);
                    $payload = json_decode($worker->payload, true);
                    $result = $pusher->fcmPushNotification($payload['message'], $payload['data']);
                }

                //                if ($result)
                //                else
                //                    $worker->__update(['log' => $result]);

            });
    }

    public function work()
    {

        // Etait whereNotNull (les workers EN ECHEC) - la boucle
        // juste en dessous traite ceux EN ATTENTE (whereNull) : sur une file
        // saine, initPusher() n'etait donc jamais appele, Push_subscription::$access_token
        // restait null, et fcmPushNotification() envoyait un
        // "Authorization: Bearer " vide -> 401 systematique. Ne touche pas
        // expoPushNotification() (n'utilise pas $access_token).
        if (Workers::initQb()
            ->whereNull("this.log")
            ->where("this.type", 'push_notification')->count()
        )
            Push_subscription::initPusher();

        $date = date('Y-m-d H:i:s');
        Workers::initQb()
            ->whereNull("this.log")
            // COALESCE(this.timer, 5) : un worker sans `timer` part apres 5 minutes
            // (Model::create() n'applique pas le defaut PHP de l'entite).
            ->where_str(" TIMESTAMPDIFF(MINUTE, this.created_at, '$date') >= COALESCE(this.timer, 5) ")
            ->limit(__prod ? 900 : 5)
            ->cursor(function (\devupscms\ModulePushNotification\Entity\Workers $worker) {
                if ($worker->type === 'push_notification') {
                    // Garde pilotable : __prod reste la seule porte en
                    // production, mais PUSH_FORCE_SEND=1 (variable d'environnement,
                    // jamais une constante de config) permet de prouver un envoi
                    // reel en dev sans rien ecrire vers de vrais utilisateurs de
                    // production - c'est le seul effet de ce drapeau.
                    if (!(__prod || getenv('PUSH_FORCE_SEND') === '1')) {
                        // Rien tente : le worker reste EN ATTENTE, jamais supprime
                        // en silence (un passage en dev ne doit pas vider la file).
                        return null;
                    }

                    $pusher = Push_subscription::find($worker->queue);
                    $payload = json_decode($worker->payload, true);
                    // ETAPE M.3 : Web Push (VAPID) a cote du FCM/Expo existant,
                    // selon le type d'abonnement - jamais les deux pour le meme.
                    if ($pusher->subscription_type === 'webpush')
                        $result = $pusher->sendWebPush($payload['message'], $payload['data'] ?? []);
                    else
                        $result = $pusher->fcmPushNotification($payload['message'], $payload['data']);
                    if ($result !== true) {
                        // fcmPushNotification()/expoPushNotification() ecrivent deja
                        // leur propre Emaillog en echec - mais $result n'etait jusque
                        // la jamais lu ici : le worker etait supprime quand meme.
                        // Desormais il reste, avec un log explicite.
                        $worker->__update(['this.log' => 'envoi push echoue, voir emaillog pour la reponse du serveur']);
                        return null;
                    }
                } else {

                    if (is_numeric($worker->payload))
                        $payload = $worker->payload;
                    else
                        $payload = $worker->payload ? unserialize($worker->payload) : null;

                    $queue = unserialize($worker->queue);
                    // Si tout est OK, on marque comme complété
                    if (is_object($queue) && method_exists($queue, 'workerCompleted')) {
                        try {
                            $queue->workerCompleted($payload);
                        } catch (\Throwable $exception) {
                            $worker->__update([
                                'this.log' => $exception->getMessage()
                            ]);
                            return null;
                        }
                    }
                }

                $worker->__delete();
            });
    }
}
