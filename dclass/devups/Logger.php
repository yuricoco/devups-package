<?php

namespace Devups;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * ETAPE 5.2 (v8.0) : LOT 5.5 - adaptateur PSR-3 (`Psr\Log\LoggerInterface`)
 * au-dessus de `\Log` (dclass/devups/Log.php) - voir TODO.md "LOT 5.5".
 *
 * `\Log` reste la seule implementation reelle (agregation par empreinte en
 * base, debordement fichier, gardes globales...) : cet adaptateur ne fait
 * QUE traduire les 8 niveaux PSR-3 vers les 3 niveaux de `\Log`
 * (error/warning/info) et deleguer. Aucun appelant existant de `\Log::`
 * n'est modifie - ajout pur, utilisable par du code qui attend
 * specifiquement un `Psr\Log\LoggerInterface` (ex. une bibliotheque
 * tierce).
 *
 * Convention PSR-3 suivie pour les exceptions : si `$context['exception']`
 * est un `\Throwable`, c'est LUI qui est transmis a `\Log::` (pas le
 * message texte) pour garder la capture fichier/ligne/trace reelle de
 * `\Log::write()` - la cle `exception` est retiree du contexte transmis
 * (un `\Throwable` brut ferait echouer le `json_encode()` de `\Log::persist()`).
 */
class Logger implements LoggerInterface
{
    /**
     * Mapping delibere, le plus simple possible : les 5 niveaux PSR-3 les
     * plus graves (emergency/alert/critical/error) vers `\Log::error()`,
     * warning vers `\Log::warning()`, et les 3 plus benins
     * (notice/info/debug) vers `\Log::info()` - `\Log` n'a jamais eu que 3
     * niveaux, et aucun appelant existant n'a besoin de plus de finesse.
     */
    private const LEVEL_MAP = [
        LogLevel::EMERGENCY => 'error',
        LogLevel::ALERT => 'error',
        LogLevel::CRITICAL => 'error',
        LogLevel::ERROR => 'error',
        LogLevel::WARNING => 'warning',
        LogLevel::NOTICE => 'info',
        LogLevel::INFO => 'info',
        LogLevel::DEBUG => 'info',
    ];

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (!isset(self::LEVEL_MAP[$level])) {
            throw new InvalidArgumentException("Unknown log level \"$level\".");
        }

        $exception = $context['exception'] ?? null;
        unset($context['exception']);

        $payload = ($exception instanceof \Throwable) ? $exception : (string)$message;

        \Log::{self::LEVEL_MAP[$level]}($payload, $context);
    }
}
