<?php

/**
 * Point unique de chargement des constantes de l'application.
 *
 * Par defaut : config/constante.php (propre a chaque installation, ignore
 * par git). La variable d'environnement DEVUPS_CONSTANTS permet d'en charger
 * un autre (chemin absolu ou relatif a la racine du projet) - c'est ce que
 * fait la suite de tests pour pointer vers une base dediee sans toucher a la
 * configuration locale.
 */

if (!defined('PROJECT_NAME')) {
    $__dv_constants = getenv('DEVUPS_CONSTANTS');
    if ($__dv_constants && !is_file($__dv_constants))
        $__dv_constants = __DIR__ . '/../' . ltrim($__dv_constants, '/');
    require $__dv_constants ?: __DIR__ . '/constante.php';
    unset($__dv_constants);
}
