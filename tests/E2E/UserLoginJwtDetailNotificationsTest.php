<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE V.4 : parcours front/API complet (login -> JWT -> detail -> JWT
 * reemis -> notifications) pour un utilisateur deja active, construit
 * directement en base (on evite /api/user/registration qui tenterait un
 * vrai envoi SMTP vers mail.3ag-edition.com - risque reseau/effet de bord
 * hors de portee de ce test). Verifie aussi qu'un tiers non authentifie ne
 * recoit que le profil public (email/telephone masques) - restrictForRequester()
 * / restrictToPublicProfile() deja couverts par ailleurs, mais jamais via
 * le endpoint reel /api/user/:id de bout en bout.
 */
final class UserLoginJwtDetailNotificationsTest extends HttpTestCase
{
    private ?int $userId = null;
    private const PASSWORD = 'Str0ng!Passw0rd_EtapeV4';
    private const EMAIL = 'etapev4-e2e@example.invalid';

    protected function tearDown(): void
    {
        if ($this->userId !== null) {
            $pdo = \Model::pdo();
            $pdo->exec("DELETE FROM notification WHERE user_id = " . $this->userId);
            $pdo->exec("DELETE FROM user WHERE id = " . $this->userId);
        }
        parent::tearDown();
    }

    public function testLoginJwtDetailAndNotificationsRoundTrip(): void
    {
        $user = new \User();
        $user->email = self::EMAIL;
        $user->username = 'etapev4e2e';
        $user->firstname = 'EtapeV4';
        $user->lastname = 'Regression';
        $user->is_activated = 1;
        $user->setPassword(self::PASSWORD);
        $user->__insert();
        $this->userId = (int)$user->getId();
        $this->assertGreaterThan(0, $this->userId, "creation de l'utilisateur de test echouee");

        \Devups\Tests\Support\HttpServer::newErrors();

        // 1. login
        $res = $this->request('POST', 'api/user/authentification', [
            'login' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();
        $this->assertSame(200, $res['status'], "reponse login :\n" . $res['body']);
        $this->assertSame([], $errors, "erreurs PHP au login :\n" . implode("\n", $errors));
        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "login echoue :\n" . $res['body']);
        $jwt = $data['jwt'] ?? null;
        $this->assertNotEmpty($jwt, "aucun JWT emis au login");
        // le detail inclus dans la reponse de login est celui du titulaire : email visible
        $this->assertSame(self::EMAIL, $data['user']['email'] ?? null, "l'email du titulaire doit etre visible sur sa propre reponse de login");

        // 2. detail via /api/user/:id, SANS authentification -> vue publique (pas d'email/telephone)
        $resPublic = $this->get('api/user/' . $this->userId);
        $this->assertSame(200, $resPublic['status'], "reponse detail public :\n" . $resPublic['body']);
        $dataPublic = $this->json($resPublic);
        $this->assertArrayNotHasKey('email', $dataPublic['user'] ?? [], "l'email ne doit jamais apparaitre sur le profil public");
        $this->assertArrayNotHasKey('phonenumber', $dataPublic['user'] ?? [], "le telephone ne doit jamais apparaitre sur le profil public");
        $this->assertSame('etapev4e2e', $dataPublic['user']['username'] ?? null, "le username doit rester visible sur le profil public");

        // 3. detail via /api/user/:id AVEC le JWT du titulaire -> email visible
        $resSelf = $this->get('api/user/' . $this->userId, ['Authorization: Bearer ' . $jwt]);
        $this->assertSame(200, $resSelf['status'], "reponse detail titulaire :\n" . $resSelf['body']);
        $dataSelf = $this->json($resSelf);
        $this->assertSame(self::EMAIL, $dataSelf['user']['email'] ?? null, "l'email doit etre visible pour le titulaire authentifie");

        // 4. /api/user/me avec le JWT -> doit retourner le meme utilisateur + notification_unreaded
        $resMe = $this->get('api/user/me', ['Authorization: Bearer ' . $jwt]);
        $this->assertSame(200, $resMe['status'], "reponse /me :\n" . $resMe['body']);
        $dataMe = $this->json($resMe);
        $this->assertSame($this->userId, (int)($dataMe['user']['id'] ?? 0), "l'id retourne par /me doit correspondre au titulaire du JWT");
        $this->assertArrayHasKey('notification_unreaded', $dataMe, "/me doit exposer le compteur de notifications non lues");

        // 5. notifications : aucune notification pour cet utilisateur neuf -> liste vide, pas d'erreur
        $resNotif = $this->get('api/user/notifications', ['Authorization: Bearer ' . $jwt]);
        $this->assertSame(200, $resNotif['status'], "reponse notifications :\n" . $resNotif['body']);
        $errorsFinal = \Devups\Tests\Support\HttpServer::newErrors();
        $this->assertSame([], $errorsFinal, "erreurs PHP sur le parcours login/detail/me/notifications :\n" . implode("\n", $errorsFinal));
    }

    public function testMeWithoutJwtReturnsCleanUnauthorizedResponse(): void
    {
        \Devups\Tests\Support\HttpServer::newErrors();

        $res = $this->get('api/user/me');
        $errors = \Devups\Tests\Support\HttpServer::newErrors();

        $this->assertSame([], $errors, "erreurs PHP sur /me sans jeton :\n" . implode("\n", $errors));
        $this->assertNotSame(200, $res['status'], "/me sans jeton ne doit jamais reussir");
    }
}
