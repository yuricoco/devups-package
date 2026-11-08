<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE P.2 : les points d'entree historiques services.php?path=...
 * (switch, appel direct) appelaient des methodes statiques inexistantes
 * pour afficher un formulaire/detail (meme bug que
 * \DClass\lib\Util::getIp() dans Country.php, corrige dans ce meme commit) -
 * jamais exerce par un test jusqu'ici, donc jamais repere : la premiere
 * utilisation reelle aurait leve une Fatal error (Call to undefined
 * method). Un simple "pas de fatal / pas de 500" suffit ici : le contenu
 * HTML/JSON rendu par formView()/detailView() est deja couvert ailleurs
 * (AdminScreensTest, etc.) pour le chemin moderne (admin/api/.../form).
 */
final class LegacyServicesFormEndpointsTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function testDvupsAdminFormDoesNotFatal(): void
    {
        $id = \Dvups_admin::where('login', 'dv_admin')->firstOrNull()->getId();
        $res = $this->request('GET', 'src/devups/ModuleAdmin/services.php?path=dvups_admin.form&id=' . $id);
        $this->assertSame(200, $res['status'], $res['body']);
    }

    public function testDvupsRoleShowDoesNotFatal(): void
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-legacy-show' . uniqid(), 'alias' => 'x']);
        $res = $this->request('GET', 'src/devups/ModuleAdmin/services.php?path=dvups_role._show&id=' . $roleId);
        $this->assertSame(200, $res['status'], $res['body']);
    }

    public function testCmscontentFormDoesNotFatal(): void
    {
        $res = $this->request('GET', 'src/devupscms/ModuleCms/services.php?path=cmscontent._new');
        $this->assertSame(200, $res['status'], $res['body']);
    }

    public function testStatusFormDoesNotFatal(): void
    {
        $res = $this->request('GET', 'src/devupscms/ModuleData/services.php?path=status._new');
        $this->assertSame(200, $res['status'], $res['body']);
    }
}
