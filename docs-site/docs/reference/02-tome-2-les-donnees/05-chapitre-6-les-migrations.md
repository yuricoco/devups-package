---
title: "Chapitre 6 — Les migrations"
sidebar_position: 5
---

:::tip Nouveau
**Nouveau en 7.0.** Les versions 4.x faisaient évoluer le schéma par `schema:update --force` sur chaque environnement, ou par des fichiers SQL lancés à la main. La 7.0 apporte un vrai système de migrations, versionnées et tracées.
:::

## 6.1 Le principe

Une migration est un fichier de `database/migration/`. `php devups migrate` joue, dans l'ordre alphabétique des noms de fichier, celles qui ne l'ont pas encore été sur la base courante, et enregistre chacune (nom, lot, date) dans la table `dvups_migration`.

Nommer les fichiers par date garantit l'ordre :

```
database/migration/
├── 2026_11_07_dvups_log_schema.sql
├── 2026_11_08_phase4_cms_rename.sql
├── 2026_11_08_phase4_drop_old_images.php
└── 2026_11_20_create_task.sql
```

## 6.2 Les deux formats

**SQL brut**, une ou plusieurs instructions :

```sql
CREATE TABLE task (
  id INT AUTO_INCREMENT NOT NULL,
  project_id INT DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  ...
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4;
```

**PHP**, quand la migration doit décider ou vérifier avant d'agir. Le fichier retourne un objet avec une méthode `up(PDO $pdo)` :

```php
<?php
return new class {
    public function up(\PDO $pdo) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM task WHERE code IS NULL")->fetchColumn();
        if ($count > 0) {
            throw new \RuntimeException("$count tâches sans code : reprise à faire avant la contrainte.");
        }
        $pdo->exec("ALTER TABLE task ADD UNIQUE INDEX uniq_task_code (code)");
    }
};
```

Une exception arrête le lot proprement : la migration est signalée `[ECHEC]` et n'est pas marquée comme jouée. C'est la forme à utiliser pour toute suppression de données qui dépend d'une reprise préalable : la migration vérifie elle-même que la reprise est complète, et refuse sinon, sans rien supprimer.

## 6.3 La commande

```bash
php devups migrate:status        # jouées et en attente
php devups migrate --dry-run     # affiche le SQL sans l'exécuter
php devups migrate               # joue les migrations en attente, en un seul lot
php devups migrate --step        # un lot par migration
php devups migrate --force       # saute la confirmation des migrations destructives
```

Une migration qui contient `DROP`, `TRUNCATE` ou `DELETE` est marquée **destructive** : elle demande une confirmation explicite (taper `oui`) ou `--force`. En production (`APP_PROD=true`), seul `--force` passe, jamais la confirmation interactive.

:::note À retenir
**Avant une migration destructive, une sauvegarde.** `mysqldump` complet, hors du dépôt, vérifié lisible. Le framework demande la confirmation ; il ne fait pas la sauvegarde à votre place.
:::

## 6.4 Installation neuve contre base existante

`php devups install` construit le schéma complet depuis les entités actuelles. Les migrations présentes à ce moment sont donc **marquées jouées sans être exécutées** (« base de départ ») : les rejouer échouerait sur des tables déjà là. Seule une base **antérieure** à une migration doit la jouer, via `migrate`.

## 6.5 Écrire une migration

1. Modifier l'entité (attribut).
2. `php devups schema:update --dump-sql` : Doctrine propose le SQL qui aligne la base sur l'entité.
3. Copier ce SQL dans un nouveau fichier daté de `database/migration/`, le relire, l'ajuster (reprise de données, ordre des `DROP` quand des clés étrangères lient les tables entre elles).
4. `php devups migrate --dry-run`, puis `php devups migrate`.
5. `schema:update --dump-sql` ne doit plus rien proposer.
6. Committer l'entité et la migration ensemble.

Le moteur est `dclass/devups/Migration/MigrationRunner.php`, couvert par `tests/Integration/MigrationRunnerTest.php` : ordre, idempotence, dry-run, refus d'une migration destructive, `--force`, `--step`, format PHP, base de départ.
