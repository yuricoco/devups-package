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

        // D.5 : en CLI, respondWithFatal() ecrit du texte brut sur STDERR (jamais de HTML).
        $this->assertStringNotContainsString('boom simule', $result['stderr'], 'le detail ne doit pas fuiter en prod');
        $this->assertStringContainsString('erreur interne', $result['stderr']);

        $row = \Dvups_log::where('this.message', 'boom simule par trigger_handler.php')->firstOrNull();
        $this->assertNotNull($row, 'l\'exception non interceptee doit etre journalisee');
        $this->assertSame(\RuntimeException::class, $row->entity_class);
    }

    public function testUncaughtExceptionInDevShowsTheDetail(): void
    {
        $result = $this->runTrigger('exception', false);

        $this->assertStringContainsString('boom simule', $result['stderr']);
    }

    public function testFatalErrorIsCaughtByTheShutdownFunctionAndLogged(): void
    {
        $result = $this->runTrigger('fatal', true);

        $this->assertStringContainsString('erreur interne', $result['stderr']);
        $this->assertStringNotContainsString('<h1>', $result['stderr'], 'D.5 : en CLI, la reponse doit etre du texte brut, pas du HTML');

        $row = \Dvups_log::where('this.level', 'error')
            ->andwhere('this.message', 'like', '%undefined_function_boom_simule%')
            ->firstOrNull();
        $this->assertNotNull($row, 'l\'erreur fatale doit etre journalisee par register_shutdown_function');
    }

    public function testFatalErrorIsLoggedWithItsOwnFileAndLineNotLogPhp(): void
    {
        $this->runTrigger('fatal', true);

        $row = \Dvups_log::where('this.level', 'error')
            ->andwhere('this.message', 'like', '%undefined_function_boom_simule%')
            ->firstOrNull();
        $this->assertNotNull($row);
        $this->assertStringContainsString('trigger_handler.php', $row->file, 'D.1 : fichier = error_get_last(), pas Log.php');
        $this->assertStringNotContainsString('Log.php', $row->file);
    }

    public function testWarningIsLoggedWithTheCallerFileAndLine(): void
    {
        $result = $this->runTrigger('warning', false);

        $this->assertMatchesRegularExpression('/LINE:\d+/', $result['stdout']);
        $expectedLine = (int)substr($result['stdout'], strpos($result['stdout'], 'LINE:') + 5);

        $row = \Dvups_log::where('this.message', 'avertissement simule par trigger_handler.php')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertStringContainsString('trigger_handler.php', $row->file, 'D.1 : fichier/ligne passes par PHP au gestionnaire, pas Log.php');
        $this->assertSame($expectedLine, (int)$row->line);
    }

    public function testWarningSuppressedByAtOperatorIsNeverLogged(): void
    {
        $this->runTrigger('suppressed', false);

        $row = \Dvups_log::where('this.message', 'avertissement masque par trigger_handler.php')->firstOrNull();
        $this->assertNull($row, 'D.2 : @ met error_reporting() a 0, ne doit jamais etre journalise');
    }

    public function testNoticeIsIgnoredByDefaultLogMinLevel(): void
    {
        $this->runTrigger('notice', false);

        $row = \Dvups_log::where('this.message', 'notice simule par trigger_handler.php')->firstOrNull();
        $this->assertNull($row, 'D.2 : E_USER_NOTICE (niveau info) est sous LOG_MIN_LEVEL par defaut (warning)');
    }
}
