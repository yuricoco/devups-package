<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

final class RightsSyncTest extends DatabaseTestCase
{
    public function testSyncDeclaresTheSixDefaultActionsForAKnownEntity(): void
    {
        \Dvups_action::sync();

        foreach (\Dvups_action::DEFAULT_ACTIONS as $action) {
            $row = \Dvups_action::where('entity', 'Dvups_role')->andwhere('action', $action)->firstOrNull();
            $this->assertNotNull($row, "action '$action' absente pour Dvups_role");
            $this->assertTrue((bool)$row->getId());
        }
    }

    public function testSyncIsIdempotent(): void
    {
        \Dvups_action::sync();
        $before = $this->countRows('dvups_action');

        $result = \Dvups_action::sync();

        $this->assertSame(0, $result['created']);
        $this->assertSame($before, $this->countRows('dvups_action'));
    }

    public function testSyncNeverRemovesAnExistingAction(): void
    {
        \Dvups_action::sync();
        \Dvups_action::create(['entity' => 'Dvups_role', 'action' => 'custom_manual', 'label' => 'Manuelle']);

        \Dvups_action::sync();

        $row = \Dvups_action::where('entity', 'Dvups_role')->andwhere('action', 'custom_manual')->firstOrNull();
        $this->assertNotNull($row);
    }
}
