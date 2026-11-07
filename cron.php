<?php

/**
 * Point d'entree des taches planifiees (crontab, toutes les minutes) :
 *
 *     * * * * * php /chemin/vers/le/projet/cron.php
 *
 * Traite la file des workers (table `workers`). Une application ajoute ses
 * propres passes periodiques en dessous de work().
 */

// En ligne de commande uniquement, ou par HTTP avec le jeton CRON_TOKEN
// (?token=...) pour les hebergeurs sans crontab.
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/config/load_constants.php';
    if (!cron_token || !hash_equals((string)cron_token, (string)($_GET['token'] ?? ''))) {
        http_response_code(403);
        exit('forbidden');
    }
}

require 'header.php';

$workerCtrl = new WorkersController();

$workerCtrl->work();
