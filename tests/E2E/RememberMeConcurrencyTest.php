<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE L.2 : le token "remember me" ne doit etre RENOUVELE (rotation) que
 * lorsque la session est RESTAUREE depuis le cookie (restartsessionAction()),
 * jamais quand une session est deja active - restartsessionAction() (voir
 * admin/header.php, appele a chaque requete admin) retourne immediatement
 * si $_SESSION[ADMIN] est deja presente, avant toute verification/rotation
 * du token. Ce test verifie le comportement observable : une rafale de
 * requetes paralleles sur une session deja active ne doit jamais provoquer
 * de deconnexion (chaque requete doit rester authentifiee).
 */
final class RememberMeConcurrencyTest extends HttpTestCase
{
    public function testConcurrentRequestsWithActiveSessionNeverLogOut(): void
    {
        $login = $this->request('POST', 'admin/devups/ModuleAdmin/dvups-admin/connexion-action', [
            'login' => 'dv_admin',
            'password' => 'admin',
            'remember_me' => 1,
        ]);
        $this->assertSame(302, $login['status']);
        $this->assertStringNotContainsString('login.php', (string)$login['location']);

        $results = $this->parallelGet([
            'admin/dashboard',
            'admin/dashboard',
            'admin/dashboard',
        ]);

        $this->assertCount(3, $results);
        foreach ($results as $i => $res) {
            $this->assertSame(200, $res['status'], "requete parallele #$i : doit rester connectee (pas de redirection vers le login)");
            $this->assertStringContainsString('</html>', $res['body'], "requete parallele #$i : doit rendre le back-office, pas une page de connexion");
        }

        // la session doit rester active APRES la rafale, elle aussi.
        $after = $this->get('admin/dashboard');
        $this->assertSame(200, $after['status']);
        $this->assertStringContainsString('</html>', $after['body']);

        $this->assertNoServerError('rafale parallele sur une session active');
    }
}
