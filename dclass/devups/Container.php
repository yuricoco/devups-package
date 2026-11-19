<?php

namespace Devups;

use Psr\Container\ContainerInterface;

/**
 * ETAPE 5.4 (v8.0) : conteneur PSR-11 minimal - voir TODO.md "LOT 5.4".
 * Ajout pur : rien d'existant ne l'utilise encore (aucun appel, aucun
 * site converti) - disponible pour du code qui choisirait de s'y
 * brancher progressivement, sans risque sur le comportement actuel.
 */
class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private $instances = [];

    /** @var array<string, callable> */
    private $factories = [];

    /**
     * Enregistre une valeur deja construite sous cet identifiant.
     */
    public function set(string $id, $value): void
    {
        $this->instances[$id] = $value;
        unset($this->factories[$id]);
    }

    /**
     * Enregistre une fabrique appelee a CHAQUE resolution de cet
     * identifiant (reçoit le conteneur en argument).
     */
    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Enregistre une fabrique resolue au plus une fois : le resultat
     * du premier appel est mis en cache et reutilise ensuite.
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bind($id, function (ContainerInterface $c) use ($id, $factory) {
            $value = $factory($c);
            $this->instances[$id] = $value;
            unset($this->factories[$id]);
            return $value;
        });
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($this);
        }

        throw new ContainerNotFoundException("No entry was found for identifier \"$id\".");
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || isset($this->factories[$id]);
    }
}
