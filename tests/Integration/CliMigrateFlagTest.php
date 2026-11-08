<?php

namespace Devups\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE V : le dispatcher CLI (dclass/generator/builder.php) traitait
 * TOUT $argv[2] comme un nom de projet (getproject($argv[2])), y compris
 * pour des commandes qui n'en prennent pas (migrate, migrate:status...)
 * et dont $argv[2] est en realite un flag ("--dry-run", "--force"...) -
 * "php devups migrate --dry-run" echouait avec
 * "ERROR : projectCore '--dry-run' not found" sans jamais atteindre
 * MigrationRunner. Corrige en ignorant tout $argv[2] commencant par
 * "--" lors de la resolution du projet.
 */
final class CliMigrateFlagTest extends TestCase
{
    public function testDryRunFlagAsArgv2IsNotMistakenForAProjectName(): void
    {
        $output = (string)shell_exec(
            'cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups migrate --dry-run 2>&1'
        );

        $this->assertStringNotContainsString('projectCore', $output);
        $this->assertStringNotContainsString('ERROR', $output);
    }

    public function testStatusStillWorksWithNoArgv2AtAll(): void
    {
        $output = (string)shell_exec(
            'cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg(PHP_BINARY) . ' devups migrate:status 2>&1'
        );

        $this->assertStringNotContainsString('projectCore', $output);
        $this->assertStringNotContainsString('ERROR', $output);
    }
}
