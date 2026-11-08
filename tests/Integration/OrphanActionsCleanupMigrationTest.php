<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE V.4 : database/migration/2026_11_08_phase11_cleanup_orphan_actions.sql
 * retire les lignes `dvups_action` orphelines (Local_content,
 * Local_content_key, Page_mapped, Request_history, Imagecms, Dv_image -
 * modules entierement retires du code, mais jamais nettoyes de
 * `dvups_action` par `php devups rights:sync`, qui n'ajoute que les
 * actions manquantes). Trouve en cliquant sur le lien de menu
 * correspondant sur l'application reelle ("Warning: Undefined array
 * key" dans Router.php).
 */
final class OrphanActionsCleanupMigrationTest extends DatabaseTestCase
{
    public function testRemovesOnlyTheOrphanEntitiesAndCascadesTheirPrivileges(): void
    {
        $pdo = \Model::pdo();

        $pdo->exec("INSERT INTO dvups_role (name, alias) VALUES ('role_test_cleanup_" . uniqid() . "', 'test')");
        $roleId = (int)$pdo->lastInsertId();

        $pdo->exec("INSERT INTO dvups_action (entity, action, label) VALUES ('Local_content', 'read', 'Read')");
        $orphanActionId = (int)$pdo->lastInsertId();

        $pdo->exec("INSERT INTO dvups_action (entity, action, label) VALUES ('Dvups_role', 'read', 'Read')");
        $keptActionId = (int)$pdo->lastInsertId();

        $pdo->exec("INSERT INTO dvups_privilege (dvups_role_id, dvups_action_id) VALUES ($roleId, $orphanActionId)");
        $pdo->exec("INSERT INTO dvups_privilege (dvups_role_id, dvups_action_id) VALUES ($roleId, $keptActionId)");

        $sql = file_get_contents(__DIR__ . '/../../database/migration/2026_11_08_phase11_cleanup_orphan_actions.sql');
        $pdo->exec($sql);

        $this->assertSame(0, $this->countRows('dvups_action', 'id = ?', [$orphanActionId]), 'la ligne orpheline Local_content doit etre supprimee');
        $this->assertSame(1, $this->countRows('dvups_action', 'id = ?', [$keptActionId]), 'une entite active (Dvups_role) ne doit jamais etre touchee');
        $this->assertSame(0, $this->countRows('dvups_privilege', 'dvups_action_id = ?', [$orphanActionId]), 'le privilege sur l\'action orpheline doit disparaitre (cascade)');
        $this->assertSame(1, $this->countRows('dvups_privilege', 'dvups_action_id = ?', [$keptActionId]), 'le privilege sur une action active doit rester');
    }
}
