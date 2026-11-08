<?php


use dclass\devups\Controller\Controller;

class ReportingmodelController extends Controller
{

    public function __construct()
    {
        $this->entity = new Reportingmodel();
    }

    public function listView($next = 1, $per_page = 10)
    {

        $this->datatable = ReportingmodelTable::init(new Reportingmodel())->buildindextable();

        self::$jsfiles[] = Reportingmodel::classpath('Resource/js/reportingmodelCtrl.js');

        $this->entitytarget = 'Reportingmodel';
        $this->title = "Manage Reportingmodel";

        $this->renderListView();

    }

    public function datatable($next, $per_page)
    {

        return ['success' => true,
            'datatable' => ReportingmodelTable::init(new Reportingmodel())->buildindextable()->getTableRest(),
        ];

    }

    public function editmailView($id = null)
    {

        //self::$jsfiles[] = Reportingmodel::classpath('Resource/js/reportingmodelCtrl.js');

        $action = Reportingmodel::classpath("services.php?path=reportingmodel.update-email&id=" . $id);
        $reportingmodel = Reportingmodel::find($id);
        $langs = Dvups_lang::all();
        Genesis::renderView("admin.reportingmodel.form", [
            "success" => true,
            "reportingmodel" => $reportingmodel,
            "action" => $action,
            "langs" => $langs,
        ]);
    }

