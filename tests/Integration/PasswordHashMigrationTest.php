<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE K.1/K.4 : password_hash()/password_verify() pour Dvups_admin et
 * User, avec migration TRANSPARENTE d'un ancien hash sha1() au prochain
 * login reussi (pas de reinitialisation forcee).
 */
final class PasswordHashMigrationTest extends DatabaseTestCase
{
    public function testLoginWithLegacySha1HashUpgradesToPasswordHash(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();
        // Le seed (config_data.sql) stocke le mot de passe au format legacy sha1().
        $this->assertSame(sha1('admin'), $admin->getPassword());

        $ok = $admin->verifyPassword('admin');

        $this->assertTrue($ok, 'le mot de passe correct doit etre accepte meme avec un hash legacy sha1()');

        $reloaded = \Dvups_admin::find($admin->getId());
        $this->assertNotSame(sha1('admin'), $reloaded->getPassword(), 'le hash doit avoir ete remplace apres un login reussi');
        $this->assertTrue(password_verify('admin', $reloaded->getPassword()), 'le nouveau hash doit etre un password_hash() valide pour le meme mot de passe');
    }

    public function testWrongPasswordIsRejectedAndHashIsNeverUpgraded(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();
        $originalHash = $admin->getPassword();

        $ok = $admin->verifyPassword('ce-nest-pas-le-mot-de-passe');

        $this->assertFalse($ok);
        $reloaded = \Dvups_admin::find($admin->getId());
        $this->assertSame($originalHash, $reloaded->getPassword(), 'un mot de passe incorrect ne doit jamais modifier le hash stocke');
    }

    public function testNewPasswordsAreHashedWithPasswordHashNotSha1(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();
        $admin->setPassword('un-nouveau-mot-de-passe');

        $this->assertNotSame(sha1('un-nouveau-mot-de-passe'), $admin->getPassword());
        $this->assertTrue(password_verify('un-nouveau-mot-de-passe', $admin->getPassword()));
    }

    public function testUserCoreLoginWithLegacySha1HashAlsoUpgrades(): void
    {
        $login = 'k1_' . uniqid();
        $user = new \User();
        $user->username = $login;
        $user->email = $login . '@example.test';
        $user->password = sha1('motdepasse123'); // simule un compte cree avant l'ETAPE K
        $userId = $user->__insert();

        $user = \User::find($userId);
        $this->assertTrue($user->verifyPassword('motdepasse123'));

        $reloaded = \User::find($userId);
        $this->assertNotSame(sha1('motdepasse123'), $reloaded->password);
        $this->assertTrue(password_verify('motdepasse123', $reloaded->password));
    }
}
