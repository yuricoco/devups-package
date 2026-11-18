<?php

/**
 * ETAPE 3.2 (v8.0) : remplace la bibliotheque "Addendum" vendoree en
 * arbre (dclass/lib/addendum/, retiree par ce lot - voir TODO.md
 * ETAPE 3.0) qui parsait les docblocks pour offrir
 * ReflectionAnnotatedClass::hasAnnotation()/getAnnotation(). Les
 * annotations de routage (@Api, @Auth, @GET, @POST, @PUT, @DELETE,
 * @MethodView) sont desormais des attributs PHP 8 natifs
 * (dclass/lib/annotation/*.php) - ces deux classes en lisent la
 * metadonnee via ReflectionClass::getAttributes()/
 * ReflectionMethod::getAttributes() au lieu de parser un commentaire.
 *
 * API conservee a l'identique (meme nom de classe, memes methodes) :
 * dclass/devups/Http/Router.php et dclass/devups/Http/Request.php, les
 * deux seuls consommateurs reels (verifie), n'ont besoin d'AUCUNE
 * modification - meme pattern que Devups\View au lot 2 (BladeD).
 *
 * Les annotations de routage sont toutes des classes globales (pas de
 * namespace) - les noms courts passes a hasAnnotation('Auth')/
 * getAnnotation('GET') resolvent donc directement sans ambiguite.
 */
class ReflectionAnnotatedMethod extends ReflectionMethod
{
    public function hasAnnotation(string $name): bool
    {
        return count($this->getAttributes($name)) > 0;
    }

    public function getAnnotation(string $name)
    {
        $attributes = $this->getAttributes($name);

        return $attributes ? $attributes[0]->newInstance() : null;
    }
}

class ReflectionAnnotatedClass extends ReflectionClass
{
    public function hasAnnotation(string $name): bool
    {
        return count($this->getAttributes($name)) > 0;
    }

    public function getAnnotation(string $name)
    {
        $attributes = $this->getAttributes($name);

        return $attributes ? $attributes[0]->newInstance() : null;
    }

    public function getMethod(string $name): ReflectionAnnotatedMethod
    {
        return new ReflectionAnnotatedMethod($this->getName(), $name);
    }

    public function getMethods(?int $filter = null): array
    {
        $methods = $filter === null ? parent::getMethods() : parent::getMethods($filter);

        return array_map(
            fn (ReflectionMethod $method) => new ReflectionAnnotatedMethod($this->getName(), $method->getName()),
            $methods
        );
    }
}
