<?php
// ETAPE H : require_once, pas require - lang.php (charge avant ce fichier
// dans header.php/admin/header.php) declenche desormais l'autoload de
// Dvups_lang (dv_valid_lang() -> cachedActiveDlangs()) : un require nu
// ici re-declarerait la classe deja chargee ("Cannot declare class
// Dvups_lang, because the name is already in use").
require_once 'Entity/Dvups_lang.php';
require 'Form/Dvups_langForm.php';
require 'Datatable/Dvups_langTable.php';
require 'Controller/Dvups_langController.php';

require 'ModuleLang.php';
