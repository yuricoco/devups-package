<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE M.4 (Phase 6, Reporting) : bouton "Collecter les modeles"
 * (ReportingmodelController::collectAction()) et son sens inverse
 * (createAction() genere le fichier gabarit), plus l'apercu avec un
 * payload d'exemple (previewAction()).
 */
final class ReportingmodelCollectTest extends DatabaseTestCase
{
    /** @var string[] fichiers crees pendant le test, supprimes au tearDown */
    private array $filesToClean = [];

    protected function tearDown(): void
    {
        foreach ($this->filesToClean as $file)
            @unlink($file);
        parent::tearDown();
    }

    private function mailsDir(): string
    {
        return ROOT . 'web/views/mails/';
    }

    public function testCollectActionCreatesAMissingReportingmodelFromAFile(): void
    {
        $slug = 'phpunit_' . uniqid();
        $file = $this->mailsDir() . $slug . '.blade.php';
        file_put_contents($file, '<h1>{{ $title ?? "" }}</h1>');
        $this->filesToClean[] = $file;

        $result = (new \ReportingmodelController())->collectAction();

        $this->assertTrue($result['success']);
        $createdNames = array_map(fn($rm) => $rm->getName(), $result['created']);
        $this->assertContains('mails.' . $slug, $createdNames);

        $row = \Reportingmodel::where('reportingmodel.name', 'mails.' . $slug)->firstOrNull();
        $this->assertNotNull($row->getId());
        $this->assertSame('email', $row->getType());
    }

    public function testCollectActionNeverCreatesDuplicatesOnASecondRun(): void
    {
        $slug = 'phpunit_' . uniqid();
        $file = $this->mailsDir() . $slug . '.blade.php';
        file_put_contents($file, '<p>test</p>');
        $this->filesToClean[] = $file;

        (new \ReportingmodelController())->collectAction();
        (new \ReportingmodelController())->collectAction();

        $count = \Reportingmodel::where('reportingmodel.name', 'mails.' . $slug)->count();
        $this->assertSame(1, $count);
    }

    public function testCollectActionNeverDeletesAnExistingReportingmodel(): void
    {
        $slug = 'phpunit_' . uniqid();
        \Reportingmodel::create(['name' => 'mails.' . $slug, 'type' => 'email']);
        // le fichier correspondant n'existe PAS sur le disque.

        (new \ReportingmodelController())->collectAction();

        $row = \Reportingmodel::where('reportingmodel.name', 'mails.' . $slug)->firstOrNull();
        $this->assertNotNull($row->getId(), 'collectAction() ne doit jamais supprimer un modele existant, meme sans fichier correspondant');
    }

    public function testCreateActionGeneratesTheMissingTemplateFile(): void
    {
        $slug = 'phpunit_' . uniqid();
        $expectedFile = $this->mailsDir() . $slug . '.blade.php';
        $this->filesToClean[] = $expectedFile;
        $this->assertFileDoesNotExist($expectedFile);

        $_POST['reportingmodel_form'] = null;
        $ctrl = new \ReportingmodelController();
        $reportingmodel = $this->hydrateReportingmodel($ctrl, 'mails.' . $slug);

        $this->invokeEnsureTemplateFile($reportingmodel);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('mails.' . $slug, file_get_contents($expectedFile));
    }

    public function testCreateActionNeverOverwritesAnExistingTemplateFile(): void
    {
        $slug = 'phpunit_' . uniqid();
        $file = $this->mailsDir() . $slug . '.blade.php';
        file_put_contents($file, 'CONTENU ORIGINAL');
        $this->filesToClean[] = $file;

        $reportingmodel = $this->hydrateReportingmodel(new \ReportingmodelController(), 'mails.' . $slug);
        $this->invokeEnsureTemplateFile($reportingmodel);

        $this->assertSame('CONTENU ORIGINAL', file_get_contents($file));
    }

    public function testPreviewActionRendersTheTemplateWithSampleData(): void
    {
        $id = \Reportingmodel::create(['name' => 'mails.otp', 'type' => 'email']);

        $result = (new \ReportingmodelController())->previewAction($id);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('payload', $result);
        $this->assertNotEmpty($result['html']);
    }

    public function testPreviewActionFailsCleanlyForAnUnknownReportingmodel(): void
    {
        $result = (new \ReportingmodelController())->previewAction(999999);
        $this->assertFalse($result['success']);
    }

    private function hydrateReportingmodel(\ReportingmodelController $ctrl, string $name): \Reportingmodel
    {
        $id = \Reportingmodel::create(['name' => $name, 'type' => 'email']);
        return \Reportingmodel::find($id);
    }

    private function invokeEnsureTemplateFile(\Reportingmodel $reportingmodel): void
    {
        $method = new \ReflectionMethod(\ReportingmodelController::class, 'ensureTemplateFile');
        $method->setAccessible(true);
        $method->invoke(null, $reportingmodel);
    }
}
