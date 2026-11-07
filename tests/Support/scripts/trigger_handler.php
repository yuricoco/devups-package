<?php

/**
 * Script autonome (lance dans son propre processus PHP par LogHandlerTest)
 * pour verifier Log::installGlobalHandler() sans l'installer dans le
 * processus PHPUnit lui-meme (ca resterait actif pour le reste de la
 * suite). Usage : APP_PROD=1 php trigger_handler.php <exception|fatal>
 */

require __DIR__ . '/../../../config/dependanceInjection.php';
require __DIR__ . '/../../../lang.php';
require __DIR__ . '/../../../src/requires.php';

Log::installGlobalHandler();

$mode = $argv[1] ?? 'exception';

if ($mode === 'exception') {
    throw new \RuntimeException('boom simule par trigger_handler.php');
}

if ($mode === 'fatal') {
    // provoque une E_ERROR reelle (fonction inexistante), capturee par
    // register_shutdown_function, pas par set_exception_handler.
    undefined_function_boom_simule();
}
