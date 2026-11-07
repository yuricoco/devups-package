<?php

/**
 * Utilitaire de diagnostic EN LECTURE SEULE : une ligne d'une entite
 * `dvtranslate = true` sans AUCUNE ligne de traduction rattachee a une langue
 * existante disparait en silence des lectures ORM (where()->first(),
 * firstOrNull(), get()...), parce que la jointure vers `<table>_lang` est
 * chainee avec une jointure INNER vers `dvups_lang` qui elimine la ligne.
 *
 * Ces methodes AJOUTENT une lecture (SQL brut, hors ORM) ; elles ne changent
 * rien a ce que QueryBuilder/l'ORM renvoie.
 */
class DvtranslateInspector
{
    /**
     * Compte et identifie les lignes invisibles d'une entite traduite.
     *
     * @param string $entityClass nom de classe de l'entite (ex: "Tree")
     * @return array{
     *   translated: bool,
     *   table: ?string,
     *   lang_table: ?string,
     *   fk_column: ?string,
     *   total: ?int,
     *   invisible_count: ?int,
     *   invisible_ids: ?int[],
     *   orphan_lang_count: ?int,
     *   refused: ?string
     * }
     */
    public static function invisibleRows(string $entityClass): array
    {
        $check = self::checkTranslated($entityClass);
        if ($check !== true) {
            return $check;
        }

        $table = strtolower($entityClass);
        $langTable = $table . '_lang';
        $fkColumn = $table . '_id';
        $pdo = Model::pdo();

        $refusal = self::refuseIfLangSchemaMissing($pdo, $table, $langTable, $fkColumn);
        if ($refusal !== null) {
            return $refusal;
        }

        $total = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();

        $idsStmt = $pdo->query(
            "SELECT id FROM `$table` WHERE NOT EXISTS (" .
            "SELECT 1 FROM `$langTable` tl JOIN dvups_lang l ON tl.lang_id = l.id " .
            "WHERE tl.`$fkColumn` = `$table`.id" .
            ")"
        );
        $invisibleIds = array_map('intval', $idsStmt->fetchAll(PDO::FETCH_COLUMN));

        $orphan = (int)$pdo->query(
            "SELECT COUNT(*) FROM `$langTable` tl " .
            "WHERE NOT EXISTS (SELECT 1 FROM dvups_lang l WHERE l.id = tl.lang_id)"
        )->fetchColumn();

        return [
            'translated' => true,
            'table' => $table,
            'lang_table' => $langTable,
            'fk_column' => $fkColumn,
            'total' => $total,
            'invisible_count' => count($invisibleIds),
            'invisible_ids' => $invisibleIds,
            'orphan_lang_count' => $orphan,
            'refused' => null,
        ];
    }

    /**
     * Pour UN identifiant donne : cette ligne existe-t-elle en base alors
     * que l'ORM ne la voit pas ?
     *
     * @return array{
     *   exists_in_db: bool,
     *   visible_to_orm: ?bool,
     *   translated: bool,
     *   refused: ?string
     * }
     */
    public static function isInvisible(string $entityClass, int $id): array
    {
        $check = self::checkTranslated($entityClass);
        $table = class_exists($entityClass) ? strtolower($entityClass) : null;
        $pdo = Model::pdo();

        $existsInDb = false;
        if ($table !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            $existsInDb = (int)$stmt->fetchColumn() > 0;
        }

        if ($check !== true) {
            return [
                'exists_in_db' => $existsInDb,
                'visible_to_orm' => null,
                'translated' => $check['translated'],
                'refused' => $check['refused'],
            ];
        }

        if (!$existsInDb) {
            return [
                'exists_in_db' => false,
                'visible_to_orm' => false,
                'translated' => true,
                'refused' => null,
            ];
        }

        $langTable = $table . '_lang';
        $fkColumn = $table . '_id';
        $refusal = self::refuseIfLangSchemaMissing($pdo, $table, $langTable, $fkColumn);
        if ($refusal !== null) {
            return [
                'exists_in_db' => true,
                'visible_to_orm' => null,
                'translated' => true,
                'refused' => $refusal['refused'],
            ];
        }

        $visStmt = $pdo->prepare(
            "SELECT EXISTS (" .
            "SELECT 1 FROM `$langTable` tl JOIN dvups_lang l ON tl.lang_id = l.id " .
            "WHERE tl.`$fkColumn` = ?" .
            ")"
        );
        $visStmt->execute([$id]);
        $visible = (bool)$visStmt->fetchColumn();

        return [
            'exists_in_db' => true,
            'visible_to_orm' => $visible,
            'translated' => true,
            'refused' => null,
        ];
    }

