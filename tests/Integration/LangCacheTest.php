<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * E.7 : Dvups_lang::cachedActiveDlangs() remplace une requete
 * Dvups_lang::all() a chaque requete (header.php) par un fichier compile
 * (cache/lang/active.php), invalide automatiquement a chaque creation/
 * modification/suppression d'une langue - __insert()/__update()/__delete()
 * en INSTANCE (le chemin reellement suivi par le back-office, cf.
 * CrudTrait::createAction()/updateAction()/deleteAction()). La methode
 * statique Dvups_lang::create() (raccourci utilise par les fixtures de
 * test ailleurs dans la suite) passe par DBAL::_createDbal() directement
 * et ne declenche PAS ces surcharges - volontairement pas utilisee ici.
 */
final class LangCacheTest extends DatabaseTestCase
{
    private ?int $langId = null;

    protected function tearDown(): void
    {
        if ($this->langId !== null) {
            \Dvups_lang::find($this->langId)->__delete();
            $this->langId = null;
        }
        parent::tearDown();
    }

    private function makeInstance(string $iso): \Dvups_lang
    {
        $lang = new \Dvups_lang();
        $lang->setName('Test');
        $lang->setMain(0);
        $lang->setActive('1');
        $lang->setIso_code($iso);
        $lang->setLanguage_code($iso);
        $lang->setLocale($iso);
        $lang->setDate_format_lite('d/m/Y');
        $lang->setDate_format_full('d/m/Y H:i:s');
        return $lang;
    }

    public function testNewActiveLanguageAppearsAfterCreationWithoutManualInvalidation(): void
    {
        $iso = 'z' . substr(uniqid(), -4);
        $lang = $this->makeInstance($iso);
        $this->langId = $lang->__insert();

        $dlangs = \Dvups_lang::cachedActiveDlangs();
        $this->assertSame($this->langId, $dlangs[$iso] ?? null, '__insert() doit invalider le cache : pas besoin de l\'appeler avant');
    }

    public function testCacheIsInvalidatedWhenALanguageIsDeactivated(): void
    {
        $iso = 'z' . substr(uniqid(), -4);
        $lang = $this->makeInstance($iso);
        $this->langId = $lang->__insert();

        $dlangs = \Dvups_lang::cachedActiveDlangs();
        $this->assertArrayHasKey($iso, $dlangs);

        \Dvups_lang::find($this->langId)->__update(['active' => '0']);

        $refreshed = \Dvups_lang::cachedActiveDlangs();
        $this->assertArrayNotHasKey($iso, $refreshed, '__update() doit invalider le cache compile');
    }

    public function testCacheIsInvalidatedOnDeletion(): void
    {
        $iso = 'z' . substr(uniqid(), -4);
        $lang = $this->makeInstance($iso);
        $langId = $lang->__insert();

        $dlangs = \Dvups_lang::cachedActiveDlangs();
        $this->assertArrayHasKey($iso, $dlangs);

        \Dvups_lang::find($langId)->__delete();

        $refreshed = \Dvups_lang::cachedActiveDlangs();
        $this->assertArrayNotHasKey($iso, $refreshed, '__delete() doit invalider le cache compile');
    }
}