    public function formView($id = null)
    {
        $reportingmodel = new Reportingmodel();
        $action = Reportingmodel::classpath("services.php?path=reportingmodel.create");
        if ($id) {
            self::$jsfiles[] = Reportingmodel::classpath('Resource/js/reportingmodelCtrl.js');

            $action = Reportingmodel::classpath("services.php?path=reportingmodel.update&id=" . $id);
            $reportingmodel = Reportingmodel::find($id);

            return ['success' => true,
                'form' => ReportingmodelForm::init($reportingmodel, $action)
                    ->buildForm()
                    ->addDformjs()
                    ->renderForm(),
            ];
        }

        return ['success' => true,
            'form' => ReportingmodelForm::init($reportingmodel, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($reportingmodel_form = null)
    {
        extract($_POST);

        $reportingmodel = $this->form_fillingentity(new Reportingmodel(), $reportingmodel_form);
        if ($this->error) {
            return //Genesis::renderView("reportingmodel.form",
                array('success' => false,
                    'error' => $this->error);//);
        }

        $id = $reportingmodel->__insert();
        //return redirect(Reportingmodel::classpath("reportingmodel/index"));

        // ETAPE M.4 : sens inverse de collectAction() ci-dessous - creer un
        // Reportingmodel depuis le back-office genere son fichier gabarit
        // Blade (web/views/mails/), s'il n'existe pas deja.
        self::ensureTemplateFile(Reportingmodel::find($id));

        return array('success' => true,
            'reportingmodel' => $reportingmodel,
            'tablerow' => ReportingmodelTable::init()->buildindextable()->getSingleRowRest($reportingmodel),
            'detail' => '');

    }

    /**
     * ETAPE M.4 : nom de fichier Blade correspondant a $name - 'mails.foo'
     * (convention attendue par DMail::init()/Genesis::getView(), notation
     * pointee de Blade) donne 'foo.blade.php' ; un nom sans ce prefixe est
     * utilise tel quel (basename() par precaution, meme raisonnement que
     * loadContent()/saveContent() plus haut).
     */
    private static function templateBasename(string $name): string
    {
        $base = basename($name);
        if (strpos($base, 'mails.') === 0)
            $base = substr($base, strlen('mails.'));
        return $base !== '' ? $base : 'template';
    }

    /**
     * N'ecrit JAMAIS par-dessus un fichier existant - seulement pour un
     * modele qui n'a pas encore de gabarit sur le disque.
     */
    private static function ensureTemplateFile(Reportingmodel $reportingmodel): void
    {
        if (!$reportingmodel->getId() || !$reportingmodel->getName())
            return;

        $dir = ROOT . 'web/views/mails/';
        if (!is_dir($dir))
            @mkdir($dir, 0777, true);

        $path = $dir . self::templateBasename($reportingmodel->getName()) . '.blade.php';
        if (file_exists($path))
            return;

        file_put_contents($path, "{{-- genere automatiquement pour le modele '" . $reportingmodel->getName() . "' (ETAPE M.4) --}}\n<h1>{{ \$title ?? '' }}</h1>\n<p>{{ \$message ?? '' }}</p>\n");
    }

    /**
     * ETAPE M.4 : bouton "Collecter les modeles" - parcourt
     * web/views/mails/*.blade.php et cree les Reportingmodel manquants
     * (par nom de fichier, prefixe 'mails.' - meme convention que
     * DMail::init()). Ne supprime jamais rien.
     */
    public function collectAction()
    {
        $dir = ROOT . 'web/views/mails/';
        $created = [];

        foreach (glob($dir . '*.blade.php') ?: [] as $file) {
            $name = 'mails.' . basename($file, '.blade.php');

            // Reportingmodel::getbyattribut('name', ...) genere une colonne
            // `name` ambigue (jointure avec reportingmodel_lang/dvups_lang,
            // qui a elle aussi une colonne `name`) - qualifiee explicitement
            // ici plutot que dans ModelTrait (partagee par tout l'ORM).
            $existing = Reportingmodel::where('reportingmodel.name', $name)->firstOrNull();
            if ($existing && $existing->getId())
                continue;

            $id = Reportingmodel::create([
                'name' => $name,
                'type' => 'email',
                'description' => t('Modele collecte automatiquement depuis :file', ['file' => basename($file)]),
            ]);
            $created[] = Reportingmodel::find($id);
        }

        return [
            'success' => true,
            'created' => $created,
            'detail' => count($created) . ' modele(s) cree(s)',
        ];
    }

    /**
     * ETAPE M.4 : apercu du rendu avec un payload d'exemple (sans envoi).
     */
    public function previewAction($id)
    {
        $reportingmodel = Reportingmodel::find($id);
        if (!$reportingmodel->getId())
            return ['success' => false, 'detail' => t('Modele introuvable')];

        global $viewdir;
        $viewdir[] = ROOT . 'web/views';

        $payload = [
            'title' => 'Exemple',
            'username' => 'Jane Doe',
            'activationcode' => '123456',
            'activation_code' => '123456',
            'message' => 'Ceci est un exemple de contenu.',
            'mail_title' => PROJECT_NAME,
        ];

        $html = Genesis::getView($reportingmodel->getName(), $payload);
        if ($html === null)
            return ['success' => false, 'detail' => t('Apercu impossible : gabarit introuvable')];

        return ['success' => true, 'html' => $html, 'payload' => $payload];
    }

    /**
     * ETAPE M.4 : "Envoi via DMail, enregistre dans Emaillog" - DMail (deja
     * utilise par UserFrontController pour les codes/OTP) rend le meme
     * gabarit Blade que collectAction()/previewAction() et logge deja dans
     * Emaillog (DMail::sendMail()) - independant de
     * Reportingmodel::sendMail() (contenu stocke en base, pas un fichier
     * Blade), conserve tel quel pour ses appelants existants.
     */
    public function sendtestviadmailAction($id, $email)
    {
        $reportingmodel = Reportingmodel::find($id);
        if (!$reportingmodel->getId())
            return ['success' => false, 'detail' => t('Modele introuvable')];

        return DMail::init($reportingmodel->getName())
            ->setObject($reportingmodel->getDescription() ?: $reportingmodel->getName())
            ->addReceiver($email, 'Test')
            ->sendMail([
                'title' => 'Test',
                'username' => 'Test',
                'activationcode' => '000000',
                'activation_code' => '000000',
                'message' => 'Message de test.',
            ]);
    }

    public function updateAction($id, $reportingmodel_form = null)
    {
        extract($_POST);

        $reportingmodel = $this->form_fillingentity(new Reportingmodel($id), $reportingmodel_form);

        if ($this->error) {
            return //Genesis::renderView("reportingmodel.form",
                array('success' => false,
                    'error' => $this->error);//);
        }

        $reportingmodel->__update();
        return array('success' => true,
            'reportingmodel' => $reportingmodel,
            'tablerow' => ReportingmodelTable::init()->buildindextable()->getSingleRowRest($reportingmodel),
            'detail' => '');

    }

    public function updateMailAction($id, $reportingmodel_form = null)
    {
        extract($_POST);

        $reportingmodel = $this->form_fillingentity(new Reportingmodel($id), $reportingmodel_form);

        if ($this->error) {
            return Genesis::renderView("reportingmodel.form", array('success' => false,
                'reportingmodel' => $reportingmodel,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error));
        }

        $reportingmodel->__update();
        redirect(Reportingmodel::classpath("reportingmodel/index"));

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Reportingmodel';
        $this->title = "Detail Reportingmodel";

        $reportingmodel = Reportingmodel::find($id);

        $this->renderDetailView(
            ReportingmodelTable::init()
                ->builddetailtable()
                ->renderentitydata($reportingmodel)
        );

    }

    public function deleteAction($id)
    {

        Reportingmodel::delete($id);
        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Reportingmodel::delete()->where("id")->in($ids);

        return array('success' => true,
            'detail' => '');

    }

    public function testmail($id, $email)
    {
        return $this->testmailAction($id, $email);
    }
    public function testmailAction($id, $email)
    {
        return Reportingmodel::find($id, 1)
            ->addReceiver($email, "devups developer")
            ->sendMail([
                "activationcode" => "ddddd",
                "username" => "devups developer",
            ]);

    }

    public function loadContent($name)
    {
        // ETAPE H.4 : basename() - $name vient d'un parametre de route et
        // etait concatene sans controle dans un chemin lu par
        // file_get_contents() (voir TODO.md, "Audit require/include/
        // file_get_contents") : un '../../..' permettait de sortir de
        // Resource/partial/ (traversee de repertoire, lecture limitee aux
        // fichiers se terminant par "_<local>.html").
        $name = basename((string)$name);
        $local = local();
        $filename = Reportingmodel::classroot("Resource/partial/{$name}_$local.html");
        if (!file_exists($filename))
            return [
                "success" => false,
                "detail" => t("Le fichier :file n existe pas!", ["file" => $filename]),
            ];
        $content = file_get_contents($filename);
        return [
            "success" => true,
            "content" => $content,
            "detail" => t("Contenu enregistrer avec sucess"),
        ];
    }

    public function saveContent($id)
    {
        $local = local();
        $rm = Reportingmodel::find($id, Dvups_lang::getByIsoCode($local)->id);

        $filename = Reportingmodel::classroot("Resource/partial");
        // ETAPE H.4 : basename() par precaution, meme principe que
        // loadContent() ci-dessus ($rm->name vient de la base, pas
        // directement de la requete, mais reste un nom de modele saisi par
        // un admin - aucune raison d'y tolerer un separateur de chemin).
        \DClass\lib\Util::log($rm->content, basename($rm->name) . "_$local.html", $filename);

        return [
            "success" => true,
            "detail" => t("Contenu enregistrer avec sucess"),
        ];
    }

    public function downloadContent($id)
    {
        $local = local();
        $rm = Reportingmodel::find($id, Dvups_lang::getByIsoCode($local)->id);

        $fileName = "{$rm->name}_$local.html";

        header('Content-Type: text/html; charset=windows-1252');
        //header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$fileName\"");

        echo $rm->content;

        exit;
    }

}
