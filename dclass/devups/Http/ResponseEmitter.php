<?php

namespace Devups\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * ETAPE 5.2 (v8.0) : LOT 6 - envoie un `Psr\Http\Message\ResponseInterface`
 * (ex. produit par `MiddlewareStack`) comme reponse HTTP reelle (code,
 * en-tetes, corps) - voir TODO.md "LOT 6". Ajout pur, jamais appele par
 * le dispatch existant (`Router`/`Response` globaux, inchanges).
 *
 * Les 3 effets de bord (code HTTP, en-tete, corps) sont derriere des
 * callables injectables (defaut : `http_response_code()`/`header()`/
 * `echo`) plutot qu'appeles en dur, pour rester testable sans emettre de
 * vrais en-tetes HTTP pendant les tests.
 */
class ResponseEmitter
{
    /** @var callable */
    private $sendStatus;

    /** @var callable */
    private $sendHeader;

    /** @var callable */
    private $emitBody;

    public function __construct(
        ?callable $sendStatus = null,
        ?callable $sendHeader = null,
        ?callable $emitBody = null
    ) {
        $this->sendStatus = $sendStatus ?? static function (int $code): void {
            http_response_code($code);
        };

        $this->sendHeader = $sendHeader ?? static function (string $name, string $value, bool $replace): void {
            header("$name: $value", $replace);
        };

        $this->emitBody = $emitBody ?? static function (ResponseInterface $response): void {
            echo (string)$response->getBody();
        };
    }

    public function emit(ResponseInterface $response): void
    {
        ($this->sendStatus)($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            $replace = true;
            foreach ($values as $value) {
                ($this->sendHeader)($name, $value, $replace);
                $replace = false;
            }
        }

        ($this->emitBody)($response);
    }
}
