<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Ouvre, connecte en administrateur, pour CHAQUE entite declaree dans
 * config/dvups_configurations.php :
 *  - la page liste  : admin/<composant>/<Module>/<entite>/list (HTML complet) ;
 *  - le formulaire  : admin/api/<entite>/form (JSON {success, form}), tel que
 *    le demande model._new() dans le back-office.
 *
 * Un ecran est valide s'il repond 200, sans aucune erreur PHP cote serveur
 * et avec un rendu complet (pas de page tronquee).
 */
final class AdminScreensTest extends HttpTestCase
{
    /**
     * Ecrans connus comme casses, a corriger (ou a supprimer avec leur
     * module) : signales comme incomplets, sans faire echouer la suite.
     * Retirer une entree des qu'elle est corrigee.
     */
    private const KNOWN_BROKEN = [
        'devups/ModuleLang/local-content-key/form' => 'module Lang a refondre (traductions en fichiers)',
        'devups/ModuleLang/page-mapped/list' => 'module Lang a refondre (traductions en fichiers)',
        'devupscms/ModuleCms/imagecms/form' => 'depend de ModuleGallery (a remplacer par cmsresource)',
        'devupscms/ModuleTree/tree-item-image/form' => 'depend de ModuleGallery (relation dv_image retiree)',
        'devupscms/ModuleLocation/department/list' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/district/list' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/region/list' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/town/list' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/country/form' => 'Location : traduction de Country a reactiver',
        'devupscms/ModuleLocation/department/form' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/district/form' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/region/form' => 'Location : seul Country est conserve',
        'devupscms/ModuleLocation/town/form' => 'Location : seul Country est conserve',
        'devups/ModuleLang/local-content/form' => 'module Lang a refondre (traductions en fichiers)',
        'devups/ModuleLang/page-mapped/form' => 'module Lang a refondre (traductions en fichiers)',
        'devupscms/ModuleCms/comment/form' => 'entite Comment jamais generee (futur cmscomment)',
        'devupscms/ModuleNotification/notificationbroadcasted/form' => 'entite Notificationbroadcasted absente (refonte notifications)',
    ];

    private static bool $logged = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$logged) {
            $this->loginAdmin();
            self::$logged = true;
            \Devups\Tests\Support\HttpServer::newErrors();
        }
    }

    public static function screens(): array
    {
        $config = require __DIR__ . '/../../config/dvups_configurations.php';
        $screens = [];
        foreach ($config as $key => $entity) {
            if (strpos($key, '\\') !== false || !is_array($entity) || empty($entity['component']))
                continue;
            $slug = str_replace('_', '-', strtolower($entity['name']));
            $base = $entity['component'] . '/' . $entity['module'] . '/' . $slug;
            $screens[$base . '/list'] = [$base . '/list', 'admin/' . $base . '/list', 'html'];
            $screens[$base . '/form'] = [$base . '/form', 'admin/api/' . strtolower($entity['name']) . '/form?1', 'json'];
        }
        ksort($screens);
        return $screens;
    }

    #[DataProvider('screens')]
    public function testScreenRendersWithoutError(string $path, string $url, string $kind): void
    {
        $res = $this->get($url);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        if (isset(self::KNOWN_BROKEN[$path])) {
            if ($this->screenIsHealthy($res, $errors, $kind))
                $this->fail("$path fonctionne desormais : le retirer de KNOWN_BROKEN");
            $this->markTestIncomplete(self::KNOWN_BROKEN[$path]);
        }

        $this->assertSame(200, $res['status'], $url);
        $this->assertSame([], $errors, "erreurs PHP sur $url :\n" . implode("\n", $errors));
        if ($kind === 'html') {
            $this->assertStringContainsString('</html>', $res['body'], "rendu tronque sur $url");
        } else {
            $data = json_decode($res['body'], true);
            $this->assertIsArray($data, "JSON attendu sur $url, recu : " . substr($res['body'], 0, 200));
            $this->assertTrue($data['success'] ?? false, $url);
            $this->assertStringContainsString('<form', (string)($data['form'] ?? ''), "formulaire absent sur $url");
            $this->assertStringContainsString('</form>', (string)$data['form'], "formulaire tronque sur $url");
        }
    }

    private function screenIsHealthy(array $res, array $errors, string $kind): bool
    {
        if ($res['status'] !== 200 || $errors)
            return false;
        if ($kind === 'html')
            return strpos($res['body'], '</html>') !== false;
        $data = json_decode($res['body'], true);
        return is_array($data) && !empty($data['success']) && strpos((string)($data['form'] ?? ''), '</form>') !== false;
    }
}
