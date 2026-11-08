<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * Log::error()/warning()/info() : regroupement par empreinte (classe +
 * fichier + ligne + message normalise), debordement vers un fichier quand
 * message/trace sont trop longs, purge.
 */
final class LogTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        foreach (glob(ROOT . 'cache/log/*/*.log') ?: [] as $file)
            @unlink($file);
        parent::tearDown();
    }

    /**
     * L'empreinte inclut le fichier+la ligne de l'appelant : deux appels
     * « identiques » doivent venir du MEME point d'appel (comme une erreur
     * qui se reproduit a chaque requete sur le meme throw/log), pas de deux
     * lignes differentes du test.
     */
    private function logAtSameCallSite(string $message): void
    {
        \Log::error($message);
    }

    public function testTwoIdenticalErrorsAreGroupedUnderOneRow(): void
    {
        $this->logAtSameCallSite('erreur de test');
        $this->logAtSameCallSite('erreur de test');

        $this->assertSame(1, (int)\Dvups_log::where('this.message', 'erreur de test')->count());
        $row = \Dvups_log::where('this.message', 'erreur de test')->firstOrNull();
        $this->assertSame(2, (int)$row->occurrences);
        $this->assertSame(40, strlen($row->fingerprint), 'D.4 : empreinte en sha1 (40 caracteres hex)');
    }

    /**
     * D.3 : si write() est deja en cours (reentrance - ex. une erreur
     * declenchee par la persistance du log elle-meme), un appel imbrique
     * doit etre ignore plutot que de reboucler.
     */
    public function testReentrantCallWhileAlreadyWritingIsIgnored(): void
    {
        $reflection = new \ReflectionClass(\Log::class);
        $flag = $reflection->getProperty('writing');
        $flag->setAccessible(true);
        $flag->setValue(null, true);

        try {
            \Log::error('ne doit jamais etre ecrit (reentrance)');
        } finally {
            $flag->setValue(null, false);
        }

        $row = \Dvups_log::where('this.message', 'ne doit jamais etre ecrit (reentrance)')->firstOrNull();
        $this->assertNull($row);
    }

    public function testMessagesDifferingOnlyByANumberShareTheSameFingerprint(): void
    {
        $this->logAtSameCallSite('introuvable id 42');
        $this->logAtSameCallSite('introuvable id 999');

        $this->assertSame(
            1,
            (int)\Dvups_log::where('this.message', 'like', 'introuvable id%')->count(),
            'la normalisation doit fusionner les nombres variables'
        );
    }

    public function testDifferentMessagesAreNotGrouped(): void
    {
        \Log::error('erreur A');
        \Log::error('erreur B');

        $this->assertSame(2, (int)\Dvups_log::where('this.message', 'like', 'erreur %')->count());
    }

    public function testFirstAndLastOccurrenceDatesAreTracked(): void
    {
        \Log::warning('suivi des dates');
        $row = \Dvups_log::where('this.message', 'suivi des dates')->firstOrNull();
        $this->assertNotNull($row->first_at);
        $this->assertSame($row->first_at, $row->last_at);

        \Log::warning('suivi des dates');
        $reloaded = \Dvups_log::find($row->getId());
        $this->assertSame($row->first_at, $reloaded->first_at, 'first_at ne doit jamais changer');
    }

    public function testLongMessageOverflowsToAFileAndDbKeepsThePath(): void
    {
        $long = str_repeat('x', 2000);
        \Log::error($long);

        $row = \Dvups_log::where('this.message', 'like', 'xxx%')->firstOrNull();
        $this->assertNotNull($row->log_file);
        $this->assertFileExists(ROOT . $row->log_file);
        $this->assertSame($long, file_get_contents(ROOT . $row->log_file));
        $this->assertLessThanOrEqual(1000, strlen($row->message));
    }

    public function testShortMessageStaysInlineWithoutAFile(): void
    {
        \Log::info('message court');
        $row = \Dvups_log::where('this.message', 'message court')->firstOrNull();
        $this->assertNull($row->log_file);
    }

    public function testThrowableIsLoggedWithItsClassFileAndLine(): void
    {
        try {
            throw new \RuntimeException('exception de test');
        } catch (\RuntimeException $e) {
            \Log::error($e);
        }

        $row = \Dvups_log::where('this.message', 'exception de test')->firstOrNull();
        $this->assertNotNull($row);
        $this->assertSame(\RuntimeException::class, $row->entity_class);
        $this->assertSame(__FILE__, $row->file);
    }

    public function testPurgeRemovesRowsAndFilesOlderThanRetention(): void
    {
        $long = str_repeat('y', 2000);
        \Log::error($long);
        $row = \Dvups_log::where('this.message', 'like', 'yyy%')->firstOrNull();
        $logFile = ROOT . $row->log_file;
        $this->assertFileExists($logFile);

        // simule une derniere occurrence tres ancienne
        $row->__update(['last_at' => '2000-01-01 00:00:00']);

        $removed = \Log::purge();

        $this->assertGreaterThanOrEqual(1, $removed);
        $this->assertFalse((bool)\Dvups_log::find($row->getId())->getId());
        $this->assertFileDoesNotExist($logFile);
    }
}
