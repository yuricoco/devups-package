<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE 5.2 (v8.0) - LOT 9 (suite) : Datatable::deriveImplicitRelationWith()
 * (methode privee, appelee via Reflection) derive automatiquement les
 * `QueryBuilder::with()` necessaires a partir des cles en notation pointee
 * de $datatablemodel ('relation.field'), pour que la colonne affiche la
 * vraie valeur de la relation au lieu d'un "stub" (seul id hydrate).
 *
 * Avant ce lot, il fallait appeler `$this->qbcustom->with('relation',
 * ['field'])` a la main dans chaque buildindextable() - cf.
 * Dvups_privilegeTable::buildindextable() qui utilise deja cette
 * convention sans l'appel manuel (elle n'en avait jamais eu besoin tant
 * que la table etait vide en dev, d'ou la decouverte tardive du bug).
 */
final class DatatableImplicitRelationWithTest extends DatabaseTestCase
{
    private function deriveImplicitRelationWith(\dclass\devups\Datatable\Datatable $datatable): void
    {
        $method = new \ReflectionMethod(\dclass\devups\Datatable\Datatable::class, 'deriveImplicitRelationWith');
        $method->setAccessible(true);
        $method->invoke($datatable);
    }

    private function qbcustomOf(\dclass\devups\Datatable\Datatable $datatable): \QueryBuilder
    {
        $prop = new \ReflectionProperty(\dclass\devups\Datatable\Datatable::class, 'qbcustom');
        $prop->setAccessible(true);
        return $prop->getValue($datatable);
    }

    public function testRelationColumnIsHydratedWithoutAnyManualWithCall(): void
    {
        $roleId = \Dvups_role::create(['name' => 'e2e-implicitwith' . uniqid(), 'alias' => 'x']);
        $actionId = \Dvups_action::create(['entity' => 'Configuration', 'action' => 'create', 'label' => 'Create']);
        $privilegeId = \Dvups_privilege::create(['dvups_role_id' => $roleId, 'dvups_action_id' => $actionId]);

        $privilege = \Dvups_privilege::find($privilegeId);
        $table = \Dvups_privilegeTable::init($privilege);
        $table->buildindextable();

        $this->deriveImplicitRelationWith($table);

        $rows = $this->qbcustomOf($table)->where('this.id', $privilegeId)->get();

        $this->assertCount(1, $rows);
        $this->assertNotNull($rows[0]->dvups_role->name);
        $this->assertSame(\Dvups_role::find($roleId)->getName(), $rows[0]->dvups_role->name);
    }

    /**
     * Non-regression : une colonne sur une relation dont le champ est
     * TRADUIT (dvtranslate - ex. Tree_item::$name, assemble depuis
     * `tree_item_lang` et non une colonne SQL directe de `tree_item`) ne
     * doit PAS declencher de with() automatique, sinon la requete leve
     * "Unknown column" (1054) - cas reel : Tree_item_imageTable::value
     * 'Tree_item.name'.
     */
    public function testTranslatedRelationColumnIsSkippedAndDoesNotBreakTheQuery(): void
    {
        $table = \Tree_item_imageTable::init(new \Tree_item_image());
        $table->buildindextable();

        $this->deriveImplicitRelationWith($table);

        // ETAPE 5.2 (v8.0) : avant le garde-fou dvtranslated_columns, cette
        // requete levait "SQLSTATE[42S22]: Unknown column 'tree_item.name'"
        // (1054) des que with() etait derive aveuglement sur un champ
        // traduit - get() ne doit plus jamais lever ici.
        $rows = $this->qbcustomOf($table)->get();

        $this->assertIsArray($rows);
    }
}
