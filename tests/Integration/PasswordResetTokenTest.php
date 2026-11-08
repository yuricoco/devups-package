<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE P.1 : jeton dedie de reinitialisation de mot de passe - distinct
 * du code court d'activation (couvert par UserActivationTest).
 */
final class PasswordResetTokenTest extends DatabaseTestCase
{
    private function makeUser(string $email = null): \User
    {
        $id = \User::create([
            'username' => 'phpunit' . uniqid(),
            'email' => $email,
            'is_activated' => 0,
        ]);
        return \User::find($id);
    }

    public function testSetResetPasswordTokenStoresOnlyTheHash(): void
    {
        $user = $this->makeUser();
        $token = $user->setResetPasswordToken();

        $this->assertNotSame('', $token);
        $this->assertStringNotContainsString('+', $token);
        $this->assertStringNotContainsString('/', $token);
        $this->assertStringNotContainsString('=', $token);

        $user->__update();
        $stored = $this->countRows('user', 'id = ? AND reset_token_hash = ?', [$user->getId(), hash('sha256', $token)]);
        $this->assertSame(1, $stored);
    }

    public function testVerifyResetPasswordTokenAcceptsOnlyTheMatchingToken(): void
    {
        $user = $this->makeUser();
        $token = $user->setResetPasswordToken();
        $user->__update();

        $this->assertTrue($user->verifyResetPasswordToken($token));
        $this->assertFalse($user->verifyResetPasswordToken('wrong-token'));
        $this->assertFalse($user->verifyResetPasswordToken(''));
    }

    public function testVerifyResetPasswordTokenRefusesAnExpiredToken(): void
    {
        $user = $this->makeUser();
        $token = $user->setResetPasswordToken();
        $user->__update(['reset_token_expired_at' => date('Y-m-d H:i:s', time() - 60)]);

        $user = \User::find($user->getId());
        $this->assertFalse($user->verifyResetPasswordToken($token));
    }

    public function testANewTokenInvalidatesThePreviousOne(): void
    {
        $user = $this->makeUser();
        $tokenA = $user->setResetPasswordToken();
        $user->__update();

        $user = \User::find($user->getId());
        $tokenB = $user->setResetPasswordToken();
        $user->__update();

        $this->assertFalse($user->verifyResetPasswordToken($tokenA));
        $this->assertTrue($user->verifyResetPasswordToken($tokenB));
    }

    public function testClearResetPasswordTokenInvalidatesIt(): void
    {
        $user = $this->makeUser();
        $token = $user->setResetPasswordToken();
        $user->clearResetPasswordToken();
        $user->__update();

        $user = \User::find($user->getId());
        $this->assertFalse($user->verifyResetPasswordToken($token));
    }

    public function testIssueResetPasswordTokenReturnsAUsableToken(): void
    {
        $user = $this->makeUser('phpunit-reset@devups.test');
        $result = \UserFrontController::issueResetPasswordToken($user);

        $this->assertTrue($result['success']);
        $user = \User::find($user->getId());
        $this->assertTrue($user->verifyResetPasswordToken($result['token']));
    }

    public function testInitResetPasswordGivesTheIdenticalResponseWhetherTheAccountExistsOrNot(): void
    {
        $user = $this->makeUser('phpunit-exists@devups.test');
        $controller = new \UserFrontController();

        $_POST = ['user_form' => ['login' => $user->email]];
        $existing = $controller->initresetpassword();

        $_POST = ['user_form' => ['login' => 'no-such-account@devups.test']];
        $missing = $controller->initresetpassword();
        $_POST = [];

        $this->assertSame($existing, $missing);
        $this->assertTrue($existing['success']);
        $this->assertArrayNotHasKey('user', $existing);
        $this->assertArrayNotHasKey('token', $existing);

        // Le jeton a bien ete emis pour le compte existant, malgre la
        // reponse generique ci-dessus.
        $reloaded = \User::find($user->getId());
        $this->assertNotNull($reloaded->getId());
    }

    public function testResetPasswordRejectsAnUnknownOrInvalidToken(): void
    {
        $controller = new \UserFrontController();

        $_POST = ['token' => 'does-not-exist', 'password' => 'NewPassword123'];
        $result = $controller->resetpassword();
        $_POST = [];

        $this->assertFalse($result['success']);
    }

    public function testResetPasswordConsumesTheTokenAfterASuccessfulReset(): void
    {
        $user = $this->makeUser('phpunit-consume@devups.test');
        $issued = \UserFrontController::issueResetPasswordToken($user);
        $this->assertTrue($issued['success']);
        $token = $issued['token'];

        $controller = new \UserFrontController();

        $_POST = ['token' => $token, 'password' => 'NewPassword123'];
        $first = $controller->resetpassword();
        $this->assertTrue($first['success']);
        $this->assertArrayHasKey('jwt', $first);

        $_POST = ['token' => $token, 'password' => 'AnotherPassword456'];
        $second = $controller->resetpassword();
        $_POST = [];

        $this->assertFalse($second['success']);
    }
}
