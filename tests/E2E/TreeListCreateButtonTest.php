<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * cliquer sur "Creer" sur admin/devupscms/ModuleTree/tree/list envoyait
 * GET /admin/api/null/form (l'entite "Tree" devenait litteralement la
 * chaine "null" dans l'URL), avec en cascade 3 warnings PHP dans
 * Router.php (cle de tableau "Null" indefinie, $function indefinie) et un
 * dd() de debug visible dans le dialogue au lieu du formulaire.
 *
 * Cause : TreeTable::init($tree = null) appelait "new TreeTable()" SANS
 * JAMAIS passer $tree au constructeur (meme quand $tree n'etait pas
 * null), alors que Datatable::__construct() ne calcule $this->class (lu
 * par le bouton "Creer" pour batir l'URL de creation) QUE si une entite
 * est passee au constructeur - c'est le meme bug deja corrige cette
 * session pour CountryTable::init(). Verifie ici que le bouton "Creer"
 * du listing Tree cible bien l'entite "tree", pas une chaine vide/null.
 */
final class TreeListCreateButtonTest extends HttpTestCase
{
    public function testCreateButtonTargetsTheTreeEntityNotNull(): void
    {
        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->get('admin/devupscms/ModuleTree/tree/list');
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status']);
        $this->assertSame([], $errors, "erreurs PHP sur tree/list :\n" . implode("\n", $errors));
        $this->assertStringContainsString("model._new(this, 'tree')", $res['body'], 'le bouton Creer doit cibler l\'entite "tree"');
        $this->assertStringNotContainsString("model._new(this, '')", $res['body']);
    }

    public function testCreateFormLoadsTheRealTreeFormNotA404Dump(): void
    {
        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->get('admin/api/tree/form?1');
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status']);
        $this->assertSame([], $errors, "erreurs PHP sur admin/api/tree/form :\n" . implode("\n", $errors));
        $this->assertStringNotContainsString('You may create method', $res['body']);

        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "formulaire tree non charge :\n" . $res['body']);
    }
}
