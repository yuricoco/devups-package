<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE E.1 (v7.1) - ModelTrait::__get() sur un attribut VRAIMENT inconnu
 * (ni une colonne de l'entite, ni une colonne traduite pas encore
 * hydratee, ni la convention "_attribut" du lazyloading de relation)
 * doit lever UndefinedPropertyException - plus jamais un die() silencieux
 * ni un simple trigger_error() + null qui laissait la page continuer
 * avec une donnee manquante sans que personne ne s'en rende compte en
 * production.
 */
final class ModelUndefinedPropertyTest extends DatabaseTestCase
{
    public function testAccessingATrulyUndefinedPropertyThrows(): void
    {
        $tree = new \Tree();

        $this->expectException(\UndefinedPropertyException::class);

        $tree->thisPropertyDoesNotExistAtAll;
    }

    public function testUndefinedPropertyExceptionIsALogicException(): void
    {
        $this->assertInstanceOf(\LogicException::class, new \UndefinedPropertyException());
    }

    /**
     * Non-regression : la convention "_attribut" (lazyloading d'une
     * relation deja hydratee comme objet) ne doit pas devenir une
     * UndefinedPropertyException - comportement inchange.
     */
    public function testUnderscorePrefixedLazyloadingConventionStillWorks(): void
    {
        $id = \Tree::create(['name' => 'etapee1_lazy']);

        // Tree_item a une relation "tree" (manyToOne) hydratable via "_tree".
        $treeItemId = \Tree_item::create(['tree_id' => $id]);
        $treeItem = \Tree_item::find($treeItemId);

        $this->assertNotNull($treeItem->_tree);
    }

    /**
     * Non-regression : une colonne traduite pas encore hydratee reste
     * null, pas une exception.
     */
    public function testUnhydratedTranslatedColumnStillReturnsNull(): void
    {
        $tree = new \Tree();

        $this->assertNull($tree->label);
    }
}
