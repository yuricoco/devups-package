<?php

/**
 * Script autonome (lance dans son propre processus PHP par LogHandlerTest)
 * pour verifier Log::installGlobalHandler() sans l'installer dans le
 * processus PHPUnit lui-meme (ca resterait actif pour le reste de la
 * suite). Usage : APP_PROD=1 php trigger_handler.php <exception|fatal|warning|suppressed|notice>
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

if ($mode === 'warning') {
    // fichier/ligne attendus en base = CE fichier et CETTE ligne, pas
    // Log.php (D.1) : $overrideFile/$overrideLine viennent de PHP lui-meme.
    echo 'LINE:' . (__LINE__ + 1) . "\n";
    trigger_error('avertissement simule par trigger_handler.php', E_USER_WARNING);
}

if ($mode === 'suppressed') {
    // @ met error_reporting() a 0 pour cette instruction : ne doit jamais
    // etre journalise (D.2).
    @trigger_error('avertissement masque par trigger_handler.php', E_USER_WARNING);
}

if ($mode === 'notice') {
    // mappe sur le niveau 'info', sous LOG_MIN_LEVEL (defaut 'warning') :
    // ignore par defaut (D.2), sans rien changer a la config LOG_MIN_LEVEL.
    trigger_error('notice simule par trigger_handler.php', E_USER_NOTICE);
}
