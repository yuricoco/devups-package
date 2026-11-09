<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE A.4 (v7.1) : Controller::formWithPost() traite toute valeur
 * "truthy" renvoyee par un setter d'entite comme un message d'erreur de
 * champ ("elseif ($error = call_user_func([$object, $setter], $value))
 * $this->error[$key] = $error;") - la convention du depot est donc qu'un
 * setter ne renvoie RIEN (ou null/false) en cas de succes, une CHAINE en
 * cas d'erreur de validation.
 *
 * Reportingmodel::setObject()/Notification::setEmittedBy()/setRedirect()/
 * Push_subscription::setEndpoint() violaient cette convention
 * ("return $this;", copie-collee d'un style de builder) : toute creation/
 * mise a jour via un formulaire admin exposant ces champs echouait
 * systematiquement, l'entite elle-meme serialisee comme "erreur"
 * (affichee cote client comme "[object Object]", TODO.md ETAPE V) -
 * corriges. Ce test balaie tout le depot pour empecher une regression,
 * la meme ou ailleurs.
 */
final class SetterReturnValueConventionTest extends TestCase
{
    public function testNoEntitySetterReturnsThisOrTrue(): void
    {
        $files = array_merge(
            glob(ROOT . 'src/*/*/Entity/*.php'),
            glob(ROOT . 'dclass/extends/*.php')
        );

        $offenders = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Une methode "public function setXxx(...) { ... }" (corps sur
            // une seule "passe" de niveau d'accolades, suffisant ici : ces
            // setters sont tous courts, deux lignes).
            if (!preg_match_all('/public function (set[A-Z]\w*)\s*\([^)]*\)\s*\{([^}]*)\}/s', $content, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $m) {
                [, $method, $body] = $m;
                // retire les commentaires "//" avant de chercher le motif,
                // pour ne pas se declencher sur une ligne qui en PARLE
                // (ex. ce fichier de test lui-meme, ou le commentaire
                // explicatif laisse sur chaque correctif).
                $codeOnly = preg_replace('/\/\/.*$/m', '', $body);
                if (preg_match('/return\s+\$this\s*;/', $codeOnly) || preg_match('/return\s+true\s*;/', $codeOnly)) {
                    $offenders[] = basename($file) . '::' . $method . '()';
                }
            }
        }

        $this->assertSame([], $offenders, "ces setters violent la convention (doivent renvoyer null/false/une chaine d'erreur, jamais \$this/true) :\n" . implode("\n", $offenders));
    }
}
