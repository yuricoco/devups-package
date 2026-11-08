<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * le panneau d'aide "SMTP CONFIGURATION" sur admin/devupscms/
 * ModuleReporting/reportingmodel/list (et emaillog/list, meme layout
 * partage) affichait en clair, a tout admin connecte, le VRAI mot de
 * passe SMTP configure (config/constante.php, sm_password) via
 * {{sm_password}} dans layout.blade.php - pas un exemple generique.
 * Masque desormais ('********'), le reste du panneau (host/port/from)
 * n'etant pas sensible et reste affiche pour l'aide integree.
 */
final class ReportingmodelListDoesNotLeakSmtpPasswordTest extends HttpTestCase
{
    public function testTheRealSmtpPasswordIsNeverRenderedOnTheReportingmodelListScreen(): void
    {
        $this->loginAdmin();

        $res = $this->get('admin/devupscms/ModuleReporting/reportingmodel/list');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString("sm_password', '********'", $res['body']);
        // n'affirme la non-fuite que si un mot de passe reel est configure
        // localement (sm_password peut etre vide en environnement de test) -
        // ne jamais imprimer sa valeur, seulement verifier son absence.
        if (sm_password !== '') {
            $this->assertStringNotContainsString(sm_password, $res['body'], "le vrai mot de passe SMTP ne doit jamais apparaitre a l'ecran");
        }
    }
}
