<?php

namespace Devups\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ETAPE 5.2 (v8.0) : LOT 6 - adapte un `callable(ServerRequestInterface):
 * ResponseInterface` en `Psr\Http\Server\RequestHandlerInterface`, pour
 * fournir le "bout" du pipeline a `MiddlewareStack` sans ecrire une
 * classe dediee a chaque usage - voir TODO.md "LOT 6".
 */
class CallableRequestHandler implements RequestHandlerInterface
{
    /** @var callable */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callable)($request);
    }
}
