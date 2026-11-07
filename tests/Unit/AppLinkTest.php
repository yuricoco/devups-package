<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AppLinkTest extends TestCase
{
    public function testFallsBackToFrontUrlWithoutConfiguration(): void
    {
        // sans base (suite unit) ou sans cle APP_DEEPLINK : __env_front
        $link = \UserFrontController::appLink('auth/activate-account');
        $this->assertStringEndsWith('/auth/activate-account', $link);
        $this->assertStringNotContainsString('//auth', $link);
    }
}
