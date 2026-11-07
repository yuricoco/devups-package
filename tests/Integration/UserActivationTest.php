<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

final class UserActivationTest extends DatabaseTestCase
{
    private function makeUser(int $activated = 0): \User
    {
        $id = \User::create([
            'username' => 'phpunit' . uniqid(),
            'email' => null,
            'is_activated' => $activated,
        ]);
        return \User::find($id);
    }

    public function testResendRegeneratesTheStoredHash(): void
    {
        $user = $this->makeUser();
        $before = $user->activationcode;

        $result = \RegistrationController::resendactivationcode($user);

        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $result['activationcode']);
        $stored = \User::find($user->getId())->activationcode;
        $this->assertNotSame($before, $stored);
        $this->assertSame(sha1($result['activationcode']), $stored);
    }

    public function testResendRefusesAnActivatedAccount(): void
    {
        $result = \RegistrationController::resendactivationcode($this->makeUser(1));
        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('activationcode', $result);
    }

    public function testPublicProfileHidesPersonalFields(): void
    {
        $id = \User::create(['username' => 'phpunit-public', 'email' => 'secret@devups.test', 'phonenumber' => '600000000']);
        $json = json_decode(json_encode(\User::find($id)->restrictToPublicProfile()), true);

        $this->assertSame('phpunit-public', $json['username'] ?? null);
        $this->assertArrayNotHasKey('email', $json);
        $this->assertArrayNotHasKey('phonenumber', $json);
        $this->assertArrayNotHasKey('password', $json);
    }
}
