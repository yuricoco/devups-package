<?php

/**
 * Verification de role reutilisable pour les actions reservees
 * gerant/administrateur, cote API (pas seulement masquage d'ecran).
 * Introduite le 2026-08-06 en extrayant le controle deja ecrit dans
 * Import_batchFrontController (T77) plutot que de le recopier une
 * deuxieme fois pour Day_closingFrontController (0.2) - voir
 * DECISIONS_AUTO.md.
 *
 * currentRoleCode()/isManager() ajoutes T110-T112 (retours de recette) :
 * Table_sessionFrontController en a besoin pour la visibilite des tables
 * par role (T111), extraits ici plutot que dupliques une troisieme fois -
 * denyUnlessManager() est reecrite pour s'appuyer dessus, comportement
 * inchange.
 */
trait RoleGuardTrait {

    /** Code du role (ADMIN/MANAGER/SERVER/...) de l'utilisateur courant, ou null si aucun. */
    private static function currentRoleCode(): ?string {
        $employee = Employee::where('this.user_id', '=', Auth::$user_id)->firstOrNull();
        if (!$employee || !$employee->getId() || !$employee->app_role || !$employee->app_role->getId()) {
            return null;
        }
        $role = App_role::find($employee->app_role->getId());
        return $role && $role->getId() ? $role->code : null;
    }

    private static function isManager(): bool {
        return in_array(self::currentRoleCode(), ['ADMIN', 'MANAGER'], true);
    }

    private static function denyUnlessManager(){
        if (!self::isManager()) {
            return array('success' => false, 'detail' => 'reserve aux roles gerant et administrateur');
        }
        return null;
    }

}
