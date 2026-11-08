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

        $detailHtml = Dvups_logTable::init()
            ->builddetailtable()
            ->renderentitydata($dvups_log);

        if ($dvups_log->log_file && file_exists(ROOT . $dvups_log->log_file)) {
            $fullContent = file_get_contents(ROOT . $dvups_log->log_file);
            $detailHtml .= '<div class="dvups-log-overflow">'
                . '<h4>' . t('Contenu complet') . '</h4>'
                . '<pre>' . htmlspecialchars($fullContent) . '</pre>'
                . '</div>';
        }

        $this->renderDetailView($detailHtml, false, '');
    }

}