    /**
     * Distingue les DEUX causes d'invisibilite,
     * pour une fiche donnee : aucune traduction du tout (demande une
     * saisie) contre traduction existante mais rattachee a un `lang_id`
     * qui n'existe plus dans `dvups_lang` (se repare, c'est un defaut de
     * donnee, pas une fiche a completer).
     *
     * @return array{
     *   case: 'visible'|'no_translation'|'orphan_lang'|'not_translated'|'not_found'|'schema_missing',
     *   message_admin: ?string,
     *   exists_in_db: bool,
     *   translated: bool
     * }
     */
    public static function diagnose(string $entityClass, int $id): array
    {
        $check = self::checkTranslated($entityClass);
        if ($check !== true) {
            return [
                'case' => 'not_translated',
                'message_admin' => null,
                'exists_in_db' => false,
                'translated' => false,
            ];
        }

        $table = strtolower($entityClass);
        $pdo = Model::pdo();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() === 0) {
            return [
                'case' => 'not_found',
                'message_admin' => null,
                'exists_in_db' => false,
                'translated' => true,
            ];
        }

        $langTable = $table . '_lang';
        $fkColumn = $table . '_id';
        if (!self::tableExists($pdo, $langTable) || !self::columnExists($pdo, $langTable, $fkColumn)) {
            return [
                'case' => 'schema_missing',
                'message_admin' => null,
                'exists_in_db' => true,
                'translated' => true,
            ];
        }

        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM `$langTable` WHERE `$fkColumn` = ?");
        $totalStmt->execute([$id]);
        $totalLangRows = (int)$totalStmt->fetchColumn();

        $validStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `$langTable` tl JOIN dvups_lang l ON tl.lang_id = l.id " .
            "WHERE tl.`$fkColumn` = ?"
        );
        $validStmt->execute([$id]);
        $validLangRows = (int)$validStmt->fetchColumn();

        if ($validLangRows > 0) {
            return [
                'case' => 'visible',
                'message_admin' => null,
                'exists_in_db' => true,
                'translated' => true,
            ];
        }

        if ($totalLangRows > 0) {
            return [
                'case' => 'orphan_lang',
                'message_admin' => "La traduction enregistree pour cette fiche est rattachee a une langue " .
                    "qui n'existe plus dans le systeme. Cette fiche n'est donc pas visible et son " .
                    "enregistrement est bloque. Veuillez contacter le developpeur pour corriger la langue associee.",
                'exists_in_db' => true,
                'translated' => true,
            ];
        }

        return [
            'case' => 'no_translation',
            'message_admin' => "Aucune traduction n'est enregistree pour cette fiche : c'est pour cette " .
                "raison qu'elle ne s'affiche pas correctement et que son enregistrement est bloque. " .
                "Veuillez saisir au moins une traduction avant d'enregistrer.",
            'exists_in_db' => true,
            'translated' => true,
        ];
    }

    /**
     * true si l'entite est reellement traduite (dvtranslate = true sur une
     * instance fraiche) ; sinon un tableau de refus pret a etre renvoye
     * tel quel par les methodes publiques. N'ouvre JAMAIS de requete sur
     * une table `_lang` ici - seule la propriete de l'instance est lue.
     *
     * @return true|array{translated:bool, table:?string, lang_table:?string,
     *   fk_column:?string, total:?int, invisible_count:?int,
     *   invisible_ids:?array, orphan_lang_count:?int, refused:string}
     */
    private static function checkTranslated(string $entityClass)
    {
        if (!class_exists($entityClass)) {
            return [
                'translated' => false,
                'table' => null,
                'lang_table' => null,
                'fk_column' => null,
                'total' => null,
                'invisible_count' => null,
                'invisible_ids' => null,
                'orphan_lang_count' => null,
                'refused' => "classe $entityClass introuvable",
            ];
        }

        $instance = new $entityClass();
        if (empty($instance->dvtranslate)) {
            return [
                'translated' => false,
                'table' => strtolower($entityClass),
                'lang_table' => null,
                'fk_column' => null,
                'total' => null,
                'invisible_count' => null,
                'invisible_ids' => null,
                'orphan_lang_count' => null,
                'refused' => "$entityClass n'est pas une entite traduite (dvtranslate != true) - aucune lecture d'une table _lang n'est effectuee",
            ];
        }

        return true;
    }

    /**
     * @return null|array{translated:true, table:string, lang_table:string,
     *   fk_column:string, total:null, invisible_count:null,
     *   invisible_ids:null, orphan_lang_count:null, refused:string}
     */
    private static function refuseIfLangSchemaMissing(PDO $pdo, string $table, string $langTable, string $fkColumn): ?array
    {
        if (self::tableExists($pdo, $langTable) && self::columnExists($pdo, $langTable, $fkColumn)) {
            return null;
        }

        return [
            'translated' => true,
            'table' => $table,
            'lang_table' => $langTable,
            'fk_column' => $fkColumn,
            'total' => null,
            'invisible_count' => null,
            'invisible_ids' => null,
            'orphan_lang_count' => null,
            'refused' => "schema de traduction attendu introuvable ($langTable.$fkColumn) - mesure impossible",
        ];
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables " .
            "WHERE table_schema = DATABASE() AND table_name = ?"
        );
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns " .
            "WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
