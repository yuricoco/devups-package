<?php

namespace Devups\Tests\Unit;

use Devups\Container;
use Devups\ContainerNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerTest extends TestCase
{
    public function testImplementsPsr11ContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testSetThenGetReturnsTheSameValue(): void
    {
        $container = new Container();
        $value = new \stdClass();

        $container->set('thing', $value);

        $this->assertSame($value, $container->get('thing'));
    }

    public function testHasReflectsSetAndBind(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('thing'));

        $container->set('thing', 'value');
        $this->assertTrue($container->has('thing'));

        $container->bind('other', fn() => 'value');
        $this->assertTrue($container->has('other'));
    }

    public function testBindCallsTheFactoryOnEveryResolution(): void
    {
        $container = new Container();
        $calls = 0;

        $container->bind('thing', function () use (&$calls) {
            $calls++;
            return new \stdClass();
        });

        $first = $container->get('thing');
        $second = $container->get('thing');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, $calls);
    }

    public function testSingletonResolvesTheFactoryOnlyOnce(): void
    {
        $container = new Container();
        $calls = 0;

        $container->singleton('thing', function () use (&$calls) {
            $calls++;
            return new \stdClass();
        });

        $first = $container->get('thing');
        $second = $container->get('thing');

        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function testSingletonFactoryReceivesTheContainer(): void
    {
        $container = new Container();

        $container->singleton('thing', function (ContainerInterface $c) use ($container) {
            $this->assertSame($container, $c);
            return new \stdClass();
        });

        $container->get('thing');
    }

    public function testGetOnUnknownIdentifierThrowsNotFoundException(): void
    {
        $container = new Container();

        $this->expectException(ContainerNotFoundException::class);
        $this->expectException(NotFoundExceptionInterface::class);

        $container->get('unknown');
    }

    public function testSetAfterBindOverridesTheFactory(): void
    {
        $container = new Container();

        $container->bind('thing', fn() => 'from factory');
        $container->set('thing', 'from set');

        $this->assertSame('from set', $container->get('thing'));
    }
}
