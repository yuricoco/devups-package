<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

final class AdminAccountTest extends DatabaseTestCase
{
    public function testSeedCreatesMasterAdmin(): void
    {
        $admin = \Dvups_admin::select()->where('login', 'dv_admin')->first();
        $this->assertTrue((bool)$admin->getId(), "l'installation doit creer le compte dv_admin");
    }

    public function testLoginLookupMatchesOnlyTheRightPassword(): void
    {
        $ok = \Dvups_admin::select()->where('login', 'dv_admin')->andwhere('password', sha1('admin'))->first();
        $ko = \Dvups_admin::select()->where('login', 'dv_admin')->andwhere('password', sha1('wrong'))->first();
        $this->assertTrue((bool)$ok->getId());
        $this->assertFalse((bool)$ko->getId());
    }

    public function testDefaultLanguagesAreSeeded(): void
    {
        $isos = array_map(fn($l) => $l->iso_code, \Dvups_lang::all());
        $this->assertContains('fr', $isos);
        $this->assertContains('en', $isos);
    }
}
