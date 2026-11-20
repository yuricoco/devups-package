<?php

namespace Devups\Tests\Unit;

use Devups\Http\ResponseEmitter;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ResponseEmitterTest extends TestCase
{
    public function testSendsTheStatusCode(): void
    {
        $sentStatus = null;
        $emitter = new ResponseEmitter(
            sendStatus: function (int $code) use (&$sentStatus) {
                $sentStatus = $code;
            },
            sendHeader: function () {},
            emitBody: function () {}
        );

        $emitter->emit(new Response(404));

        $this->assertSame(404, $sentStatus);
    }

    public function testSendsEachHeaderValueWithTheCorrectReplaceFlag(): void
    {
        $sentHeaders = [];
        $emitter = new ResponseEmitter(
            sendStatus: function () {},
            sendHeader: function (string $name, string $value, bool $replace) use (&$sentHeaders) {
                $sentHeaders[] = [$name, $value, $replace];
            },
            emitBody: function () {}
        );

        $response = new Response(200, ['X-Multi' => ['a', 'b'], 'Content-Type' => 'application/json']);

        $emitter->emit($response);

        $this->assertSame(
            [
                ['X-Multi', 'a', true],
                ['X-Multi', 'b', false],
                ['Content-Type', 'application/json', true],
            ],
            $sentHeaders
        );
    }

    public function testEmitsTheBodyViaTheInjectedCallable(): void
    {
        $emittedResponse = null;
        $response = new Response(200, [], 'the body');

        $emitter = new ResponseEmitter(
            sendStatus: function () {},
            sendHeader: function () {},
            emitBody: function (ResponseInterface $r) use (&$emittedResponse) {
                $emittedResponse = $r;
            }
        );

        $emitter->emit($response);

        $this->assertSame($response, $emittedResponse);
    }

    public function testDefaultCallablesDoNotErrorInCliContext(): void
    {
        // http_response_code() leve un warning "headers already sent" si
        // un AUTRE test de la suite a deja produit une sortie reelle avant
        // celui-ci (ordre d'execution non garanti, surtout entre versions
        // de PHP) - artefact de process partage entre tests, pas un defaut
        // de `ResponseEmitter` lui-meme (deja couvert, callables injectes,
        // par les tests ci-dessus).
        if (headers_sent()) {
            $this->markTestSkipped('En-tetes deja envoyes par un test precedent dans ce processus.');
        }

        $emitter = new ResponseEmitter();

        ob_start();
        $emitter->emit(new Response(200, [], 'body'));
        $output = ob_get_clean();

        $this->assertSame('body', $output);
    }
}
