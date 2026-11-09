<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE C.4 (v7.1) - defaut D2 (tome4.md, table 10.2) : sur une entite
 * traduite (dvtranslate = true) sans id_lang force, initSelect() joint
 * dvups_lang (alias "l") pour le format bilingue - cette table a ses
 * propres colonnes "id"/"name". Une reference a une colonne NON
 * qualifiee du cote de l'entite de base (ex. where('name', ...), ou
 * whereIn/whereInReverse/between sur une colonne non qualifiee) devient
 * alors ambigue pour MySQL/MariaDB : "Column 'name' in where clause is
 * ambiguous" (SQLSTATE[23000], erreur 1052).
 *
 * Decouvert pendant l'ETAPE B (LazyloadingInjectionTest) sur l'entite
 * Tree avec les operateurs "rein"/"btw" - contourne a l'epoque en
 * utilisant l'entite Interval (non traduite) pour ce test-la. Ce test
 * reproduit directement le defaut D2 sur Tree et doit echouer avant le
 * correctif (colonne non qualifiee -> qualifiee automatiquement avec
 * la table de base quand une jointure de traduction est presente) et
 * passer apres.
 */
final class AmbiguousColumnOnTranslatedEntityTest extends DatabaseTestCase
{
    public function testWhereOnAnUnqualifiedColumnIsNotAmbiguousOnATranslatedEntity(): void
    {
        \Tree::create(['name' => 'etapec4_unqualified']);

        $rows = \Tree::select()->where('name', 'etapec4_unqualified')->get();

        $this->assertCount(1, $rows);
    }

    public function testWhereInReverseOnAnUnqualifiedColumnIsNotAmbiguousOnATranslatedEntity(): void
    {
        \Tree::create(['name' => 'etapec4_rein']);

        $rows = \Tree::select()->whereInReverse('etapec4_rein', 'name')->get();

        $this->assertCount(1, $rows);
    }

    public function testWhereBetweenOnAnUnqualifiedColumnIsNotAmbiguousOnATranslatedEntity(): void
    {
        $id = \Tree::create(['name' => 'etapec4_btw']);

        $rows = \Tree::select()->whereBetween('id', [$id, $id])->get();

        $this->assertCount(1, $rows);
    }
}
