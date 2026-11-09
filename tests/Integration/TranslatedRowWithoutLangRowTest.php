<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE C.1 (v7.1) - defaut D1/D15 (tome4.md, table 10.2) : une ligne
 * d'une entite traduite du socle qui n'a AUCUNE ligne "<table>_lang" (ex.
 * creee avant que la traduction soit renseignee, ou dont la seule ligne
 * de langue a ete supprimee) est INVISIBLE a l'ORM - `QueryBuilder::initSelect()`
 * construit un INNER JOIN vers `dvups_lang` (`join dvups_lang l on (...)`)
 * pour le format bilingue (GROUP_CONCAT) : sans ligne `<table>_lang`, la
 * jointure precedente (`left join <table>_lang`) donne `lang_id = NULL`,
 * et `NULL = l.id` n'est VRAI pour aucune ligne de `dvups_lang` - la
 * jointure INNER elimine donc la ligne ENTIERE, pas seulement ses
 * colonnes traduites.
 *
 * Ces tests DOIVENT echouer avant le correctif de initSelect() (ETAPE
 * C.2) et passer apres, pour chacune des 8 entites traduites du socle,
 * a travers find()/findOrNull()/where()->get()/first()/count() SANS
 * langue forcee (lazyloading est couvert separement, voir
 * LazyloadingTranslatedRowTest.php).
 */
final class TranslatedRowWithoutLangRowTest extends DatabaseTestCase
{
    /**
     * [classe, colonnes de base minimales (jamais traduites)].
     */
    public static function entityProvider(): array
    {
        return [
            'Tree' => [\Tree::class, ['name' => 'etapec1_tree']],
            'Tree_item' => [\Tree_item::class, []],
            'Status' => [\Status::class, ['color' => '#fff', '_key' => 'etapec1_key', 'entity' => 'etapec1_entity']],
            'Cmscontent' => [\Cmscontent::class, ['active' => 1, 'position' => 1]],
            'Notificationtype' => [\Notificationtype::class, ['_key' => 'etapec1_key', 'session' => 'etapec1_session', 'entity' => 'etapec1_entity']],
            'Reportingmodel' => [\Reportingmodel::class, ['name' => 'etapec1_reportingmodel', 'type' => 'welcome']],
            'Country' => [\Country::class, ['name' => 'Etapec1land', 'iso' => 'E1', 'numcode' => '999', 'phonecode' => 1, 'status' => 1]],
            // ETAPE F.2 (v7.1) : `name` (herite de Dvups_config_item) est
            // NOT NULL sans defaut PHP ni SQL - absent de l'INSERT, pas
            // d'erreur sous sql_mode tolerant (MariaDB), 1364 sous sql_mode
            // strict (MySQL 8, celui de la CI) ; sans rapport avec le
            // defaut D1/D15 teste ici.
            'Dvups_entity' => [\Dvups_entity::class, ['name' => 'etapec1_entity']],
        ];
    }

    /**
     * Cree une ligne SANS AUCUNE ligne "<table>_lang" : insere via l'ORM
     * (colonnes de base uniquement, jamais une colonne traduite), puis
     * supprime explicitement toute ligne de langue que la creation
     * aurait pu poser quand meme - la precondition du test ("aucune
     * ligne _lang") est ainsi garantie independamment du comportement
     * de create().
     */
    private function createRowWithoutLangRow(string $class, array $baseColumns): int
    {
        $id = (int)$class::create($baseColumns);
        $table = strtolower($class);
        \Model::pdo()->exec("DELETE FROM `{$table}_lang` WHERE `{$table}_id` = $id");
        return $id;
    }

    /**
     * @dataProvider entityProvider
     */
    public function testCountStillCountsARowWithoutAnyLangRow(string $class, array $baseColumns): void
    {
        $before = (int)$class::select()->count();
        $this->createRowWithoutLangRow($class, $baseColumns);
        $after = (int)$class::select()->count();

        $this->assertSame($before + 1, $after, "$class::count() doit compter la ligne meme sans ligne _lang");
    }

    /**
     * @dataProvider entityProvider
     */
    public function testFindStillFindsARowWithoutAnyLangRow(string $class, array $baseColumns): void
    {
        $id = $this->createRowWithoutLangRow($class, $baseColumns);

        $found = $class::find($id);

        $this->assertNotNull($found);
        $this->assertSame($id, $found->getId(), "$class::find() doit retourner la ligne meme sans ligne _lang");
    }

    /**
     * @dataProvider entityProvider
     */
    public function testFindOrNullStillFindsARowWithoutAnyLangRow(string $class, array $baseColumns): void
    {
        $id = $this->createRowWithoutLangRow($class, $baseColumns);

        $found = $class::findOrNull($id);

        $this->assertNotNull($found, "$class::findOrNull() ne doit jamais rendre null pour une ligne qui existe (sans ligne _lang)");
        $this->assertSame($id, $found->getId());
    }

    /**
     * @dataProvider entityProvider
     */
    public function testWhereGetStillReturnsARowWithoutAnyLangRow(string $class, array $baseColumns): void
    {
        $id = $this->createRowWithoutLangRow($class, $baseColumns);

        $rows = $class::where('this.id', '=', $id)->get();

        $this->assertCount(1, $rows, "$class::where()->get() doit retourner la ligne meme sans ligne _lang");
        $this->assertSame($id, $rows[0]->getId());
    }

    /**
     * @dataProvider entityProvider
     */
    public function testFirstStillReturnsARowWithoutAnyLangRow(string $class, array $baseColumns): void
    {
        $id = $this->createRowWithoutLangRow($class, $baseColumns);

        $row = $class::where('this.id', '=', $id)->first();

        $this->assertNotNull($row);
        $this->assertSame($id, $row->getId(), "$class::where()->first() doit retourner la ligne meme sans ligne _lang");
    }
}
