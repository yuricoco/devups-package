<?php

use dclass\devups\Controller\Controller;

/**
 * Ecran back-office en lecture seule (comme le module Log) : Emaillog
 * n'est alimente que par un envoi d'email reel (DMail::sendMail(),
 * Reportingmodel::sendMail()), jamais par une action du back-office.
 */
class EmaillogController extends Controller
{

    public function listView()
    {
        if ($this->forbidIfCannotFor('read', 'Emaillog')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $this->datatable = EmaillogTable::init(new Emaillog())->buildindextable();

        self::$jsfiles[] = Emaillog::classpath('Resource/js/emaillogCtrl.js');

        $this->entitytarget = 'Emaillog';
        $this->title = "Emails envoyes";

        $this->renderListView();

    }

    public function datatable($next, $per_page)
    {
        if ($this->forbidIfCannotFor('read', 'Emaillog'))
            return ['success' => false, 'detail' => t('Action non autorisee')];

        return ['success' => true,
            'datatable' => EmaillogTable::init(new Emaillog())->router()->getTableRest(),
        ];

    }

    public function detailView($id)
    {
        if ($this->forbidIfCannotFor('read', 'Emaillog')) {
            echo json_encode(['success' => false, 'detail' => t('Action non autorisee')]);
            die;
        }

        $this->entitytarget = 'Emaillog';
        $this->title = "Detail de l'email";

        $emaillog = Emaillog::find($id);

        $this->renderDetailView(
            EmaillogTable::init()
                ->builddetailtable()
                ->renderentitydata($emaillog),
            false,
            '',
        );
    }

}
