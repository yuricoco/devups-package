<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE 5.1 (v8.0) : Model::shortClassName(), le helper qui derive un nom
 * court de classe/instance independamment d'un namespace eventuel - voir
 * TODO.md ETAPE 5.0/5.1. Verifie l'equivalence stricte avec
 * get_class()/get_called_class() d'aujourd'hui (tout est encore en
 * namespace global), condition necessaire pour que la conversion des sites
 * d'appel ORM (table/colonne/cle de formulaire/filtre/cache) soit un
 * changement de comportement nul.
 */
final class ShortClassNameTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists('ShortClassNameFixtureEntity')) {
            // bloc "namespace { ... }" : Model vit dans l'espace de noms
            // global - eval() herite sinon de l'espace de noms de CE
            // fichier de test (Devups\Tests\Unit), meme piege que
            // ReflectionAnnotatedTest (voir UPGRADE-8.0.md LOT 3).
            eval(<<<'PHP'
namespace {
class ShortClassNameFixtureEntity extends \Model
{
}
}
PHP);
        }
    }

    public function testReturnsShortNameForAnInstance(): void
    {
        $entity = new \ShortClassNameFixtureEntity();

        $this->assertSame('ShortClassNameFixtureEntity', \Model::shortClassName($entity));
        $this->assertSame(get_class($entity), \Model::shortClassName($entity));
    }

    public function testReturnsShortNameForAClassNameString(): void
    {
        $this->assertSame('ShortClassNameFixtureEntity', \Model::shortClassName('ShortClassNameFixtureEntity'));
    }

    public function testDefaultsToTheCalledClassWhenNoArgumentIsGiven(): void
    {
        $this->assertSame(
            (new \ReflectionClass(\Model::class))->getShortName(),
            \Model::shortClassName()
        );
    }

    public function testMatchesReflectionGetShortNameSemantics(): void
    {
        $entity = new \ShortClassNameFixtureEntity();

        $this->assertSame(
            (new \ReflectionClass($entity))->getShortName(),
            \Model::shortClassName($entity)
        );
    }
}
