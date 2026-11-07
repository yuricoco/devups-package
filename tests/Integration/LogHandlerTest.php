<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * Log::installGlobalHandler() : verifie dans un processus PHP separe
 * (tests/Support/trigger_handler.php) pour ne jamais installer ces gardes
 * globales dans le processus PHPUnit lui-meme, ou elles resteraient
 * actives pour le reste de la suite.
 */
final class LogHandlerTest extends DatabaseTestCase
{
    private function runTrigger(string $mode, bool $prod): array
    {
        $env = getenv();
        $env['APP_PROD'] = $prod ? '1' : '0';

        $script = realpath(__DIR__ . '/../Support/scripts/trigger_handler.php');
        $process = proc_open(
            [PHP_BINARY, $script, $mode],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            realpath(__DIR__ . '/../..'),
            $env
        );
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }

    public function testUncaughtExceptionInProdShowsAGenericMessageAndIsLogged(): void
    {
        $result = $this->runTrigger('exception', true);

        $this->assertStringNotContainsString('boom simule', $result['stdout'], 'le detail ne doit pas fuiter en prod');
        $this->assertStringContainsString('erreur interne', $result['stdout']);

        $row = \Dvups_log::where('this.message', 'boom simule par trigger_handler.php')->firstOrNull();
        $this->assertNotNull($row, 'l\'exception non interceptee doit etre journalisee');
        $this->assertSame(\RuntimeException::class, $row->entity_class);
    }

    public function testUncaughtExceptionInDevShowsTheDetail(): void
    {
        $result = $this->runTrigger('exception', false);

        $this->assertStringContainsString('boom simule', $result['stdout']);
    }

    public function testFatalErrorIsCaughtByTheShutdownFunctionAndLogged(): void
    {
        $result = $this->runTrigger('fatal', true);

        $this->assertStringContainsString('erreur interne', $result['stdout']);

        $row = \Dvups_log::where('this.level', 'error')
            ->andwhere('this.message', 'like', '%undefined_function_boom_simule%')
            ->firstOrNull();
        $this->assertNotNull($row, 'l\'erreur fatale doit etre journalisee par register_shutdown_function');
    }
}
