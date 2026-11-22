---
title: "Chapitre 3 — Installation"
sidebar_position: 3
---

## 3.1 La séquence officielle

```bash
composer create-project spacekola/devups monprojet
cd monprojet

cp config/constante.example.php config/constante.php
cp .env.example .env            # puis renseigner la base, les URLs, les secrets

php devups install              # base, schéma, compte administrateur maître
php devups serve                # http://127.0.0.1:<APP_PORT>/ et /admin/
```

`config/constante.php` est une simple copie du modèle : il lit toutes ses valeurs dans `.env` via `dv_env()` (chapitre 4). Il n'y a donc aucun secret à y écrire.

Pour essayer le framework depuis une copie locale du dépôt, sans passer par Packagist :

```bash
composer create-project \
  --repository '{"type":"path","url":"/chemin/vers/devups"}' \
  spacekola/devups:dev-master monprojet
```

## 3.2 Ce que fait `install`

`php devups install` enchaîne, hors production :

1. la création de l'arborescence `cache/` et `database/` si elle manque ;
2. la création de la base si elle n'existe pas ;
3. `doctrine orm:schema:create`, qui crée toutes les tables à partir des entités ;
4. l'exécution de `dclass/generator/config_data.sql`, qui sème les langues, le rôle `admin` et le compte maître, puis la table `configuration` ;
5. la mise en cache des langues ;
6. le **marquage de toutes les migrations existantes comme jouées** : le schéma vient d'être créé à partir des entités actuelles, les rejouer produirait des erreurs (chapitre 6 du Tome II).

Le compte maître est `dv_admin` / `admin`. À la première connexion, le back-office redirige vers `complete-registration` pour le personnaliser.

:::tip Nouveau
**Corrigé en 7.0.** Dans les versions 4.x, `config_data.sql` commençait par ses propres `CREATE TABLE`. Sur une base que Doctrine venait de créer, la première instruction échouait, l'exception était avalée, et aucun administrateur n'était créé — alors que le message de succès s'affichait. Le fichier ne contient plus que des `INSERT`, et la séquence complète est vérifiée de bout en bout (installation dans un dossier vide, puis connexion au back-office).
:::

En production (`APP_PROD=true`), `install` ne crée ni la base ni le schéma : sur un serveur, la base existe déjà et évolue par `php devups migrate`.

## 3.3 Les dossiers créés

```
cache/
├── views/          vues Blade compilées
└── local/{admin,front}/
database/
├── dump/
├── log/
├── migration/      les migrations (Tome II, chapitre 6)
└── transaction/
```

À l'exécution s'ajoutent sous `cache/` : `rights/` (droits compilés par rôle), `lang/` (langues actives), `trees/` (arbres compilés, Tome IV), `log/` (messages de journal trop longs pour la base) et `ratelimit/`. `php devups init:caches` recrée l'arborescence de base à la demande.

## 3.4 Le serveur de développement

```bash
php devups serve
```

Lance le serveur PHP intégré sur le port déclaré par `APP_PORT` (par défaut `:3031` dans `.env.example`), donc `http://127.0.0.1:3031/` pour le site et `http://127.0.0.1:3031/admin/` pour le back-office.

## 3.5 Vérifier que l'installation tient

| Vérification | Attendu |
|---|---|
| `php devups migrate:status` | aucune migration en attente |
| `http://<hôte>/admin/` | redirection vers le formulaire de connexion |
| connexion `dv_admin` / `admin` | redirection vers `complete-registration` |
| `GET /api/hello` | `{"success":true,"message":"hello devups you made your first apicall"}` |
| `composer test` | toutes les suites au vert (base de test dédiée, chapitre 9) |
