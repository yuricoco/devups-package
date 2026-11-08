<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : parcours "mot de passe oublie" de bout en bout sur l'endpoint
 * reel /api/user/resetpassword. Le jeton est genere directement via
 * User::setResetPasswordToken() (meme code que UserFrontController::
 * issueResetPasswordToken()) plutot que via /api/user/initresetpassword,
 * pour un utilisateur SANS email : issueResetPasswordToken() ne tente
 * l'envoi SMTP que si $user->email est renseigne (if ($user->email) {...}),
 * donc aucune connexion reseau reelle n'est declenchee ici - seul le code
 * de generation/validation du jeton et l'endpoint de reset sont exerces.
 */
final class PasswordResetViaTokenEndpointTest extends HttpTestCase
{
    private ?int $userId = null;

    protected function tearDown(): void
    {
        if ($this->userId !== null) {
            $pdo = \Model::pdo();
            $pdo->exec("DELETE FROM user WHERE id = " . $this->userId);
        }
        parent::tearDown();
    }

    public function testResetPasswordWithValidTokenChangesPasswordAndIsSingleUse(): void
    {
        $user = new \User();
        $user->username = 'etapev4reset';
        $user->is_activated = 1;
        $user->setPassword('OldPassw0rd!EtapeV4');
        $user->__insert();
        $this->userId = (int)$user->getId();
        $this->assertGreaterThan(0, $this->userId);

        // pas d'email -> issueResetPasswordToken() n'enverrait aucun mail ;
        // ici on appelle directement le meme generateur de jeton qu'elle utilise.
        $token = $user->setResetPasswordToken();
        $user->__update();

        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'api/user/resetpassword', [
            'token' => $token,
            'password' => 'NewPassw0rd!EtapeV4',
        ]);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse resetpassword :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur resetpassword :\n" . implode("\n", $errors));
        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "reset de mot de passe echoue :\n" . $res['body']);
        $this->assertNotEmpty($data['jwt'] ?? null, "aucun JWT emis apres reset reussi");

        // le jeton est a usage unique : une seconde tentative avec le MEME jeton doit echouer
        $resReplay = $this->request('POST', 'api/user/resetpassword', [
            'token' => $token,
            'password' => 'AnotherPassw0rd!EtapeV4',
        ]);
        $dataReplay = $this->json($resReplay);
        $this->assertFalse($dataReplay['success'] ?? true, "le meme jeton de reset ne doit pas pouvoir etre reutilise");

        // le nouveau mot de passe doit permettre la connexion, l'ancien non
        $reloaded = \User::find($this->userId);
        $this->assertTrue($reloaded->verifyPassword('NewPassw0rd!EtapeV4'), "le nouveau mot de passe doit etre actif");
        $this->assertFalse($reloaded->verifyPassword('OldPassw0rd!EtapeV4'), "l'ancien mot de passe ne doit plus fonctionner");
    }

    public function testResetPasswordWithInvalidTokenReturnsCleanError(): void
    {
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->request('POST', 'api/user/resetpassword', [
            'token' => 'ce-jeton-n-existe-pas',
            'password' => 'PeuImporte123!',
        ]);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame(200, $res['status'], "reponse resetpassword jeton invalide :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP sur resetpassword avec jeton invalide :\n" . implode("\n", $errors));
        $data = $this->json($res);
        $this->assertFalse($data['success'] ?? true, "un jeton invalide ne doit jamais reussir");
    }
}
