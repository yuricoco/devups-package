<?php

/**
 * ETAPE A.5 (v7.1) : pose trois contraintes d'unicite manquantes,
 * jamais exprimees nulle part avant ce correctif - rien n'empechait en
 * base deux lignes `tree` de meme `name`, deux `tree_item` de meme
 * (`tree_id`, `slug`), ou deux `status` de meme (`entity`, `_key`),
 * alors que le code lit deja ces triplets comme des cles fonctionnelles
 * (Tree::constant(), Tree_item::getmainmenu(), Status::getStatus()).
 *
 * Verifie D'ABORD qu'aucun doublon n'existe pour chacune des trois
 * contraintes (format PHP, comme l'exige la consigne) - si au moins une
 * existe, LEVE une \RuntimeException listant les doublons trouves et NE
 * MODIFIE RIEN (aucun index pose), pour les trois contraintes a la fois
 * (jamais une pose partielle).
 *
 * Idempotent : si un index du meme nom existe deja (migration rejouee),
 * ne le recree pas.
 *
 * Les noms d'index ci-dessous sont EXACTEMENT ceux des
 * `@UniqueConstraint` portes par Tree/Tree_item/Status (voir ces
 * entites) - c'est ce qui garantit un `php devups schema:update
 * --dump-sql` vide une fois cette migration jouee.
 */
return new class {
    public function up(\PDO $pdo): void
    {
        $duplicates = [];

        $treeNameDupes = $pdo->query(
            'SELECT `name`, COUNT(*) AS n FROM `tree` GROUP BY `name` HAVING COUNT(*) > 1'
        )->fetchAll(\PDO::FETCH_ASSOC);
        if ($treeNameDupes) {
            $duplicates['tree.name'] = $treeNameDupes;
        }

        $treeItemDupes = $pdo->query(
            'SELECT `tree_id`, `slug`, COUNT(*) AS n FROM `tree_item`
             WHERE `slug` IS NOT NULL
             GROUP BY `tree_id`, `slug` HAVING COUNT(*) > 1'
        )->fetchAll(\PDO::FETCH_ASSOC);
        if ($treeItemDupes) {
            $duplicates['tree_item.(tree_id, slug)'] = $treeItemDupes;
        }

        $statusDupes = $pdo->query(
            'SELECT `entity`, `_key`, COUNT(*) AS n FROM `status` GROUP BY `entity`, `_key` HAVING COUNT(*) > 1'
        )->fetchAll(\PDO::FETCH_ASSOC);
        if ($statusDupes) {
            $duplicates['status.(entity, _key)'] = $statusDupes;
        }

        if ($duplicates) {
            $message = "ETAPE A.5 : doublons detectes - aucune contrainte d'unicite posee.\n";
            foreach ($duplicates as $constraint => $rows) {
                $message .= "  - $constraint : " . var_export($rows, true) . "\n";
            }
            throw new \RuntimeException($message);
        }

        $indexExists = function (string $table, string $indexName) use ($pdo): bool {
            $stmt = $pdo->query(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table) . "
                   AND INDEX_NAME = " . $pdo->quote($indexName)
            );
            return (int)$stmt->fetchColumn() > 0;
        };

        if (!$indexExists('tree', 'uq_tree_name')) {
            $pdo->exec('ALTER TABLE `tree` ADD UNIQUE INDEX `uq_tree_name` (`name`)');
        }

        if (!$indexExists('tree_item', 'uq_tree_item_tree_id_slug')) {
            $pdo->exec('ALTER TABLE `tree_item` ADD UNIQUE INDEX `uq_tree_item_tree_id_slug` (`tree_id`, `slug`)');
        }

        if (!$indexExists('status', 'uq_status_entity_key')) {
            $pdo->exec('ALTER TABLE `status` ADD UNIQUE INDEX `uq_status_entity_key` (`entity`, `_key`)');
        }
    }
};
