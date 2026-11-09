<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE E.3 (v7.1) - Router::manageServe() : le garde de session
 * (if (!isset($_SESSION[ADMIN])) { ... }) n'avait qu'un corps VIDE
 * (commentaire mort) - manageServe() lui-meme ne refusait RIEN. Certains
 * appelants (AdminService) avaient leur propre garde dans leur
 * constructeur, et admin/header.php garde deja les points d'entree
 * historiques par nom de script/chemin (voir AdminSecurityTest) - mais
 * Admin, ModuleConfig, ModuleLog, ModuleUser (et tout futur module genere
 * sur le meme patron) n'avaient AUCUN garde propre a ce point d'entree
 * partage par toutes ces classes.
 *
 * Verifie dans un processus PHP separe (bypass volontaire d'admin/header.php,
 * pour isoler le garde du Router lui-meme - voir
 * tests/Support/scripts/trigger_manageserve_guard.php) avec ModuleConfig,
 * une classe sans aucun garde propre.
 */
final class ManageServeSessionGuardTest extends DatabaseTestCase
{
    private function runTrigger(string $pathInfo, bool $withSession): string
    {
        $script = realpath(__DIR__ . '/../Support/scripts/trigger_manageserve_guard.php');
        $process = proc_open(
            [PHP_BINARY, $script, $pathInfo, $withSession ? '1' : '0'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath(__DIR__ . '/../..')
        );
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stdout;
    }

    public function testManageServeRefusesAnAnonymousCallWithoutAnySessionGuardOfItsOwn(): void
    {
        $stdout = $this->runTrigger('/api/hello', false);

        $data = json_decode($stdout, true);
        $this->assertIsArray($data, "reponse JSON attendue, recu :\n$stdout");
        $this->assertFalse($data['success']);
        $this->assertSame('admin session required', $data['detail']);
    }

    public function testManageServeStillServesTheRequestWithAnAdminSession(): void
    {
        $stdout = $this->runTrigger('/api/hello', true);

        $data = json_decode($stdout, true);
        $this->assertIsArray($data, "reponse JSON attendue, recu :\n$stdout");
        $this->assertTrue($data['success']);
    }

    public function testConnexionActionStaysExemptEvenThroughManageServe(): void
    {
        // connexion-action ne passe normalement jamais par manageServe()
        // (il passe par Router::manage(), voir le commentaire
        // d'AdminService) - verifie neanmoins que le garde ne le bloque
        // pas lui-meme, par coherence avec l'exemption de manage().
        $stdout = $this->runTrigger('/api/connexion-action', false);

        $this->assertStringNotContainsString('admin session required', $stdout);
    }
}
