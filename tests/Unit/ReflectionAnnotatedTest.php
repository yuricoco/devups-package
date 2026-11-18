<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE 3.2/3.4 (v8.0) : ReflectionAnnotatedClass/ReflectionAnnotatedMethod,
 * le shim qui remplace la bibliotheque "Addendum" vendoree (dclass/lib/
 * addendum/, retiree - voir TODO.md ETAPE 3.0) en lisant des attributs PHP 8
 * natifs via getAttributes()->newInstance() au lieu de parser des docblocks.
 * Couvre l'usage reel fait par dclass/devups/Http/Request.php : annotation
 * de classe (Api), annotation de methode verb-specifique (GET/POST/PUT/
 * DELETE), annotation de methode generique (Api sur une methode, distincte
 * d'un verbe), annotation absente (false/null), et le cas DELETE/PUT qui
 * defaut leur $path a '/:id' quand aucun n'est fourni (fidelite avec
 * l'ancien comportement d'hydratation par tableau de la classe Annotation
 * d'Addendum - voir TODO.md ETAPE 3.0).
 */
final class ReflectionAnnotatedTest extends TestCase
{
    private function fixtureClass(): string
    {
        // bloc "namespace { ... }" : les classes d'annotation
        // (dclass/lib/annotation/*.php) vivent dans l'espace de noms
        // global - eval() herite sinon de l'espace de noms de CE fichier
        // de test (Devups\Tests\Unit), ce qui ferait echouer la
        // resolution des attributs (meme piege que route/Admin.php,
        // voir UPGRADE-8.0.md LOT 3).
        return <<<'PHP'
namespace {
#[Api(name: '/widget')]
class ReflectionAnnotatedFixtureController
{
    #[GET]
    public function index() {}

    #[GET(path: '/:id')]
    public function detail() {}

    #[POST(path: '/create')]
    #[Auth(authorized: 1)]
    public function create() {}

    #[DELETE]
    public function delete() {}

    #[Api(name: '/widget/custom')]
    public function custom() {}

    public function plain() {}
}
}
PHP;
    }

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('ReflectionAnnotatedClass')) {
            require_once ROOT . 'dclass/lib/ReflectionAnnotated.php';
        }
    }

    protected function setUp(): void
    {
        if (!class_exists('ReflectionAnnotatedFixtureController')) {
            eval($this->fixtureClass());
        }
    }

    public function testClassLevelAnnotationIsReadBack(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');

        $this->assertTrue($reflection->hasAnnotation('Api'));
        $this->assertSame('/widget', $reflection->getAnnotation('Api')->name);
    }

    public function testVerbSpecificMethodAnnotationIsDetected(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');

        $index = $reflection->getMethod('index');
        $this->assertTrue($index->hasAnnotation('GET'));
        $this->assertFalse($index->hasAnnotation('POST'));

        $detail = $reflection->getMethod('detail');
        $this->assertTrue($detail->hasAnnotation('GET'));
        $this->assertSame('/:id', $detail->getAnnotation('GET')->path);
    }

    public function testMultipleAttributesOnTheSameMethodAreBothReadable(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');
        $create = $reflection->getMethod('create');

        $this->assertTrue($create->hasAnnotation('POST'));
        $this->assertSame('/create', $create->getAnnotation('POST')->path);
        $this->assertTrue($create->hasAnnotation('Auth'));
        $this->assertSame(1, $create->getAnnotation('Auth')->authorized);
    }

    public function testDeleteDefaultsItsPathToIdPlaceholderWhenNoneGiven(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');
        $delete = $reflection->getMethod('delete');

        $this->assertTrue($delete->hasAnnotation('DELETE'));
        $this->assertSame('/:id', $delete->getAnnotation('DELETE')->path);
    }

    public function testGenericApiAnnotationOnAMethodIsDistinctFromAVerbAnnotation(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');
        $custom = $reflection->getMethod('custom');

        $this->assertTrue($custom->hasAnnotation('Api'));
        $this->assertSame('/widget/custom', $custom->getAnnotation('Api')->name);
        $this->assertFalse($custom->hasAnnotation('GET'));
    }

    public function testMethodWithoutAnyAnnotationReturnsFalseAndNull(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');
        $plain = $reflection->getMethod('plain');

        $this->assertFalse($plain->hasAnnotation('Api'));
        $this->assertNull($plain->getAnnotation('Api'));
    }

    public function testGetMethodsReturnsAnnotatedInstancesForAll(): void
    {
        $reflection = new \ReflectionAnnotatedClass('ReflectionAnnotatedFixtureController');

        foreach ($reflection->getMethods() as $method) {
            $this->assertInstanceOf(\ReflectionAnnotatedMethod::class, $method);
        }
    }
}
