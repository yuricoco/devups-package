<?php

/**
 * ETAPE E.1 (v7.1) - levee par ModelTrait::__get() pour toute propriete
 * non definie (ni une colonne connue, ni une colonne traduite pas encore
 * hydratee, ni la convention "_attribut" de lazyloading des relations) -
 * remplace l'ancien `trigger_error()` + `return null` silencieux (lui-meme
 * un remplacement anterieur d'un `die()` encore plus silencieux en prod).
 * `\LogicException` : erreur de programmation (faute de frappe, colonne
 * renommee/supprimee), jamais une condition runtime attendue - attrapee et
 * journalisee par le gestionnaire global (Log::installGlobalHandler()).
 */
class UndefinedPropertyException extends \LogicException
{
}
