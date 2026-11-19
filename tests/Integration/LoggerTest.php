<?php

namespace Devups\Tests\Integration;

use Devups\Logger;
use Devups\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * LOT 5.5 (v8.0) : `Devups\Logger`, adaptateur PSR-3 au-dessus de `\Log`.
 * Verifie seulement la traduction de niveaux et le passage du contexte -
 * le comportement de persistance lui-meme (agregation, debordement
 * fichier, purge) est deja couvert par `tests/Integration/LogTest.php`.
 */
final class LoggerTest extends DatabaseTestCase
{
    public function testImplementsPsr3LoggerInterface(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, new Logger());
    }

    #[DataProvider('severeLevelsProvider')]
    public function testEmergencyAlertCriticalAndErrorMapToLogError(string $method): void
    {
        $logger = new Logger();
        $message = 'logger adapter - ' . $method;

        $logger->{$method}($message);

        $row = \Dvups_log::where('this.message', $message)->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame('error', $row->level);
    }

    public static function severeLevelsProvider(): array
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
        ];
    }

    public function testWarningMapsToLogWarning(): void
    {
        $logger = new Logger();
        $logger->warning('logger adapter - warning');

        $row = \Dvups_log::where('this.message', 'logger adapter - warning')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame('warning', $row->level);
    }

    #[DataProvider('mildLevelsProvider')]
    public function testNoticeInfoAndDebugMapToLogInfo(string $method): void
    {
        $logger = new Logger();
        $message = 'logger adapter - ' . $method;

        $logger->{$method}($message);

        $row = \Dvups_log::where('this.message', $message)->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame('info', $row->level);
    }

    public static function mildLevelsProvider(): array
    {
        return [
            ['notice'],
            ['info'],
            ['debug'],
        ];
    }

    public function testGenericLogDispatchesToTheMappedLevel(): void
    {
        $logger = new Logger();
        $logger->log('warning', 'logger adapter - generic log');

        $row = \Dvups_log::where('this.message', 'logger adapter - generic log')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame('warning', $row->level);
    }

    public function testUnknownLevelThrowsInvalidArgumentException(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->log('not-a-psr3-level', 'peu importe');
    }

    /**
     * Convention PSR-3 : `$context['exception']` prime sur le message texte
     * - c'est le `\Throwable` qui doit etre transmis a `\Log::`, pour garder
     * la vraie classe/fichier/ligne capturee par `\Log::write()`.
     */
    public function testExceptionInContextIsLoggedWithItsOwnClassAndLocation(): void
    {
        $logger = new Logger();

        try {
            throw new \RuntimeException('logger adapter - exception en contexte');
        } catch (\RuntimeException $e) {
            $logger->error('message texte ignore au profit de l\'exception', ['exception' => $e]);
        }

        $row = \Dvups_log::where('this.message', 'logger adapter - exception en contexte')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame(\RuntimeException::class, $row->entity_class);
        $this->assertSame(__FILE__, $row->file);
    }

    public function testArbitraryContextIsPassedThrough(): void
    {
        $logger = new Logger();
        $logger->info('logger adapter - contexte', ['foo' => 'bar']);

        $row = \Dvups_log::where('this.message', 'logger adapter - contexte')->firstOrNull();
        $this->assertNotNull($row);
        $context = json_decode($row->context, true);
        $this->assertSame('bar', $context['foo']);
    }

    /**
     * Quand `exception` est present dans le contexte, c'est lui qui devient
     * le sujet du log (voir testExceptionInContextIsLoggedWithItsOwnClassAndLocation)
     * - la cle `exception` elle-meme ne doit donc jamais se retrouver dans le
     * contexte JSON persiste (un `\Throwable` brut ferait echouer son
     * `json_encode()`).
     */
    public function testExceptionKeyIsRemovedFromThePersistedContext(): void
    {
        $logger = new Logger();

        try {
            throw new \RuntimeException('logger adapter - cle exception retiree');
        } catch (\RuntimeException $e) {
            $logger->error('ignore', ['foo' => 'bar', 'exception' => $e]);
        }

        $row = \Dvups_log::where('this.message', 'logger adapter - cle exception retiree')->firstOrNull();
        $this->assertNotNull($row);
        $context = json_decode($row->context, true);
        $this->assertSame('bar', $context['foo']);
        $this->assertArrayNotHasKey('exception', $context);
    }
}
