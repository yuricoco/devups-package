<?php

namespace Devups\Tests\Unit;

use dclass\devups\Controller\FrontController;
use PHPUnit\Framework\TestCase;

final class FrontControllerOwnershipTest extends TestCase
{
    private array $backup;

    protected function setUp(): void
    {
        $this->backup = FrontController::getOwnerColumns();
    }

    protected function tearDown(): void
    {
        FrontController::setOwnerColumns($this->backup, false);
    }

    public function testFrameworkDeclaresNoApplicationEntity(): void
    {
        FrontController::setOwnerColumns([], false);
        $this->assertSame([], FrontController::getOwnerColumns());
    }

    public function testOwnerColumnsCanBeMergedOrReplaced(): void
    {
        FrontController::setOwnerColumns(['post' => 'user_id'], false);
        FrontController::setOwnerColumns(['follow' => 'follower_id']);
        $this->assertSame(['post' => 'user_id', 'follow' => 'follower_id'], FrontController::getOwnerColumns());

        FrontController::setOwnerColumns(['comment' => 'author_id'], false);
        $this->assertSame(['comment' => 'author_id'], FrontController::getOwnerColumns());
    }
}
