<?php

// ETAPE 3.1 (v8.0) : @ApiLazyloading(...) -> #[ApiLazyloading(...)].
// Aucun usage reel dans le depot (verifie) - converti par coherence avec
// le reste des annotations de routage.
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class ApiLazyloading
{

    public $entity;
    public $constraint;

    public function __construct($entity = null, $constraint = null)
    {
        $this->entity = $entity;
        $this->constraint = $constraint;
    }

}