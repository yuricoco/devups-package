<?php

namespace Devups\Tests\Support;

/**
 * Serveur PHP integre (php -S) demarre une seule fois pour la suite e2e,
 * avec le meme environnement que les tests (base de test, constantes).
 * Les erreurs PHP du serveur sont capturees dans un journal, lu apres
 * chaque requete.
 */
final class HttpServer
{
    private static $process = null;
    private static string $log = '';
    private static int $logOffset = 0;

    public static function baseUrl(): string
    {
        return rtrim(__env, '/');
    }

    public static function start(): void
    {
        if (self::$process)
            return;

        $port = (int)ltrim(__env_port, ':');
        if (!$port)
            throw new \RuntimeException('APP_PORT doit etre defini (ex. :8765) pour les tests e2e');

        self::$log = sys_get_temp_dir() . '/devups-e2e-' . getmypid() . '.log';
        @unlink(self::$log);

        $env = getenv();
        $cmd = [PHP_BINARY, '-d', 'display_errors=0', '-d', 'log_errors=1', '-S', '127.0.0.1:' . $port, '-t', realpath(__DIR__ . '/../..')];
        self::$process = proc_open($cmd, [
            0 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'],
            1 => ['file', self::$log, 'a'],
            2 => ['file', self::$log, 'a'],
        ], $pipes, realpath(__DIR__ . '/../..'), $env);

        if (!is_resource(self::$process))
            throw new \RuntimeException('impossible de demarrer php -S');

        register_shutdown_function([self::class, 'stop']);

        // attendre que le port reponde
        for ($i = 0; $i < 50; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($fp) {
                fclose($fp);
                return;
            }
            usleep(100000);
        }
        throw new \RuntimeException("le serveur de test ne repond pas sur le port $port :\n" . @file_get_contents(self::$log));
    }

    public static function stop(): void
    {
        if (self::$process) {
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$process = null;
        }
    }

    /** Lignes d'erreur PHP ecrites par le serveur depuis le dernier appel. */
    public static function newErrors(): array
    {
        clearstatcache(true, self::$log);
        $content = (string)@file_get_contents(self::$log, false, null, self::$logOffset);
        self::$logOffset += strlen($content);
        preg_match_all('/PHP (?:Fatal error|Warning|Notice|Parse error|Uncaught)[^\n]*/', $content, $m);
        return array_values(array_unique($m[0]));
    }
}
