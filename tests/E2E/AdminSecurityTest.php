<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aucune ecriture du back-office ne doit etre possible sans session
 * d'administration, ni par l'API d'administration, ni par les points
 * d'entree historiques src/<composant>/<Module>/services.php, ni par les
 * index.php des modules back-office (devups, devupscms). Le composant
 * public devupsfront (inscription, connexion, OAuth) n'est pas concerne :
 * son index.php reste joignable en anonyme, protege par sa propre
 * authentification.
 */
final class AdminSecurityTest extends HttpTestCase
{
    public static function legacyServices(): array
    {
        $root = realpath(__DIR__ . '/../..');
        $cases = [];
        foreach (glob($root . '/src/*/*/services.php') as $file) {
            $rel = substr($file, strlen($root) + 1);
            $cases[$rel] = [$rel];
        }
        return $cases;
    }

    #[DataProvider('legacyServices')]
    public function testLegacyServicesRequireAnAdminSession(string $service): void
    {
        $before = $this->countAllRows();
        $res = $this->request('POST', $service . '?path=anything.create', ['x' => 'y']);
        $this->assertSame(401, $res['status'], "$service doit refuser un visiteur anonyme");
        $this->assertSame($before, $this->countAllRows(), "$service a ecrit en base sans authentification");
    }

    public function testBackofficeModuleIndexRequiresAnAdminSession(): void
    {
        $root = realpath(__DIR__ . '/../..');
        $files = array_merge(glob($root . '/src/devups/*/index.php'), glob($root . '/src/devupscms/*/index.php'));
        if (!$files)
            $this->markTestSkipped('aucun index.php de module back-office a ce jour');

        foreach ($files as $file) {
            $rel = substr($file, strlen($root) + 1);
            $res = $this->get($rel);
            $this->assertSame(401, $res['status'], "$rel doit refuser un visiteur anonyme");
            $res = $this->get($rel . '/api/hello');
            $this->assertSame(401, $res['status'], "$rel (/api/) doit refuser un visiteur anonyme");
        }
    }

    public function testAdminApiRequiresAnAdminSession(): void
    {
        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_ANON', '_value' => 'x'],
        ]);
        $this->assertSame(401, $res['status']);
        $this->assertSame(0, (int)\Configuration::where('this._key', 'E2E_ANON')->count());
    }

    public function testAdminApiCrudWithSession(): void
    {
        $this->loginAdmin();

        $res = $this->request('POST', 'admin/api/configuration/create', [
            'configuration_form' => ['_key' => 'E2E_CRUD', '_value' => 'v1', '_type' => 'string', 'comment' => 'e2e'],
        ]);
        $data = $this->json($res);
        $this->assertTrue($data['success'], $res['body']);
        $id = $data['configuration']['id'];
        $this->assertSame('v1', \Configuration::get('E2E_CRUD'));

        $res = $this->request('POST', 'admin/api/configuration/update?id=' . $id, [
            'configuration_form' => ['_key' => 'E2E_CRUD', '_value' => 'v2', '_type' => 'string', 'comment' => 'e2e'],
        ]);
        $this->assertTrue($this->json($res)['success'], $res['body']);
        $this->assertSame('v2', \Configuration::get('E2E_CRUD'));

        $res = $this->request('POST', 'admin/api/configuration/delete?id=' . $id);
        $this->assertTrue($this->json($res)['success'] ?? false, $res['body']);
        $this->assertNull(\Configuration::get('E2E_CRUD'));
        $this->assertNoServerError('CRUD configuration');
    }

    private function countAllRows(): int
    {
        $total = 0;
        $tables = \Model::pdo()->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table)
            $total += (int)\Model::pdo()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        return $total;
    }
}
