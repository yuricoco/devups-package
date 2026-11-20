<?php

namespace Devups\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ETAPE 5.2 (v8.0) : LOT 6 - pipeline PSR-15 minimal (oignon classique) -
 * voir TODO.md "LOT 6". Ajout pur : `Router`/`Request` (dclass/devups/
 * Http/Router.php, Request.php - espace de noms global, inchanges) ne
 * l'appellent pas, rien n'est branche dans le dispatch reel.
 *
 * Chaque middleware recoit la requete et un `RequestHandlerInterface`
 * representant le "reste" du pipeline : il peut inspecter/modifier la
 * requete avant d'appeler `$next->handle()`, inspecter/modifier la
 * reponse au retour, ou court-circuiter en ne l'appelant pas du tout.
 * Instance immuable (chaque etape clone l'instance avec l'index suivant)
 * - une meme pile peut donc etre reutilisee pour plusieurs requetes sans
 * etat partage entre elles.
 */
class MiddlewareStack implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware;

    private RequestHandlerInterface $finalHandler;

    private int $index = 0;

    /**
     * @param MiddlewareInterface[] $middleware Ordre d'execution : le
     *     premier de la liste est le premier appele.
     */
    public function __construct(array $middleware, RequestHandlerInterface $finalHandler)
    {
        $this->middleware = $middleware;
        $this->finalHandler = $finalHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middleware[$this->index])) {
            return $this->finalHandler->handle($request);
        }

        $next = clone $this;
        $next->index = $this->index + 1;

        return $this->middleware[$this->index]->process($request, $next);
    }
}
