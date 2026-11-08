<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * POSTer sur /api/user/authentification sans "login" (ou sans "password")
 * levait "Undefined array key" (warning PHP) avant de tomber dans la
 * validation empty($login). Corrige via $_POST['login'] ?? null /
 * $_POST['password'] ?? null dans UserFrontController::authentification().
 */
final class UserAuthentificationMissingFieldsTest extends HttpTestCase
{
    public function testMissingCredentialsReturnsCleanValidationErrorWithoutPhpWarning(): void
    {
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'api/user/authentification', []);

        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur authentification sans champs :\n" . implode("\n", $errors));

        $data = $this->json($res);
        $this->assertFalse($data['success'] ?? true, "l'authentification sans identifiants ne doit pas reussir");
    }

    public function testMissingPasswordOnlyReturnsCleanValidationErrorWithoutPhpWarning(): void
    {
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'api/user/authentification', [
            'login' => 'nobody@example.invalid',
        ]);

        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur authentification sans password :\n" . implode("\n", $errors));

        $data = $this->json($res);
        $this->assertFalse($data['success'] ?? true, "l'authentification sans mot de passe ne doit pas reussir");
    }
}
