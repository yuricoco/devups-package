#!/usr/bin/env php
<?php

/*
 * Console entrypoint Doctrine (LOT 1.2, v8.0) : doctrine/orm 3 ne fournit
 * plus de binaire (ancien vendor/doctrine/orm/bin/doctrine, supprime en
 * ORM 3). Cette copie, versionnee ici en fallback (voir
 * dclass/generator/builder.php::dv_doctrine_command()), construit
 * elle-meme l'application console via ConsoleRunner::run() + le
 * EntityManagerProvider retourne par cli-config.php. Identique a
 * bin/doctrine (conserve pour les anciens appelants - doctrine.bat,
 * doctrine.php.bat).
 */

require __DIR__ . '/../vendor/autoload.php';

$entityManagerProvider = require __DIR__ . '/../cli-config.php';

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($entityManagerProvider);
