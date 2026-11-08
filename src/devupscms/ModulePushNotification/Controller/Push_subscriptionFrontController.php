<?php


/*namespace devupscms\ModulePushNotification\Controller;

use Configuration;
use Emaillog;
use Push_subscription;
use Push_subscriptionTable;
use Request;
use User;*/

use Minishlink\WebPush\VAPID;
use dclass\devups\Controller\Controller;

/**
 * @Api(name='/push_subscription')
 */
class Push_subscriptionFrontController extends \dclass\devups\Controller\FrontController
{

    /** Reservee a l'administration : seul le back-office peut l'appeler. */
    private static function denyUnlessAdmin(): ?array
    {
        if (Router::$admin_route === true) {
            return null;
        }
        http_response_code(403);
        return ['success' => false, 'detail' => 'reserved to the administration'];
    }

    /**
     * Restreinte au demandeur. L'administration ne passe pas par
     * ici (voir PUSH-NOTES.md - mesure, pas suppose) : elle a son
     * propre Push_subscriptionController, une classe distincte.
     *
     * @Auth(authorized=1)
     * @GET
     * @return Lazyloading|QueryBuilder
     * @throws ReflectionException
     */
     public function index(){

            $qb = Push_subscription::initQb()->where('this.user_id', (int)Auth::$user_id);

            return $qb->lazyloading();

    }

    /**
     * Le proprietaire vient de la session, jamais du corps - meme
     * classe de defaut fermee ailleurs le 16/10. Liste blanche : seul
     * `auth_token` est accepte du client (`content_type` n'est lu par
     * aucun code d'envoi - mesure, voir PUSH-NOTES.md - exclu par
     * prudence). `subscription_type`/`status` poses explicitement par le
     * serveur, jamais par le client.
     *
     * @Auth(authorized=1)
     * @POST(path='/register')
     * @return array
     */
    public function register()
    {
        $raw = Request::raw()['push_subscription'] ?? [];
        $data = array_intersect_key($raw, array_flip(['auth_token']));
        $data['user_id'] = (int)Auth::$user_id;
        $data['subscription_type'] = 'user';
        $data['status'] = 1;

        $exist = Push_subscription::where(
            [
                'auth_token' => $data['auth_token'] ?? null,
                'user_id' => $data['user_id'],
            ])->firstOrNull();
        if (!$exist)
            $exist = Push_subscription::create($data);
        /*else
            $exist->__update([
                'public_key' => $subscription['public_key'],
                'auth_token' => $subscription['auth_token'],
            ]);*/

        return [
            'success' => true,
            'exist' => $exist,
        ];
    }

    /**
     * Reservee a l'administration, pas retiree - voir
     * PUSH-NOTES.md pour le choix entre les deux. Reecrit la paire de
     * cles VAPID dans Configuration (invaliderait toute souscription web
     * existante) ; VAPID est abandonne (designb) mais aucun appelant
     * n'a ete trouve nulle part dans ce depot.
     *
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function generateKey()
    {
        if ($denied = self::denyUnlessAdmin()) {
            return $denied;
        }

        $data = VAPID::createVapidKeys();

        Configuration::set('VAPID_PUBLIC_KEY', $data['publicKey']);
        Configuration::set('VAPID_PRIVATE_KEY', $data['privateKey']);
        return [
            'key' => $data['publicKey'],
        ];
    }

    /**
     * Generate public and private key for pushnotification then save to the configuration table
     * @return array
     * @throws ErrorException
     */
    public function key()
    {
        return [
            'key' => Configuration::get('VAPID_PUBLIC_KEY'),
            //'key'=>'BIbPxqOqlFtjC4vHDXWWMRDtXEAg2_llqsYj2uprShJBbbIXsPksaKjAojNJzTyezeMeP3xaFDu6o_G5QVZAmvw'
        ];
    }

}
