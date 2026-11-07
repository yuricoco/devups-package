<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * B.8 : Emaillog::logSuccess()/logFailure() (nouveau schema -
 * destinataire/sujet/modele/statut/erreur, voir B.5) enregistrent un
 * envoi reussi et un envoi en echec ; Log:: (dclass/devups/Log.php)
 * n'ecrit JAMAIS dans emaillog - separation stricte des deux journaux
 * (voir README.md#Logs).
 */
final class EmaillogTest extends DatabaseTestCase
{
    public function testLogSuccessRecordsASentEmail(): void
    {
        \Emaillog::logSuccess('sujet de test', ['dest@example.test' => 'Destinataire'], 'mails.welcome');

        $row = \Emaillog::where('this.sujet', 'sujet de test')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame(\Emaillog::STATUT_ENVOYE, $row->statut);
        $this->assertStringContainsString('dest@example.test', $row->destinataire);
        $this->assertSame('mails.welcome', $row->modele);
        $this->assertNull($row->erreur);
    }

    public function testLogFailureRecordsAFailedEmailWithItsError(): void
    {
        \Emaillog::logFailure('sujet en echec', ['dest@example.test' => 'Destinataire'], 'SMTP connection failed');

        $row = \Emaillog::where('this.sujet', 'sujet en echec')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame(\Emaillog::STATUT_ECHEC, $row->statut);
        $this->assertSame('SMTP connection failed', $row->erreur);
    }

    public function testDestinataireAcceptsAPlainStringToo(): void
    {
        \Emaillog::logSuccess('sujet chaine simple', 'dest2@example.test');

        $row = \Emaillog::where('this.sujet', 'sujet chaine simple')->firstOrNull();
        $this->assertSame('dest2@example.test', $row->destinataire);
    }

    public function testLogNeverWritesToEmaillog(): void
    {
        \Log::error('erreur qui ne doit jamais finir dans emaillog');

        $this->assertSame(0, (int)\Emaillog::where('this.erreur', 'like', '%ne doit jamais finir%')->count());
        $this->assertSame(0, (int)\Emaillog::where('this.sujet', 'like', '%ne doit jamais finir%')->count());
    }

    public function testEmaillogNeverWritesToDvupsLog(): void
    {
        \Emaillog::logFailure('sujet separation', 'dest3@example.test', 'erreur separation emaillog/log');

        $this->assertSame(0, (int)\Dvups_log::where('this.message', 'like', '%separation emaillog%')->count());
    }
}
