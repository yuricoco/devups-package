<?php

/**
 * API de journalisation unique : Log::error()/warning()/info(), utilisable
 * partout (noyau, composants, front, admin). Les occurrences identiques
 * (meme empreinte : classe + fichier + ligne + message normalise) sont
 * regroupees en une seule ligne dvups_log (compteur, premiere/derniere
 * occurrence) plutot que d'empiler une ligne par appel.
 *
 * Un message ou une trace trop longs pour la colonne sont ecrits dans
 * cache/log/<AAAA-MM>/<empreinte>.log (non versionne, ecrase a chaque
 * nouvelle occurrence pour garder la plus recente) ; la ligne en base ne
 * garde que le chemin et un apercu tronque.
 *
 * N'interrompt jamais l'appelant : une erreur d'ecriture du journal
 * (base ou fichier) est elle-meme avalee, jamais remontee.
 */
class Log
{
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';

    /** Longueur au-dela de laquelle message/trace debordent vers un fichier. */
    const INLINE_MAX_LENGTH = 1000;

    public static function error($messageOrThrowable, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $messageOrThrowable, $context);
    }

    public static function warning($messageOrThrowable, array $context = []): void
    {
        self::write(self::LEVEL_WARNING, $messageOrThrowable, $context);
    }

    public static function info($messageOrThrowable, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $messageOrThrowable, $context);
    }

    private static function write(string $level, $messageOrThrowable, array $context): void
    {
        try {
            if ($messageOrThrowable instanceof \Throwable) {
                $t = $messageOrThrowable;
                $class = get_class($t);
                $file = $t->getFile();
                $line = $t->getLine();
                $message = $t->getMessage();
                $trace = $t->getTraceAsString();
            } else {
                $class = $context['class'] ?? null;
                $trace = $context['trace'] ?? null;
                unset($context['class'], $context['trace']);
                $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
                $file = $caller['file'] ?? null;
                $line = $caller['line'] ?? null;
                $message = (string)$messageOrThrowable;
            }

            $normalized = self::normalize($message);
            $fingerprint = self::fingerprint($class, $file, $line, $normalized);

            $fullContext = array_merge(self::ambientContext(), $context);
            self::persist($level, $fingerprint, $class, $file, $line, $message, $trace, $fullContext);
        } catch (\Throwable $ignored) {
            // La journalisation ne doit jamais faire echouer l'appelant.
        }
    }

    private static function normalize(string $message): string
    {
        // Fusionne les messages identiques a une valeur variable pres
        // (id, timestamp...) sous la meme empreinte.
        return preg_replace('/\d+/', '#', trim($message));
    }

    private static function fingerprint(?string $class, ?string $file, ?int $line, string $normalizedMessage): string
    {
        return hash('crc32b', ($class ?? '') . '|' . ($file ?? '') . '|' . ($line ?? '') . '|' . $normalizedMessage);
    }

    private static function ambientContext(): array
    {
        $admin = (defined('ADMIN') && !empty($_SESSION[ADMIN]) && function_exists('getadmin')) ? getadmin() : null;
        $user = (defined('USER') && !empty($_SESSION[USER])) ? @unserialize($_SESSION[USER]) : null;

        return [
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'system' => (class_exists('Request') && \Request::$system === 'admin') ? 'admin' : 'front',
            'admin_id' => ($admin && method_exists($admin, 'getId') && $admin->getId()) ? $admin->getId() : null,
            'user_id' => ($user && method_exists($user, 'getId') && $user->getId()) ? $user->getId() : null,
        ];
    }

    private static function persist(
        string $level,
        string $fingerprint,
        ?string $class,
        ?string $file,
        ?int $line,
        string $message,
        ?string $trace,
        array $context
    ): void {
        $now = date('Y-m-d H:i:s');
        $logFile = self::overflowToFileIfNeeded($fingerprint, $message, $trace);
        $preview = mb_substr($message, 0, self::INLINE_MAX_LENGTH);

        $existing = \Dvups_log::where('fingerprint', $fingerprint)->firstOrNull();
        if ($existing && $existing->getId()) {
            $existing->__update([
                'occurrences' => (int)$existing->occurrences + 1,
                'last_at' => $now,
                'message' => $preview,
                'context' => json_encode($context),
                'log_file' => $logFile,
            ]);
            return;
        }

        \Dvups_log::create([
            'fingerprint' => $fingerprint,
            'level' => $level,
            'entity_class' => $class,
            'file' => $file,
            'line' => $line,
            'message' => $preview,
            'occurrences' => 1,
            'first_at' => $now,
            'last_at' => $now,
            'context' => json_encode($context),
            'log_file' => $logFile,
        ]);
    }

    /**
     * Ecrit message+trace dans cache/log/<AAAA-MM>/<empreinte>.log quand ils
     * depassent INLINE_MAX_LENGTH, et retourne le chemin relatif (depuis
     * ROOT) a garder en base. Ecrase le fichier existant : seule la
     * derniere occurrence complete est gardee (le compteur/dates en base
     * couvrent l'historique).
     */
    private static function overflowToFileIfNeeded(string $fingerprint, string $message, ?string $trace): ?string
    {
        $full = $message . ($trace ? "\n\n" . $trace : '');
        if (mb_strlen($full) <= self::INLINE_MAX_LENGTH)
            return null;

        $relativeDir = 'cache/log/' . date('Y-m') . '/';
        $dir = ROOT . $relativeDir;
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        $relativePath = $relativeDir . $fingerprint . '.log';
        file_put_contents(ROOT . $relativePath, $full);

        return $relativePath;
    }

    /**
     * Supprime les lignes dvups_log (et leur fichier debordement) dont la
     * derniere occurrence est plus ancienne que LOG_RETENTION_DAYS (voir
     * config/constante.example.php). A appeler depuis une tache planifiee
     * (cron.php) ; jamais execute automatiquement par Log::error() lui-meme.
     */
    public static function purge(): int
    {
        $days = defined('LOG_RETENTION_DAYS') ? (int)LOG_RETENTION_DAYS : 90;
        $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));

        $old = \Dvups_log::where('last_at', '<', $cutoff)->get();
        $count = 0;
        foreach ($old as $row) {
            if ($row->log_file && file_exists(ROOT . $row->log_file))
                @unlink(ROOT . $row->log_file);
            $row->__delete();
            $count++;
        }

        return $count;
    }

    /**
     * Installe les gardes globales (une seule fois, front et admin) :
     * - exceptions non interceptees : journalisees, reponse generique en
     *   prod (JSON ou page selon le contexte), detail en dev ;
     * - erreurs fatales (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR) a
     *   l'arret du script, via register_shutdown_function (set_error_handler
     *   ne les intercepte pas) ;
     * - E_WARNING/E_NOTICE/E_USER_* : journalises SANS rien changer au
     *   comportement natif de PHP (on ne retourne pas true, donc
     *   log_errors/display_errors continuent de s'appliquer comme avant -
     *   la suite de tests e2e verifie l'absence d'erreur via le journal PHP
     *   natif, pas via dvups_log).
     */
    public static function installGlobalHandler(): void
    {
        set_exception_handler(function (\Throwable $e) {
            self::error($e);
            self::respondWithFatal($e->getMessage());
        });

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            self::write(
                in_array($level, [E_WARNING, E_USER_WARNING], true) ? self::LEVEL_WARNING : self::LEVEL_INFO,
                $message,
                ['class' => null, 'trace' => null]
            );
            return false; // laisse PHP traiter l'erreur normalement en plus
        });

        register_shutdown_function(function () {
            $fatal = error_get_last();
            if (!$fatal || !in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true))
                return;

            self::error($fatal['message'], [
                'class' => null,
                'trace' => null,
            ]);
            if (!headers_sent())
                self::respondWithFatal($fatal['message']);
        });
    }

    private static function respondWithFatal(string $message): void
    {
        if (headers_sent())
            return;

        http_response_code(500);
        $detail = (!defined('__prod') || !__prod) ? $message : t('Une erreur interne est survenue.');

        $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($wantsJson || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'detail' => $detail]);
        } else {
            echo '<h1>500</h1><p>' . htmlspecialchars($detail) . '</p>';
        }
    }
}
