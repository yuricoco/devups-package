<?php
if (!defined('PROJECT_NAME') && (getenv('DEVUPS_CONSTANTS') || file_exists(__DIR__ . '/../config/constante.php')))
    require_once __DIR__ . '/../config/load_constants.php';
if (!defined('PROJECT_NAME'))
    define('PROJECT_NAME', 'Devups');

/**
 * Génère le JSON OpenAPI 3.0 pour l'API du projet.
 *
 * ETAPE N.1 : logique deplacee dans dclass/generator/OpenApiGenerator.php
 * (dv_build_openapi_spec()), partagee avec `php devups api:doc` - ce
 * fichier ne fait plus que l'appeler et echoer le resultat en JSON.
 */

require_once __DIR__ . '/../dclass/generator/OpenApiGenerator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode(dv_build_openapi_spec(__DIR__ . '/..'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
