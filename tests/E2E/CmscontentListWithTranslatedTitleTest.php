<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * creer un Cmscontent (titre traduit fr/en, stocke en tableau par langue
 * via le mecanisme dvtranslate) declenchait un "Warning: Array to string
 * conversion" dans Datatable.php (ligne ~1183) et affichait "Array" dans
 * la colonne Titre, des que buildindextable() liste une colonne SANS cle
 * "value" explicite pointant vers une colonne de dvtranslated_columns
 * (ex. CmscontentTable::buildindextable() -> 'title', et de la meme facon
 * NotificationtypeTable::buildindextable() -> 'content').
 *
 * Le chemin get<Value>() plus bas dans la meme boucle savait deja resoudre
 * ce cas ($tdcontent[$iso_code]) - seul le chemin "attribut direct" (pas
 * de 'value' du tout) ne le faisait pas. Corrige a la source dans
 * Datatable.php en reutilisant la meme resolution par $iso_code.
 *
 * AdminScreensTest::screens() ne le voyait jamais : la table `cmscontent`
 * y est toujours VIDE (aucun seed), donc la boucle de rendu par ligne ne
 * s'executait jamais - meme raison que CountryListWithDataTest.
 */
final class CmscontentListWithTranslatedTitleTest extends HttpTestCase
{
    private ?int $cmscontentId = null;

    protected function tearDown(): void
    {
        if ($this->cmscontentId !== null) {
            $pdo = \Model::pdo();
            $pdo->exec("DELETE FROM cmscontent_lang WHERE cmscontent_id = " . $this->cmscontentId);
            $pdo->exec("DELETE FROM cmscontent WHERE id = " . $this->cmscontentId);
        }
        parent::tearDown();
    }

    public function testListRendersTheTranslatedTitleWithoutArrayToStringWarning(): void
    {
        $pdo = \Model::pdo();
        $pdo->exec("INSERT INTO cmscontent (reference, active, position) VALUES ('etape_v4_regression_test', 0, 0)");
        $this->cmscontentId = (int)$pdo->lastInsertId();

        $frLangId = (int)$pdo->query("SELECT id FROM dvups_lang WHERE iso_code = 'fr'")->fetchColumn();
        $enLangId = (int)$pdo->query("SELECT id FROM dvups_lang WHERE iso_code = 'en'")->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO cmscontent_lang (cmscontent_id, lang_id, title, content, sommary) VALUES (?, ?, ?, '', '')");
        $stmt->execute([$this->cmscontentId, $frLangId, 'Titre FR regression test']);
        $stmt->execute([$this->cmscontentId, $enLangId, 'Title EN regression test']);

        $this->loginAdmin();
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->get('admin/devupscms/ModuleCms/cmscontent/list');
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status']);
        $this->assertSame([], $errors, "erreurs PHP sur cmscontent/list :\n" . implode("\n", $errors));
        $this->assertStringNotContainsString('Array to string conversion', $res['body']);
        $this->assertStringContainsString('Titre FR regression test', $res['body'], 'le titre traduit doit etre affiche, pas "Array"');
        $this->assertStringNotContainsString('<td>Array</td>', $res['body']);
    }
}
