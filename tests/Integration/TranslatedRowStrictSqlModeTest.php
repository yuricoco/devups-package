<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE F.2 (v7.1) - non-regression CI : la suite tourne desormais en
 * sql_mode strict (tests/bootstrap.php, le defaut reel de MySQL 8, celui de
 * la CI GitHub) plutot que le mode tolerant de MariaDB utilise en local -
 * ce qui a revele un bug invisible en local uniquement.
 *
 * `DBAL::createDbal()`/`_createDbal()` omettaient entierement une colonne
 * traduite jamais fournie par l'appelant (`Tree::create(['name' => ...])`
 * sans `label`) du tableau transmis a l'INSERT de la ligne `_lang` - la
 * colonne manquait donc de l'INSERT lui-meme, jamais d'un NULL explicite.
 * Sous sql_mode tolerant, MySQL/MariaDB complete silencieusement par la
 * valeur par defaut du type (chaine vide) ; sous sql_mode strict
 * (STRICT_TRANS_TABLES), toute colonne NOT NULL sans valeur par defaut et
 * absente de l'INSERT leve une erreur 1364 ("doesn't have a default
 * value") - exactement l'echec observe sur la CI, jamais en local avant ce
 * correctif.
 */
final class TranslatedRowStrictSqlModeTest extends DatabaseTestCase
{
    public function testCreatingATranslatedEntityWithoutAnyTranslatedColumnNeverErrorsUnderStrictSqlMode(): void
    {
        $id = \Tree::create(['name' => 'etapef2_no_label_at_all']);

        $tree = \Tree::find($id);
        $this->assertSame($id, $tree->getId());

        $rows = \Model::pdo()
            ->query("SELECT label FROM tree_lang WHERE tree_id = $id")
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertNotEmpty($rows, "une ligne tree_lang doit exister par langue active, meme sans label fourni");
        foreach ($rows as $label) {
            $this->assertSame('', $label, "a defaut de valeur fournie pour cette langue ou pour la langue par defaut, la colonne traduite doit etre une chaine vide - jamais omise de l'INSERT");
        }
    }

    public function testCreatingATranslatedEntityWithOnlyOneLanguageFallsBackToTheDefaultLanguageNeverTheOtherLanguage(): void
    {
        $defaultIso = \Dvups_lang::defaultLang()->getIso_code();
        $otherIso = $defaultIso === 'fr' ? 'en' : 'fr';

        $id = \Tree::create(['name' => 'etapef2_default_lang_fallback', 'label' => [$defaultIso => 'Valeur par defaut']]);

        $defaultLangId = \Dvups_lang::where('this.iso_code', '=', $defaultIso)->first()->getId();
        $otherLangId = \Dvups_lang::where('this.iso_code', '=', $otherIso)->first()->getId();

        $pdo = \Model::pdo();
        $defaultLabel = $pdo->query("SELECT label FROM tree_lang WHERE tree_id = $id AND lang_id = $defaultLangId")->fetchColumn();
        $otherLabel = $pdo->query("SELECT label FROM tree_lang WHERE tree_id = $id AND lang_id = $otherLangId")->fetchColumn();

        $this->assertSame('Valeur par defaut', $defaultLabel);
        $this->assertSame('Valeur par defaut', $otherLabel, "une langue sans valeur fournie reprend celle de la langue par defaut, pas une chaine vide tant que la langue par defaut en a une");
    }
}
