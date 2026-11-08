<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE K.3 : "remember me" sans mot de passe (ni son hash) dans le cookie -
 * un token aleatoire dont seul le hash (sha256) est stocke en base, avec
 * expiration, rotation a chaque usage, et invalidation a la deconnexion/au
 * changement de mot de passe.
 */
final class RememberMeTest extends DatabaseTestCase
{
    private function freshAdmin(): \Dvups_admin
    {
        return \Dvups_admin::select()->where('login', 'dv_admin')->first();
    }

    public function testIssuedTokenVerifiesSuccessfully(): void
    {
        $admin = $this->freshAdmin();
        $token = $admin->issueRememberToken();

        $this->assertNotEmpty($token);
        $this->assertTrue($admin->verifyRememberToken($token));
        $this->assertFalse($admin->verifyRememberToken('un-autre-token'));
    }

    public function testCookieNeverStoresThePasswordOrItsHash(): void
    {
        $admin = $this->freshAdmin();
        $hashBefore = $admin->getPassword();
        $token = $admin->issueRememberToken();

        $this->assertNotSame($hashBefore, $token);
        $this->assertNotSame($admin->getPassword(), $token);
        // le token n'est pas non plus un hash sha1()/md5() du mot de passe
        $this->assertNotSame(sha1('admin'), $token);
    }

    public function testRotationInvalidatesTheStolenPreviousToken(): void
    {
        $admin = $this->freshAdmin();
        $stolenToken = $admin->issueRememberToken();
        $this->assertTrue($admin->verifyRememberToken($stolenToken));

        // usage legitime suivant : le token est RENOUVELE (rotation)
        $newToken = $admin->issueRememberToken();

        $this->assertNotSame($stolenToken, $newToken);
        $this->assertFalse($admin->verifyRememberToken($stolenToken), 'un token "remember me" vole (deja consomme) ne doit plus jamais etre accepte apres rotation');
        $this->assertTrue($admin->verifyRememberToken($newToken));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $admin = $this->freshAdmin();
        $token = $admin->issueRememberToken(-1); // deja expire

        $this->assertFalse($admin->verifyRememberToken($token));
    }

    public function testLogoutInvalidatesTheRememberToken(): void
    {
        $admin = $this->freshAdmin();
        $token = $admin->issueRememberToken();
        $this->assertTrue($admin->verifyRememberToken($token));

        $admin->clearRememberToken();

        $this->assertFalse($admin->verifyRememberToken($token));
        $reloaded = \Dvups_admin::find($admin->getId());
        $this->assertNull($reloaded->getRememberTokenHash());
    }

    public function testChangingThePasswordInvalidatesTheRememberToken(): void
    {
        $admin = $this->freshAdmin();
        $token = $admin->issueRememberToken();

        $this->assertTrue($admin->updatePassword('admin', 'un-nouveau-mot-de-passe'));

        $this->assertFalse($admin->verifyRememberToken($token), 'un cookie "remember me" vole avant un changement de mot de passe ne doit plus donner acces apres');
    }
}
