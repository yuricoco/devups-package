<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

final class AdminAuthTest extends HttpTestCase
{
    public function testLoginPageRenders(): void
    {
        $res = $this->get('admin/login.php');
        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('name="password"', $res['body']);
        $this->assertNoServerError('GET admin/login.php');
    }

    public function testWrongPasswordIsRejected(): void
    {
        $res = $this->loginAdmin('dv_admin', 'mauvais');
        $this->assertSame(302, $res['status']);
        $this->assertStringContainsString('admin/login.php', (string)$res['location']);
        $this->assertNoServerError('connexion refusee');
    }

    public function testValidLoginOpensTheBackOffice(): void
    {
        $res = $this->loginAdmin();
        $this->assertSame(302, $res['status']);
        $this->assertStringNotContainsString('login.php', (string)$res['location']);

        $dashboard = $this->get('admin/dashboard');
        $this->assertSame(200, $dashboard['status']);
        $this->assertStringContainsString('</html>', $dashboard['body']);
        $this->assertNoServerError('GET admin/dashboard');
    }
}
