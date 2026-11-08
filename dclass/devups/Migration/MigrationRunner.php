<?php

namespace dclass\devups\Migration;

/**
 * ETAPE Q : rejoue dans l'ordre (tri alphabetique des noms de fichiers,
 * d'ou le prefixe YYYY_MM_DD_* des migrations existantes) les fichiers de
 * database/migration/ non encore enregistres dans `dvups_migration`
 * (nom, lot, date). Le format reste le SQL brut (execute tel quel) ; un
 * fichier .php est accepte en plus s'il `return` un objet dote d'une
 * methode up(PDO $pdo) - utile a la marge, pour une migration qui ne peut
 * pas s'exprimer en SQL pur (ex. lire/transformer des donnees existantes).
 *
 * N'utilise PAS Model::create()/Dvups_migration::create() (qui injecterait
 * de force une colonne created_at que cette table n'a pas) : insertions et
 * lectures passent par Model::pdo() en SQL prepare, directement sur
 * `dvups_migration` - la classe \Dvups_migration (dclass/extends) n'existe
 * que pour que Doctrine SchemaTool (tests/bootstrap.php) cree la table sur
 * la base de test.
 */
class MigrationRunner
{
    /** @var string motif (hors commentaires) qui marque une migration comme destructive. */
    private const DESTRUCTIVE_PATTERN = '/\b(DROP|TRUNCATE|DELETE)\b/i';

    public static function migrationsDir(): string
    {
        return rtrim(ROOT, '/\\') . '/database/migration/';
    }

