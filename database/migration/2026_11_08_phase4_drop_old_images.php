<?php

/**
 * ETAPE T : supprime `imagecms` et `dv_image` (ModuleGallery, retire du
 * code depuis l'ETAPE G.4 - voir 2026_11_08_phase4_cms_rename.sql dont ce
 * fichier reprend les DROP, volontairement laisses commentes dans ce
 * script SQL) - mais SEULEMENT si la reprise de donnees de ce dernier est
 * verifiee complete :
 *   - nombre de lignes de `cmsresource` >= nombre de lignes de `imagecms` ;
 *   - aucune `tree_item_image` qui avait une image dans `dv_image`
 *     (`image_id` non NULL) sans sa colonne `image` (ImageCore) remplie.
 *
 * Idempotent : si `imagecms`/`dv_image` n'existent deja plus (installation
 * neuve, ou deja nettoye manuellement), ne fait rien plutot que d'echouer.
 *
 * En cas d'echec (comptages insuffisants), leve une \RuntimeException avec
 * les comptages en clair - MigrationRunner::run() capture cette exception,
 * n'enregistre PAS la migration comme jouee et arrete le lot (voir
 * MigrationRunner::run()) : rien n'est supprime.
 */
return new class {
    public function up(\PDO $pdo): void
    {
        $tableExists = function (string $name) use ($pdo): bool {
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($name));
            return (bool)$stmt->fetchColumn();
        };

        $columnExists = function (string $table, string $column) use ($pdo): bool {
            $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
            return (bool)$stmt->fetchColumn();
        };

        $hasImagecms = $tableExists('imagecms');
        $hasDvImage = $tableExists('dv_image');

        if (!$hasImagecms && !$hasDvImage)
            return;

        if ($hasImagecms) {
            $countImagecms = (int)$pdo->query('SELECT COUNT(*) FROM `imagecms`')->fetchColumn();
            $countCmsresource = (int)$pdo->query('SELECT COUNT(*) FROM `cmsresource`')->fetchColumn();

            if ($countCmsresource < $countImagecms) {
                throw new \RuntimeException(sprintf(
                    "ETAPE T : reprise imagecms -> cmsresource incomplete (cmsresource=%d < imagecms=%d) - " .
                    "aucune table supprimee. Verifiez/rejouez d'abord la reprise de donnees " .
                    "(2026_11_08_phase4_cms_rename.sql) avant de rejouer cette migration.",
                    $countCmsresource,
                    $countImagecms
                ));
            }
        }

        $hasImageIdColumn = $hasDvImage && $tableExists('tree_item_image') && $columnExists('tree_item_image', 'image_id');

        if ($hasImageIdColumn) {
            $missing = (int)$pdo->query(
                'SELECT COUNT(*) FROM `tree_item_image` WHERE `image_id` IS NOT NULL AND `image` IS NULL'
            )->fetchColumn();

            if ($missing > 0) {
                throw new \RuntimeException(sprintf(
                    "ETAPE T : reprise tree_item_image incomplete (%d ligne(s) avec image_id renseigne mais " .
                    "sans colonne image remplie) - aucune table supprimee. Verifiez/rejouez d'abord la reprise " .
                    "de donnees (2026_11_08_phase4_cms_rename.sql) avant de rejouer cette migration.",
                    $missing
                ));
            }
        }

        // Reprise verifiee complete (ou rien a reprendre) : suppression.
        if ($hasImagecms)
            $pdo->exec('DROP TABLE `imagecms`');

        if ($hasImageIdColumn) {
            $fk = $pdo->query(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tree_item_image'
                   AND COLUMN_NAME = 'image_id' AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            )->fetchColumn();

            if ($fk)
                $pdo->exec('ALTER TABLE `tree_item_image` DROP FOREIGN KEY `' . $fk . '`');

            $pdo->exec('ALTER TABLE `tree_item_image` DROP COLUMN `image_id`');
        }

        if ($hasDvImage)
            $pdo->exec('DROP TABLE `dv_image`');
    }
};
