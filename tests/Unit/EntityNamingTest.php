<?php

/**
 * ETAPE 5.2 (v8.0) - LOT 9 : EntityNaming est le point de verite unique
 * qui remplace la concatenation de chaine dupliquee dans Controller.php/
 * ControllerCore.php/FrontController.php pour deriver le nom des classes
 * satellites (Controller/Datatable/Form) d'une entite - voir TODO.md
 * "LOT 9". Fixtures declarees dans des blocs de namespace dedies (jamais
 * utilisees par le framework lui-meme) pour exercer les deux chemins
 * reels : FQCN namespacee avec/sans satellite existant, et nom court
 * historique avec/sans entree dans dvups_configurations.php.
 */

namespace Devups\Tests\Fixtures\EntityNaming\Entity {

    class Widget
    {
    }

    class NoSiblingEntity
    {
    }
}

namespace Devups\Tests\Fixtures\EntityNaming\Controller {

    class WidgetController
    {
    }
}

namespace Devups\Tests\Fixtures\EntityNaming\Datatable {

    class WidgetTable
    {
    }
}

namespace Devups\Tests\Fixtures\EntityNaming\Form {

    class WidgetForm
    {
    }
}

namespace Devups\Tests\Unit {

    use Devups\Tests\Fixtures\EntityNaming\Controller\WidgetController;
    use Devups\Tests\Fixtures\EntityNaming\Datatable\WidgetTable;
    use Devups\Tests\Fixtures\EntityNaming\Entity\NoSiblingEntity;
    use Devups\Tests\Fixtures\EntityNaming\Entity\Widget;
    use Devups\Tests\Fixtures\EntityNaming\Form\WidgetForm;
    use PHPUnit\Framework\TestCase;

    final class EntityNamingTest extends TestCase
    {
        public function testBareShortNameWithoutConfigEntryFallsBackToGlobalConvention(): void
        {
            $this->assertSame('WidgetlessTable', \EntityNaming::tableFor('Widgetless'));
            $this->assertSame('WidgetlessForm', \EntityNaming::formFor('Widgetless'));
            $this->assertSame('WidgetlessController', \EntityNaming::controllerFor('Widgetless'));
        }

        public function testNamespacedEntityWithExistingSiblingReturnsItsFqcn(): void
        {
            $this->assertSame(WidgetController::class, \EntityNaming::controllerFor(Widget::class));
            $this->assertSame(WidgetTable::class, \EntityNaming::tableFor(Widget::class));
            $this->assertSame(WidgetForm::class, \EntityNaming::formFor(Widget::class));
        }

        public function testNamespacedEntityWithoutExistingSiblingFallsBackToShortName(): void
        {
            // Aucune classe ...\Form\NoSiblingEntityForm n'existe (voir
            // le bloc de namespace Entity ci-dessus) - repli attendu sur
            // le nom court global, jamais d'erreur.
            $this->assertSame('NoSiblingEntityForm', \EntityNaming::formFor(NoSiblingEntity::class));
        }

        public function testFqcnWhoseParentSegmentIsNotEntityFallsBackToShortName(): void
        {
            $this->assertSame('ThingController', \EntityNaming::controllerFor('Some\\Other\\Thing'));
        }

        public function testFrontControllerSuffix(): void
        {
            $this->assertSame('WidgetlessFrontController', \EntityNaming::controllerFor('Widgetless', true));
        }

        public function testLeadingBackslashIsIgnored(): void
        {
            $this->assertSame(WidgetTable::class, \EntityNaming::tableFor('\\' . Widget::class));
        }
    }
}
