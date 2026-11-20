<?php

namespace Devups\Tests\Unit;

use Devups\Http\CallableRequestHandler;
use Devups\Http\MiddlewareStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareStackTest extends TestCase
{
    private function request(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'https://example.test/');
    }

    private function recordingMiddleware(array &$order, string $name): MiddlewareInterface
    {
        return new class ($order, $name) implements MiddlewareInterface {
            private array $order;
            private string $name;

            public function __construct(array &$order, string $name)
            {
                $this->order = &$order;
                $this->name = $name;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->order[] = "before:{$this->name}";
                $response = $handler->handle($request);
                $this->order[] = "after:{$this->name}";
                return $response;
            }
        };
    }

    private function shortCircuitingMiddleware(ResponseInterface $response): MiddlewareInterface
    {
        return new class ($response) implements MiddlewareInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    public function testWithNoMiddlewareDelegatesDirectlyToTheFinalHandler(): void
    {
        $final = new Response(200, [], 'final');
        $stack = new MiddlewareStack([], new CallableRequestHandler(fn() => $final));

        $this->assertSame($final, $stack->handle($this->request()));
    }

    public function testMiddlewareRunsInOnionOrder(): void
    {
        $order = [];
        $final = new Response(200, [], 'final');

        $stack = new MiddlewareStack(
            [
                $this->recordingMiddleware($order, 'A'),
                $this->recordingMiddleware($order, 'B'),
            ],
            new CallableRequestHandler(function () use (&$order, $final) {
                $order[] = 'final-handler';
                return $final;
            })
        );

        $response = $stack->handle($this->request());

        $this->assertSame($final, $response);
        $this->assertSame(['before:A', 'before:B', 'final-handler', 'after:B', 'after:A'], $order);
    }

    public function testMiddlewareCanShortCircuitWithoutCallingTheFinalHandler(): void
    {
        $shortCircuited = new Response(403, [], 'denied');
        $finalHandlerCalled = false;

        $stack = new MiddlewareStack(
            [$this->shortCircuitingMiddleware($shortCircuited)],
            new CallableRequestHandler(function () use (&$finalHandlerCalled) {
                $finalHandlerCalled = true;
                return new Response(200);
            })
        );

        $response = $stack->handle($this->request());

        $this->assertSame($shortCircuited, $response);
        $this->assertFalse($finalHandlerCalled);
    }

    public function testTheSameStackInstanceCanBeReusedForSeveralRequests(): void
    {
        $order = [];
        $final = new Response(200);

        $stack = new MiddlewareStack(
            [$this->recordingMiddleware($order, 'A')],
            new CallableRequestHandler(fn() => $final)
        );

        $stack->handle($this->request());
        $stack->handle($this->request());

        $this->assertSame(['before:A', 'after:A', 'before:A', 'after:A'], $order);
    }
}
