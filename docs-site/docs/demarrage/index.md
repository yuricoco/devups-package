---
title: Démarrage
sidebar_position: 0
slug: /
---

# Devups

Devups est un framework PHP pour construire des back-offices et des API : un noyau d'administration (droits, listes, formulaires générés), une couche de données qui conjugue Doctrine (métadonnées et schéma) avec son propre ORM d'écriture (`Tchutte`), et un routage par annotations/attributs.

Cette section vous mène de zéro à un premier module fonctionnel. Pour la référence exhaustive, voir [Référence](/reference). Pour un historique des ruptures de compatibilité, voir [Migration](/migration/8.0).

## Installation rapide

```bash
composer create-project spacekola/devups monprojet
cd monprojet
composer install
php devups serve
```

Le détail de chaque étape (prérequis, configuration, structure créée) est au [Tome I — Le socle et le démarrage](/reference/tome-1-le-socle-et-le-demarrage/chapitre-3-installation).

:::note Page en construction
Cette page d'accueil et le reste de la section Démarrage seront enrichis au fil du chantier de documentation — voir la Référence pour le contenu complet dès maintenant.
:::
