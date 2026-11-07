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

    private const LEVEL_RANK = ['info' => 1, 'warning' => 2, 'error' => 3];

    /** Longueur au-dela de laquelle message/trace debordent vers un fichier. */
    const INLINE_MAX_LENGTH = 1000;

    /** Garde anti-recursion : une erreur declenchee par write() lui-meme ne doit pas reboucler. */
    private static bool $writing = false;

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

    /**
     * $overrideFile/$overrideLine : a fournir quand l'appelant connait deja
     * le vrai point d'origine (set_error_handler, error_get_last()) - sinon
     * debug_backtrace() pointerait sur le gestionnaire global lui-meme
     * (dans ce fichier), pas sur le fichier ou l'erreur a eu lieu.
     */
    private static function write(
        string $level,
        $messageOrThrowable,
        array $context,
        ?string $overrideFile = null,
        ?int $overrideLine = null
    ): void {
        if (self::$writing)
            return;
        self::$writing = true;

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
                $message = (string)$messageOrThrowable;

                if ($overrideFile !== null) {
                    $file = $overrideFile;
                    $line = $overrideLine;
                } else {
                    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
                    $file = $caller['file'] ?? null;
                    $line = $caller['line'] ?? null;
                }
            }

            $normalized = self::normalize($message);
            $fingerprint = self::fingerprint($class, $file, $line, $normalized);

            $fullContext = array_merge(self::ambientContext(), $context);
            self::persist($level, $fingerprint, $class, $file, $line, $message, $trace, $fullContext);
        } catch (\Throwable $ignored) {
            // La journalisation ne doit jamais faire echouer l'appelant.
        } finally {
            self::$writing = false;
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
        return sha1(($class ?? '') . '|' . ($file ?? '') . '|' . ($line ?? '') . '|' . $normalizedMessage);
    }

    private static function levelRank(string $level): int
    {
        return self::LEVEL_RANK[$level] ?? self::LEVEL_RANK[self::LEVEL_INFO];
    }

    /** LOG_MIN_LEVEL (config/constante.example.php) : defaut 'warning' - E_NOTICE/E_DEPRECATED (mappes sur 'info') sont donc ignores par defaut. */
    private static function minLevelRank(): int
    {
        $min = defined('LOG_MIN_LEVEL') ? (string)LOG_MIN_LEVEL : self::LEVEL_WARNING;
        return self::levelRank($min);
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

    /**
     * Incrementation atomique (UPDATE ... SET occurrences = occurrences + 1)
     * plutot qu'un firstOrNull() suivi d'un __update() : deux requetes
     * concurrentes sur la meme empreinte ne doivent jamais perdre une
     * occurrence (lost update). La ligne est creee seulement si l'UPDATE
     * n'a touche aucune ligne (empreinte inconnue).
     */
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

        $update = \Model::pdo()->prepare(
            'UPDATE dvups_log SET occurrences = occurrences + 1, last_at = :last_at, message = :message, context = :context, log_file = :log_file WHERE fingerprint = :fingerprint'
        );
        $update->execute([
            'last_at' => $now,
            'message' => $preview,
            'context' => json_encode($context),
            'log_file' => $logFile,
            'fingerprint' => $fingerprint,
        ]);
        if ($update->rowCount() > 0)
            return;

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
     *   ne les intercepte pas) - fichier/ligne pris sur error_get_last(),
     *   jamais sur l'emplacement de ce gestionnaire ;
     * - E_WARNING/E_NOTICE/E_USER_* : journalises SANS rien changer au
     *   comportement natif de PHP (on retourne toujours false, donc
     *   log_errors/display_errors continuent de s'appliquer comme avant).
     *   error_reporting() est respecte (une erreur masquee par @ n'est pas
     *   journalisee), et seuls les niveaux >= LOG_MIN_LEVEL (defaut
     *   'warning') sont enregistres : E_NOTICE/E_DEPRECATED (mappes sur
     *   'info') sont donc ignores par defaut.
     */
    public static function installGlobalHandler(): void
    {
        set_exception_handler(function (\Throwable $e) {
            self::error($e);
            self::respondWithFatal($e->getMessage());
        });

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (!(error_reporting() & $level))
                return false; // masquee par @ (ou par la config php.ini courante) : ne pas journaliser

            $logLevel = in_array($level, [E_WARNING, E_USER_WARNING], true) ? self::LEVEL_WARNING : self::LEVEL_INFO;
            if (self::levelRank($logLevel) < self::minLevelRank())
                return false;

            self::write($logLevel, $message, ['class' => null, 'trace' => null], $file, $line);
            return false; // laisse PHP traiter l'erreur normalement en plus
        });

        register_shutdown_function(function () {
            $fatal = error_get_last();
            if (!$fatal || !in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true))
                return;

            self::write(self::LEVEL_ERROR, $fatal['message'], ['class' => null, 'trace' => null], $fatal['file'] ?? null, $fatal['line'] ?? null);
            if (!headers_sent())
                self::respondWithFatal($fatal['message']);
        });
    }

    private static function respondWithFatal(string $message): void
    {
        $detail = (!defined('__prod') || !__prod) ? $message : t('Une erreur interne est survenue.');

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $detail . "\n");
            return;
        }

        if (headers_sent())
            return;

        http_response_code(500);

        $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($wantsJson || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'detail' => $detail]);
        } else {
            echo '<h1>500</h1><p>' . htmlspecialchars($detail) . '</p>';
        }
    }
}
