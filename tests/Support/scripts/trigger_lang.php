<?php

/**
 * Script autonome (lance dans son propre processus par LangTest) pour
 * verifier que t() ne collecte JAMAIS une cle manquante en prod. Usage :
 * APP_PROD=1 php trigger_lang.php <cle>
 */

require __DIR__ . '/../../../config/dependanceInjection.php';
require __DIR__ . '/../../../lang.php';

t($argv[1] ?? 'cle_test');
