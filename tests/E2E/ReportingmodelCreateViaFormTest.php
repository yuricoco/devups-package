<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE A.4 (v7.1) : creer un Reportingmodel depuis l'ecran admin echouait
 * systematiquement ("Column 'type' cannot be null") - un essai informel
 * pendant l'ETAPE V avait produit une erreur client illisible
 * ("[object Object]"), non confirmee comme un bug a l'epoque (TODO.md).
 *
 * Root cause : ReportingmodelForm::buildForm() listait les gabarits depuis
 * "src/devupscms/ModuleReporting/Resource/views/models/", un dossier qui
 * n'a JAMAIS existe - les gabarits reels vivent dans "web/views/mails/"
 * (meme convention que ReportingmodelController::collectAction()/
 * ensureTemplateFile(), ETAPE M.4). $files restait donc toujours vide : le
 * champ radio "type" (colonne NOT NULL) se rendait sans AUCUNE option -
 * rien a cocher, donc aucune valeur "type" soumise, donc l'INSERT echouait
 * toujours au niveau base.
 */
final class ReportingmodelCreateViaFormTest extends HttpTestCase
{
    private ?int $reportingmodelId = null;

    protected function tearDown(): void
    {
        if ($this->reportingmodelId !== null) {
            $pdo = \Model::pdo();
            $pdo->exec("DELETE FROM reportingmodel_lang WHERE reportingmodel_id = " . $this->reportingmodelId);
            $pdo->exec("DELETE FROM reportingmodel WHERE id = " . $this->reportingmodelId);
        }
        parent::tearDown();
    }

    public function testFormBuildsAtLeastOneSelectableTypeOption(): void
    {
        require_once ROOT . 'src/devupscms/ModuleReporting/Form/ReportingmodelForm.php';

        $form = \ReportingmodelForm::init(new \Reportingmodel())->buildForm();

        $this->assertNotEmpty($form->fields['type']['options'], "le champ 'type' doit avoir au moins une option (gabarit reel dans web/views/mails/)");
        $this->assertNotEmpty($form->fields['type']['value'], "une option par defaut doit etre pre-selectionnee");
    }

    public function testCreatingAReportingmodelFromTheAdminScreenSucceeds(): void
    {
        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'admin/api/reportingmodel/create', [
            'reportingmodel_form' => [
                'type' => 'welcome.blade.php',
                'name' => 'etapea4_regression_reportingmodel',
                'title' => ['fr' => 'Titre FR', 'en' => 'Title EN'],
                'object' => ['fr' => 'Objet FR', 'en' => 'Object EN'],
                'description' => 'Modele de test ETAPE A.4',
                'contenttext' => ['fr' => 'Contenu FR', 'en' => 'Content EN'],
            ],
        ]);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur reportingmodel/create :\n" . implode("\n", $errors));

        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "creation echouee :\n" . $res['body']);
        $this->reportingmodelId = (int)$data['reportingmodel']['id'];

        // jsonSerialize() de Reportingmodel n'expose pas 'type' - verifie
        // directement en base que la valeur a bien ete reduite a son nom
        // sans extension (Reportingmodel::setType()).
        $type = \Model::pdo()->query("SELECT type FROM reportingmodel WHERE id = " . $this->reportingmodelId)->fetchColumn();
        $this->assertSame('welcome', $type, "'type' doit etre reduit a son nom sans extension");
    }
}