    /**
     * Cree `dvups_migration` si elle n'existe pas encore - bootstrap
     * necessaire sur une base existante/anterieure a cette fonctionnalite
     * (une installation neuve l'a deja via orm:schema:create, Dvups_migration
     * etant desormais une entite Doctrine comme une autre).
     */
    public static function ensureMigrationsTableExists(): void
    {
        \Model::pdo()->exec(
            "CREATE TABLE IF NOT EXISTS `dvups_migration` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `executed_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `dvups_migration_migration_unique` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    /** Fichiers .sql/.php de $dir, tries par nom (ordre d'execution). */
    public static function allMigrationFiles(string $dir): array
    {
        if (!is_dir($dir))
            return [];

        $files = array_values(array_filter(scandir($dir), function ($file) use ($dir) {
            return preg_match('/\.(sql|php)$/i', $file) && is_file($dir . $file);
        }));
        sort($files);
        return $files;
    }

    /** @return array<string,array{batch:int,executed_at:string}> migration => ligne */
    public static function playedMigrations(): array
    {
        self::ensureMigrationsTableExists();

        $played = [];
        $stmt = \Model::pdo()->query('SELECT `migration`, `batch`, `executed_at` FROM `dvups_migration`');
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
            $played[$row['migration']] = ['batch' => (int)$row['batch'], 'executed_at' => $row['executed_at']];

        return $played;
    }

    /** Migrations presentes dans $dir mais absentes de dvups_migration, dans l'ordre. */
    public static function pending(?string $dir = null): array
    {
        $dir = self::normalizeDir($dir);
        $played = self::playedMigrations();

        return array_values(array_filter(self::allMigrationFiles($dir), function ($file) use ($played) {
            return !array_key_exists($file, $played);
        }));
    }

    /** @return array<int,array{migration:string,played:bool,batch:?int,executed_at:?string}> */
    public static function status(?string $dir = null): array
    {
        $dir = self::normalizeDir($dir);
        $played = self::playedMigrations();

        $rows = [];
        foreach (self::allMigrationFiles($dir) as $file) {
            $rows[] = [
                'migration' => $file,
                'played' => array_key_exists($file, $played),
                'batch' => $played[$file]['batch'] ?? null,
                'executed_at' => $played[$file]['executed_at'] ?? null,
            ];
        }
        return $rows;
    }

    /** Retire les commentaires SQL (-- ligne, /* bloc) avant de chercher DROP/TRUNCATE/DELETE. */
    public static function isDestructive(string $sql): bool
    {
        $stripped = preg_replace('/--.*$/m', '', $sql);
        $stripped = preg_replace('#/\*.*?\*/#s', '', (string)$stripped);
        return (bool)preg_match(self::DESTRUCTIVE_PATTERN, (string)$stripped);
    }

    private static function nextBatch(): int
    {
        self::ensureMigrationsTableExists();
        $max = \Model::pdo()->query('SELECT MAX(`batch`) AS m FROM `dvups_migration`')->fetch(\PDO::FETCH_ASSOC);
        return (int)($max['m'] ?? 0) + 1;
    }

    private static function recordPlayed(string $migration, int $batch): void
    {
        $stmt = \Model::pdo()->prepare(
            'INSERT INTO `dvups_migration` (`migration`, `batch`, `executed_at`) VALUES (:migration, :batch, :executed_at)'
        );
        $stmt->execute([
            'migration' => $migration,
            'batch' => $batch,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function executeFile(string $path, string $sql = null): void
    {
        if (preg_match('/\.php$/i', $path)) {
            $migration = require $path;
            if (is_object($migration) && method_exists($migration, 'up'))
                $migration->up(\Model::pdo());
            return;
        }

        $sql = (string)$sql;
        if (trim($sql) !== '')
            \Model::pdo()->exec($sql);
    }

    /**
     * Marque, sans les executer, toutes les migrations presentes dans $dir
     * comme jouees au lot 0 - a utiliser uniquement juste apres une
     * installation neuve (orm:schema:create construit deja le schema
     * ENTIER depuis les entites actuelles : rejouer ces fichiers
     * creerait des doublons/erreurs SQL).
     */
    public static function markAllAsBaseline(?string $dir = null): int
    {
        $dir = self::normalizeDir($dir);
        self::ensureMigrationsTableExists();

        $played = self::playedMigrations();
        $marked = 0;
        foreach (self::allMigrationFiles($dir) as $file) {
            if (array_key_exists($file, $played))
                continue;
            self::recordPlayed($file, 0);
            $marked++;
        }
        return $marked;
    }

    /**
     * @param array{dir?:string,dry_run?:bool,force?:bool,step?:bool,confirm?:callable} $options
     *        confirm($migration): bool - appele pour une migration destructive hors --force/prod ;
     *        sans callable, une migration destructive non --force est toujours refusee (comportement non-interactif).
     * @return array{ran:string[],dry_run:array,refused_destructive:string[],failed:array}
     */
    public static function run(array $options = []): array
    {
        $dir = self::normalizeDir($options['dir'] ?? null);
        $dryRun = !empty($options['dry_run']);
        $force = !empty($options['force']);
        $step = !empty($options['step']);
        $confirm = $options['confirm'] ?? null;
        // surchargeable (tests) : __prod est une constante figee pour la vie
        // du processus (dv_env() au bootstrap), impossible a faire varier
        // autrement d'un test a l'autre dans le meme run PHPUnit.
        $prod = array_key_exists('prod', $options) ? (bool)$options['prod'] : (defined('__prod') && \__prod);

        $result = ['ran' => [], 'dry_run' => [], 'refused_destructive' => [], 'failed' => []];

        $pending = self::pending($dir);
        if (!$pending)
            return $result;

        $batch = $step ? null : self::nextBatch();

        foreach ($pending as $file) {
            $path = $dir . $file;
            $isPhp = (bool)preg_match('/\.php$/i', $file);
            $sql = $isPhp ? null : file_get_contents($path);
            $destructive = $sql !== null && self::isDestructive($sql);

            if ($dryRun) {
                $result['dry_run'][] = ['migration' => $file, 'sql' => $sql, 'destructive' => $destructive];
                continue;
            }

            if ($destructive && !$force) {
                if ($prod) {
                    $result['refused_destructive'][] = $file;
                    break;
                }
                $confirmed = is_callable($confirm) ? (bool)$confirm($file) : false;
                if (!$confirmed) {
                    $result['refused_destructive'][] = $file;
                    break;
                }
            }

            try {
                self::executeFile($path, $sql);
            } catch (\Throwable $e) {
                // une migration qui se juge elle-meme non sure a l'execution
                // (ex. phase4_drop_old_images.php, ETAPE T : comptages de
                // reprise incomplets) doit arreter le lot ici, sans etre
                // enregistree jouee ni laisser une migration suivante
                // s'executer sur un etat qu'elle ne prevoyait pas.
                $result['failed'][] = ['migration' => $file, 'error' => $e->getMessage()];
                break;
            }

            $usedBatch = $step ? self::nextBatch() : $batch;
            self::recordPlayed($file, $usedBatch);
            $result['ran'][] = $file;
        }

        return $result;
    }

    private static function normalizeDir(?string $dir): string
    {
        return rtrim($dir ?? self::migrationsDir(), '/\\') . '/';
    }
}
