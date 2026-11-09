<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE E.2 (v7.1) - Datatable::getTableEntityBody() (methode privee,
 * appelee via Reflection) resout chaque colonne en appelant
 * `call_user_func([$entity, 'get' . ucfirst($value)])` - si la cle de
 * colonne ne correspond a AUCUN getter de l'entite (datatablemodel
 * desynchronise du datacore : colonne renommee/supprimee cote entite,
 * faute de frappe dans la config du Datatable), PHP 8 leve une \Error
 * fatale ("Call to undefined method") qui plante tout l'ecran - au lieu
 * d'une cellule vide avec un avertissement journalise.
 */
final class DatatableUnknownColumnKeyTest extends DatabaseTestCase
{
    private function callGetTableEntityBody($entity, array $header): array
    {
        $method = new \ReflectionMethod(\dclass\devups\Datatable\Datatable::class, 'getTableEntityBody');
        $method->setAccessible(true);
        return $method->invoke(null, $entity, $header);
    }

    public function testAnUnknownColumnKeyRendersAnEmptyCellInsteadOfCrashing(): void
    {
        $id = \Tree::create(['name' => 'etapee2_unknown_col']);
        $tree = \Tree::find($id);

        $rows = $this->callGetTableEntityBody($tree, [
            ['value' => 'thiscolumndoesnotexistatall', 'label' => 'Inconnue'],
        ]);

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('<td> Inconnue </td>', $rows[0]);
        $this->assertStringContainsString('<td></td>', $rows[0]);
    }

    public function testAKnownColumnStillRendersItsValue(): void
    {
        $id = \Tree::create(['name' => 'etapee2_known_col']);
        $tree = \Tree::find($id);

        $rows = $this->callGetTableEntityBody($tree, [
            ['value' => 'id', 'label' => 'Id'],
        ]);

        $this->assertCount(1, $rows);
        $this->assertStringContainsString((string)$id, $rows[0]);
    }
}
