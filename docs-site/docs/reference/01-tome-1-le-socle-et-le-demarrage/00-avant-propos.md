---
title: "Avant-propos"
sidebar_position: 0
---

## Ce que ce document est

Devups est un framework PHP écrit par Aurelien Atemkeng (Spacekola). Ces quatre tomes en sont la documentation de référence pour la **version 7.0.0**, celle qui est publiée sur Packagist sous le nom `spacekola/devups`.

La première édition avait été écrite en lisant le code d'un projet client construit sur Devups 4.3. Elle décrivait fidèlement ce projet-là, avec ses entités métier, ses contournements et ses défauts de l'époque. Cette deuxième édition repart du dépôt du framework lui-même, devenu neutre : plus aucun code applicatif n'y vit, et chaque exemple utilise un projet fictif (`monprojet`, un module `ModuleTask`, des entités `task` et `project`).

Elle est écrite pour trois lecteurs :

- **le développeur qui démarre un projet** avec `composer create-project` et veut aller de l'installation à un premier écran d'administration ;
- **le développeur qui reprend un projet Devups existant** et doit comprendre où les choses se passent avant d'y toucher ;
- **l'auteur et les contributeurs du framework**, pour qui ce document sert de miroir : il dit ce que le code fait réellement, y compris là où ce n'est pas ce qu'il avait l'air de vouloir faire.

## Comment il a été produit

Chaque affirmation provient d'une lecture du code de la version 7.0.0, et la plupart sont couvertes par la suite de tests livrée avec le framework (`tests/`, plus de 300 tests unitaires, d'intégration et de bout en bout). Quand un comportement est cité, le fichier qui le porte est nommé — mais plus le numéro de ligne, qui change à chaque version.

Les encadrés suivent une convention constante :

:::tip Nouveau
**Nouveau en 7.0.** Un mécanisme qui n'existait pas, ou qui a changé de forme, depuis la première édition.
:::

:::warning Piège
**Piège.** Un comportement réel qui surprend, et qui a déjà coûté du temps sur un projet.
:::

:::note À retenir
**À retenir.** Une règle de travail ou une convention.
:::

## Comment le lire

Le découpage suit celui de la documentation Laravel : un chapitre explique un concept, montre un exemple complet, puis donne le tableau de référence.

| Tome | Contenu |
|---|---|
| I — Le socle et le démarrage | ce qu'est Devups, l'anatomie d'un projet, l'installation, la configuration, le cycle de vie d'une requête, la ligne de commande, le générateur, un premier module, les tests |
| II — Les données | le datacore, les entités, le `QueryBuilder`, l'écriture, les transactions, les migrations, la traduction des données |
| III — L'interface | les routes, les contrôleurs, les styles d'API, les listes, les formulaires, les vues, les langues de l'interface, la sécurité, la documentation d'API |
| IV — Les modules fournis | les composants du socle, les droits, les journaux, le CMS, les arbres, les statuts, les notifications, les courriels, les dettes connues |

## Ce que ce document ne couvre pas

- Le générateur Android/Java (`dclass/generator/android/`) : présent, non documenté.
- L'intérieur de `DBAL.php` et de `Dfile.php` : leur contrat public est documenté, pas leur implémentation.
- La couche JavaScript du back-office (`dclass/devupsjs/`) : décrite par son rôle au Tome III, pas en référence d'API.
- Le déploiement en production : les commandes `build` et `deploy` existent, mais aucune procédure de mise en ligne n'est imposée par le framework.

Ce document est destiné à être itéré. Chaque projet devrait y ajouter ce qu'il mesure et corriger ce qu'il trouve faux.
