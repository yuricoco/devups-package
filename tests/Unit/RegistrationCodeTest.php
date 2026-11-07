<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RegistrationCodeTest extends TestCase
{
    public function testDefaultCodeIsSixDigits(): void
    {
        $code = \RegistrationController::generatecode();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    public function testCustomLength(): void
    {
        $this->assertSame(10, strlen(\RegistrationController::generatecode(10)));
    }

    public function testCodesAreNotConstant(): void
    {
        $codes = [];
        for ($i = 0; $i < 50; $i++)
            $codes[] = \RegistrationController::generatecode();
        $this->assertGreaterThan(40, count(array_unique($codes)));
    }

    public function testUserStoresOnlyTheHashOfTheCode(): void
    {
        $user = new \User();
        $user->setActivationcode('123456');
        $this->assertNotSame('123456', $user->activationcode);
        $this->assertSame(sha1('123456'), $user->activationcode);
    }
}
