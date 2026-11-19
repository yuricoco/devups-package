<?php

namespace Devups;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * ETAPE 5.4 (v8.0) : exception levee par Container::get() pour un
 * identifiant inconnu - voir TODO.md "LOT 5.4".
 */
class ContainerNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
