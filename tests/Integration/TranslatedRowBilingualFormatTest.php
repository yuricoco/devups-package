<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE C.2 (v7.1) - non-regression : le correctif de initSelect()
 * (INNER JOIN -> LEFT JOIN vers dvups_lang, pour que les lignes SANS
 * ligne "_lang" restent visibles - voir TranslatedRowWithoutLangRowTest.php)
 * ne doit RIEN changer pour une ligne qui A des traductions - le format
 * bilingue {iso => valeur, ...} reconstruit par ModelTrait::parseLang()
 * depuis le GROUP_CONCAT doit rester identique.
 */
final class TranslatedRowBilingualFormatTest extends DatabaseTestCase
{
    public function testARowWithTranslationsInBothLanguagesKeepsTheBilingualFormat(): void
    {
        $id = \Tree::create(['name' => 'etapec2_bilingual', 'label' => ['fr' => 'Libelle FR', 'en' => 'Label EN']]);

        $tree = \Tree::find($id);

        $this->assertIsArray($tree->label);
        $this->assertSame('Libelle FR', $tree->label['fr']);
        $this->assertSame('Label EN', $tree->label['en']);

        $rows = \Tree::where('this.id', '=', $id)->get();
        $this->assertSame('Libelle FR', $rows[0]->label['fr']);
        $this->assertSame('Label EN', $rows[0]->label['en']);
    }

    public function testARowTranslatedInOnlyOneLanguageNeverErrors(): void
    {
        $id = \Tree::create(['name' => 'etapec2_partial']);
        $pdo = \Model::pdo();
        $pdo->exec("DELETE FROM tree_lang WHERE tree_id = $id");
        $frId = \Dvups_lang::where('this.iso_code', '=', 'fr')->first()->getId();
        $pdo->exec("INSERT INTO tree_lang (tree_id, lang_id, label) VALUES ($id, $frId, 'Seulement FR')");

        $tree = \Tree::find($id);

        $this->assertIsArray($tree->label);
        $this->assertSame('Seulement FR', $tree->label['fr'] ?? null);

        $rows = \Tree::where('this.id', '=', $id)->get();
        $this->assertSame($id, $rows[0]->getId());
    }

    /**
     * ETAPE C.3 - D3 : QueryBuilder::get($col, $id_lang) posait la
     * langue APRES initSelect() - $id_lang n'avait donc jamais d'effet,
     * la requete restait toujours en mode bilingue (GROUP_CONCAT), meme
     * en demandant explicitement une langue.
     */
    public function testGetWithAnExplicitIdLangReturnsAPlainStringNotTheBilingualArray(): void
    {
        $id = \Tree::create(['name' => 'etapec3_explicit_lang', 'label' => ['fr' => 'Libelle FR', 'en' => 'Label EN']]);
        $frId = \Dvups_lang::where('this.iso_code', '=', 'fr')->first()->getId();

        $rows = \Tree::select()->where('this.id', '=', $id)->get('*', $frId);

        $this->assertSame('Libelle FR', $rows[0]->label, "avec un id_lang explicite, label doit etre une chaine simple (langue fixee), jamais le tableau bilingue");
    }
}
