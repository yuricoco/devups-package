<?php

return array (
  'ModuleAdmin' => 
  array (
    'name' => 'ModuleAdmin',
    'label' => 'Administration',
    'icon' => 'fas fa-user-shield',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devups/ModuleAdmin/',
        'module' => 'ModuleAdmin',
        'name' => 'Dvups_action',
        'label' => 'Dvups_action',
        'namespace' => 'devups\\ModuleAdmin\\Entity\\',
        'component' => 'devups',
      ),
      1 => 
      array (
        'path' => 'devups/ModuleAdmin/',
        'module' => 'ModuleAdmin',
        'name' => 'Dvups_admin',
        'label' => 'Dvups_admin',
        'namespace' => 'devups\\ModuleAdmin\\Entity\\',
        'component' => 'devups',
      ),
      2 => 
      array (
        'path' => 'devups/ModuleAdmin/',
        'module' => 'ModuleAdmin',
        'name' => 'Dvups_privilege',
        'label' => 'Dvups_privilege',
        'namespace' => 'devups\\ModuleAdmin\\Entity\\',
        'component' => 'devups',
      ),
      3 => 
      array (
        'path' => 'devups/ModuleAdmin/',
        'module' => 'ModuleAdmin',
        'name' => 'Dvups_role',
        'label' => 'Dvups_role',
        'namespace' => 'devups\\ModuleAdmin\\Entity\\',
        'component' => 'devups',
      ),
    ),
  ),
  'ModuleConfig' => 
  array (
    'name' => 'ModuleConfig',
    'label' => 'Configuration',
    'icon' => 'fas fa-cogs',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devups/ModuleConfig/',
        'module' => 'ModuleConfig',
        'name' => 'Configuration',
        'label' => 'Configuration',
        'namespace' => 'devups\\ModuleConfig\\Entity\\',
        'component' => 'devups',
      ),
    ),
  ),
  'ModuleLang' =>
  array (
    'name' => 'ModuleLang',
    'label' => 'Langues',
    'icon' => 'fas fa-language',
    'entities' => 
    array (
      0 =>
      array (
        'path' => 'devups/ModuleLang/',
        'module' => 'ModuleLang',
        'name' => 'Dvups_lang',
        'label' => 'Dvups_lang',
        'namespace' => 'devups\\ModuleLang\\Entity\\',
        'component' => 'devups',
      ),
    ),
  ),
  'ModuleLog' =>
  array (
    'name' => 'ModuleLog',
    'label' => 'Journal',
    'icon' => 'fas fa-file-alt',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devups/ModuleLog/',
        'module' => 'ModuleLog',
        'name' => 'Dvups_log',
        'label' => 'Dvups_log',
        'namespace' => 'devups\\ModuleLog\\Entity\\',
        'component' => 'devups',
      ),
    ),
  ),
  'ModuleCms' => 
  array (
    'name' => 'ModuleCms',
    'label' => 'Contenu',
    'icon' => 'fas fa-file-alt',
    'entities' => 
    array (
      0 =>
      array (
        'path' => 'devupscms/ModuleCms/',
        'module' => 'ModuleCms',
        'name' => 'Cmscontent',
        'label' => 'Cmscontent',
        'namespace' => 'devupscms\\ModuleCms\\Entity\\',
        'component' => 'devupscms',
      ),
      1 =>
      array (
        'path' => 'devupscms/ModuleCms/',
        'module' => 'ModuleCms',
        'name' => 'Cmscomment',
        'label' => 'Cmscomment',
        'namespace' => 'devupscms\\ModuleCms\\Entity\\',
        'component' => 'devupscms',
      ),
      2 =>
      array (
        'path' => 'devupscms/ModuleCms/',
        'module' => 'ModuleCms',
        'name' => 'Cmsresource',
        'label' => 'Cmsresource',
        'namespace' => 'devupscms\\ModuleCms\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleData' => 
  array (
    'name' => 'ModuleData',
    'label' => 'Donnees',
    'icon' => 'fas fa-table',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupscms/ModuleData/',
        'module' => 'ModuleData',
        'name' => 'Interval',
        'label' => 'Interval',
        'namespace' => 'devupscms\\ModuleData\\Entity\\',
        'component' => 'devupscms',
      ),
      1 => 
      array (
        'path' => 'devupscms/ModuleData/',
        'module' => 'ModuleData',
        'name' => 'Status',
        'label' => 'Status',
        'namespace' => 'devupscms\\ModuleData\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleLocation' =>
  array (
    'name' => 'ModuleLocation',
    'label' => 'Localisation',
    'icon' => 'fas fa-map-marker-alt',
    'entities' =>
    array (
      0 =>
      array (
        'path' => 'devupscms/ModuleLocation/',
        'module' => 'ModuleLocation',
        'name' => 'Country',
        'label' => 'Country',
        'namespace' => 'devupscms\\ModuleLocation\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleNotification' => 
  array (
    'name' => 'ModuleNotification',
    'label' => 'Notifications',
    'icon' => 'fas fa-bell',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupscms/ModuleNotification/',
        'module' => 'ModuleNotification',
        'name' => 'Notification',
        'label' => 'Notification',
        'namespace' => 'devupscms\\ModuleNotification\\Entity\\',
        'component' => 'devupscms',
      ),
      2 =>
      array (
        'path' => 'devupscms/ModuleNotification/',
        'module' => 'ModuleNotification',
        'name' => 'Notificationtype',
        'label' => 'Notificationtype',
        'namespace' => 'devupscms\\ModuleNotification\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModulePushNotification' => 
  array (
    'name' => 'ModulePushNotification',
    'label' => 'Notifications push',
    'icon' => 'fas fa-paper-plane',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupscms/ModulePushNotification/',
        'module' => 'ModulePushNotification',
        'name' => 'Push_subscription',
        'label' => 'Push_subscription',
        'namespace' => 'devupscms\\ModulePushNotification\\Entity\\',
        'component' => 'devupscms',
      ),
      1 => 
      array (
        'path' => 'devupscms/ModulePushNotification/',
        'module' => 'ModulePushNotification',
        'name' => 'Workers',
        'label' => 'Workers',
        'namespace' => 'devupscms\\ModulePushNotification\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleReporting' => 
  array (
    'name' => 'ModuleReporting',
    'label' => 'Rapports',
    'icon' => 'fas fa-chart-bar',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupscms/ModuleReporting/',
        'module' => 'ModuleReporting',
        'name' => 'Push_email',
        'label' => 'Push_email',
        'namespace' => 'devupscms\\ModuleReporting\\Entity\\',
        'component' => 'devupscms',
      ),
      1 => 
      array (
        'path' => 'devupscms/ModuleReporting/',
        'module' => 'ModuleReporting',
        'name' => 'Emaillog',
        'label' => 'Emaillog',
        'namespace' => 'devupscms\\ModuleReporting\\Entity\\',
        'component' => 'devupscms',
      ),
      2 => 
      array (
        'path' => 'devupscms/ModuleReporting/',
        'module' => 'ModuleReporting',
        'name' => 'Reportingmodel',
        'label' => 'Reportingmodel',
        'namespace' => 'devupscms\\ModuleReporting\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleTree' => 
  array (
    'name' => 'ModuleTree',
    'label' => 'Arborescence',
    'icon' => 'fas fa-sitemap',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupscms/ModuleTree/',
        'module' => 'ModuleTree',
        'name' => 'Tree',
        'label' => 'Tree',
        'namespace' => 'devupscms\\ModuleTree\\Entity\\',
        'component' => 'devupscms',
      ),
      1 => 
      array (
        'path' => 'devupscms/ModuleTree/',
        'module' => 'ModuleTree',
        'name' => 'Tree_item',
        'label' => 'Tree_item',
        'namespace' => 'devupscms\\ModuleTree\\Entity\\',
        'component' => 'devupscms',
      ),
      2 => 
      array (
        'path' => 'devupscms/ModuleTree/',
        'module' => 'ModuleTree',
        'name' => 'Tree_item_image',
        'label' => 'Tree_item_image',
        'namespace' => 'devupscms\\ModuleTree\\Entity\\',
        'component' => 'devupscms',
      ),
    ),
  ),
  'ModuleUser' => 
  array (
    'name' => 'ModuleUser',
    'label' => 'Utilisateurs',
    'icon' => 'fas fa-users',
    'entities' => 
    array (
      0 => 
      array (
        'path' => 'devupsfront/ModuleUser/',
        'module' => 'ModuleUser',
        'name' => 'User',
        'label' => 'User',
        'namespace' => 'devupsfront\\ModuleUser\\Entity\\',
        'component' => 'devupsfront',
      ),
      1 => 
      array (
        'path' => 'devupsfront/ModuleUser/',
        'module' => 'ModuleUser',
        'name' => 'Oauth_client',
        'label' => 'Oauth_client',
        'namespace' => 'devupsfront\\ModuleUser\\Entity\\',
        'component' => 'devupsfront',
      ),
    ),
  ),
);
