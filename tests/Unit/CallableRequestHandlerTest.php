<?php

namespace Devups\Tests\Unit;

use Devups\Http\CallableRequestHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class CallableRequestHandlerTest extends TestCase
{
    public function testHandleCallsTheWrappedCallableWithTheRequest(): void
    {
        $received = null;
        $response = new Response(200, [], 'ok');
        $request = new ServerRequest('GET', 'https://example.test/');

        $handler = new CallableRequestHandler(function ($request) use (&$received, $response) {
            $received = $request;
            return $response;
        });

        $result = $handler->handle($request);

        $this->assertSame($request, $received);
        $this->assertSame($response, $result);
    }
}
