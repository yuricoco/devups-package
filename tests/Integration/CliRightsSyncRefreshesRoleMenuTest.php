<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
 * le menu lateral (admin/views/layout/sidebar.blade.php) lit
 * Dvups_role::getConfigs() (colonne `configs`, un JSON materialise par
 * Dvups_role::updateConfigs()) et non dvups_action/
 * config/dvups_configurations.php directement. Avant ce correctif,
 * "php devups rights:sync" synchronisait dvups_action mais ne
 * rafraichissait jamais ce cache par role : un lien de menu supprime
 * (ou ajoute) par une migration restait visible (ou absent) jusqu'a la
 * prochaine sauvegarde manuelle d'un role via l'ecran Roles.
 */
final class CliRightsSyncRefreshesRoleMenuTest extends TestCase
{
    public function testRightsSyncReportsHowManyRolesWereRefreshed(): void
    {
        $output = (string)shell_exec(
            'cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups rights:sync 2>&1'
        );

        $this->assertStringContainsString('action(s) synchronisee(s)', $output);
        $this->assertMatchesRegularExpression('/\d+ role\(s\) : menu rafraichi/', $output);
    }
}
