<?php


use dclass\devups\Controller\Controller;

/**
 * Ecran back-office en lecture seule : aucune action d'ecriture (pas de
 * create/update/delete) n'est exposee, par construction, pas seulement
 * par une garde can(). Le detail lit le fichier de debordement
 * (cache/log/<AAAA-MM>/<empreinte>.log) quand la ligne en a un.
 */
class Dvups_logController extends Controller
{

    public function listView()
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_log')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $this->datatable = Dvups_logTable::init(new Dvups_log())->buildindextable();

        self::$jsfiles[] = Dvups_log::classpath('Resource/js/dvups_logCtrl.js');

        $this->entitytarget = 'Dvups_log';
        $this->title = "Journal";

        $this->renderListView();

    }

    /**
     * Filtres optionnels (query string, lus par Dvups_logTable::buildindextable())
     * : level (exact), from/to (AAAA-MM-JJ, sur last_at), q (recherche sur
     * entity_class/file/message - couvre un filtre par "module" via le
     * chemin du fichier source).
     */
    public function datatable()
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_log'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        return ['success' => true,
            'datatable' => Dvups_logTable::init(new Dvups_log())->router()->getTableRest(),
        ];

    }

    public function detailView($id)
    {
        if ($this->forbidIfCannotFor('read', 'Dvups_log')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $this->entitytarget = 'Dvups_log';
        $this->title = "Detail du journal";

        $dvups_log = Dvups_log::find($id);
        $fullContent = null;
        if ($dvups_log->log_file && file_exists(ROOT . $dvups_log->log_file))
            $fullContent = file_get_contents(ROOT . $dvups_log->log_file);

        $this->renderDetailView(
            Dvups_logTable::init()
                ->builddetailtable()
                ->renderentitydata($dvups_log),
            false,
            '',
        );
        // $fullContent reste disponible pour la vue detail.blade si elle
        // souhaite l'afficher (cf. Resource/views/admin/dvups_log/detail.blade.php) ;
        // renderDetailView() avec $return=false echoe deja la reponse, donc
        // pas d'injection supplementaire ici sans reprendre ce rendu.
    }

}
