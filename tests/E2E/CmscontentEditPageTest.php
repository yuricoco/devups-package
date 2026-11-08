<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * Phase 4 (CMS), ETAPE G.3 : la page d'edition admin d'un cmscontent
 * fonctionne (/admin/devupscms/ModuleCms/cmscontent/edit?id=1) -
 * cassee avant cette etape par un bloc Blade malforme (accolade
 * d'ouverture manquante) dependant de ModuleGallery/Dv_image, corrige
 * en le repointant vers CmsresourceTable (voir
 * Resource/views/cmscontent/form.blade.php).
 */
final class CmscontentEditPageTest extends HttpTestCase
{
    public function testEditPageRendersWithoutError(): void
    {
        $this->loginAdmin();

        $id = \Cmscontent::create(['reference' => 'e2e-edit-ref']);

        $res = $this->get('admin/devupscms/ModuleCms/cmscontent/edit?id=' . $id);

        $this->assertSame(200, $res['status']);
        $this->assertNoServerError('sur la page d\'edition cmscontent');
        $this->assertStringContainsString('</html>', $res['body'], 'rendu tronque');

        \Cmscontent::delete($id);
    }
}
