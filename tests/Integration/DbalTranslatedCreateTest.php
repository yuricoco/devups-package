<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE I.4 : DBAL::_createDbal() (chemin statique Model::create(), utilise
 * par Cmscontent::create() entre autres) cree une ligne "_lang" par langue
 * ACTIVE. Avant ce correctif, le code appelait Dvups_lang::all() - une
 * methode RETIREE a l'ETAPE E.7 (remplacee par le cache
 * Dvups_lang::cachedActiveDlangs()) et jamais exercee par aucun test avant
 * l'ETAPE G (voir TODO.md, "Dvups_lang::readCache() ... decouvert et
 * corrige en testant") : Dvups_lang::all() avait ete reintroduite comme
 * correctif minimal a ce moment-la, au lieu du cache attendu. Ce test
 * verifie que c'est bien desormais le cache des langues ACTIVES qui pilote
 * la creation des lignes de traduction - pas toutes les langues existantes.
 */
final class DbalTranslatedCreateTest extends DatabaseTestCase
{
    public function testCreatesOneTranslationRowPerActiveLanguage(): void
    {
        $activeLangs = \Dvups_lang::cachedActiveDlangs();
        $activeIsos = array_values(array_filter(array_keys($activeLangs), fn($iso) => is_string($iso) && substr($iso, -1) !== '/'));

        $id = \Cmscontent::create(['reference' => 'dbal-active-ref']);

        $rows = \Model::pdo()->query('SELECT lang_id FROM cmscontent_lang WHERE cmscontent_id = ' . (int)$id)->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertCount(count($activeIsos), $rows, 'une ligne cmscontent_lang doit etre creee par langue ACTIVE, ni plus ni moins');

        foreach ($activeIsos as $iso)
            $this->assertContains($activeLangs[$iso], $rows, "la langue active '$iso' doit avoir sa ligne de traduction");
    }

    public function testDoesNotCreateATranslationRowForAnInactiveLanguage(): void
    {
        $inactiveId = \Dvups_lang::create([
            'name' => 'Deutsch (test)',
            'main' => 0,
            'active' => 0,
            'iso_code' => 'de',
            'language_code' => 'de',
            'locale' => 'de',
            'date_format_lite' => 'd/m/Y',
            'date_format_full' => 'd/m/Y H:i:s',
        ]);

        $id = \Cmscontent::create(['reference' => 'dbal-inactive-ref']);

        $rows = \Model::pdo()->query('SELECT lang_id FROM cmscontent_lang WHERE cmscontent_id = ' . (int)$id)->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertNotContains($inactiveId, $rows, "une langue INACTIVE ('de') ne doit jamais recevoir de ligne de traduction");

        \Dvups_lang::find($inactiveId)->__delete();
    }
}
