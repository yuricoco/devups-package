---
title: "Chapitre 3 — Les trois classes de la couche d'accès"
sidebar_position: 2
---

## 3.1 L'architecture

```
Model           (dclass/devups/model/Model.php)
 └─ use ModelTrait
     → les points d'entrée STATIQUES : Entity::find(), ::where(), ::create()…
     → chaque point d'entrée fabrique un `new QueryBuilder($entity)`

QueryBuilder    (dclass/devups/Tchutte/QueryBuilder.php)   extends DBAL
     → construit le SQL (SELECT / INSERT / UPDATE / WHERE / JOIN / ORDER BY…)
     → API fluide : chaque méthode retourne $this

DBAL            (dclass/devups/Tchutte/DBAL.php)           extends Database
     → exécute le SQL et hydrate les entités
     → expose la connexion PDO partagée
     → lève DBALException sur toute erreur SQL
```

## 3.2 Doctrine, et ce qu'il ne fait pas

L'`EntityManager` de Doctrine, accessible par `DBAL::getEntityManager()`, sert à exactement deux choses : lire les métadonnées d'attribut (quelle table, quelles colonnes, quelles associations) et comparer ou créer le schéma (`schema:update`, `install`).

Aucune requête métier ne passe par Doctrine. Ne pas aller chercher une connexion dans `getEntityManager()` : c'est une connexion différente, volontairement.

## 3.3 La connexion partagée

```php
$pdo = Model::pdo();
```

Retourne le même objet PDO que celui utilisé par `find()`, `__insert()`, `__update()` et tout le `QueryBuilder`. À utiliser pour toute requête SQL qui ne passe pas par le `QueryBuilder`. Jamais `new PDO(...)` : cela ouvrirait une connexion séparée, invisible à toute transaction ouverte par `Model::beginTransaction()`.

## 3.4 Les erreurs SQL

:::tip Nouveau
**Nouveau en 7.0.** Toute erreur SQL lève une `dclass\devups\Tchutte\DBALException` (qui étend `RuntimeException`), avec la requête (`$e->sql`) et ses paramètres (`$e->param`). Hors production, le message inclut le SQL. Les versions 4.x affichaient l'erreur puis tuaient la requête : une violation de contrainte n'était pas rattrapable par un contrôleur. Elle l'est désormais.
:::

```php
use dclass\devups\Tchutte\DBALException;

try {
    Task::create(['name' => $name, 'project_id' => $projectId]);
} catch (DBALException $e) {
    Log::error($e);
    return ['success' => false, 'detail' => t('Impossible d\'enregistrer la tâche')];
}
```

Vérifier avant d'écrire reste la meilleure forme quand la condition est connue (§4.7) : un message métier clair vaut mieux qu'une exception SQL traduite.
