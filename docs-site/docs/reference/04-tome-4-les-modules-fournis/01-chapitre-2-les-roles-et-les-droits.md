---
title: "Chapitre 2 — Les rôles et les droits"
sidebar_position: 1
---

:::tip Nouveau
**Nouveau en 7.0.** Le système de droits a été réécrit : un catalogue d'actions par entité, des privilèges par rôle, une fonction unique `can()`, un cache compilé.
:::

## 2.1 Le modèle

| Table | Contenu |
|---|---|
| `dvups_admin` | un compte d'administration, rattaché à un rôle |
| `dvups_role` | un rôle (`name`, `alias`) et son menu matérialisé |
| `dvups_action` | le catalogue : une ligne par couple (entité, action), avec un libellé |
| `dvups_privilege` | un couple (rôle, action) : ce rôle a le droit de faire cette action |

Toute entité a six actions implicites — `create`, `read`, `update`, `delete`, `export`, `import` — plus les actions métier déclarées dans la clé `actions` de son datacore (Tome II, §1.4). S'y ajoute une action « virtuelle », sans entité : `Documentation/read`, qui ouvre le Swagger du back-office.

## 2.2 Le catalogue : `rights:sync`

```bash
php devups rights:sync
```

`Dvups_action::sync()` parcourt `config/dvups_configurations.php` et le datacore de chaque entité, et crée les lignes `dvups_action` manquantes. Il ne supprime ni ne modifie jamais une ligne existante : une action retirée d'un datacore reste en base avec ses privilèges, à l'administrateur de la retirer explicitement. La commande rafraîchit ensuite le menu matérialisé de chaque rôle, pour qu'une entité nouvelle ou retirée apparaisse ou disparaisse de la barre latérale sans attendre la prochaine sauvegarde du rôle.

À lancer après tout `dvups_:update`.

## 2.3 L'attribution

L'écran « Rôles » du back-office présente, sur le formulaire même du rôle, une grille entité × action de cases à cocher. Enregistrer le rôle réécrit ses privilèges et vide son cache.

:::note À retenir
**Le rôle `admin` est le rôle maître.** `can()` le laisse toujours passer, quels que soient ses privilèges. Et **seul ce rôle** peut modifier les privilèges d'un rôle ou changer le rôle d'un administrateur — même si un autre rôle a reçu `update` sur `Dvups_role` ou `Dvups_admin` pour d'autres usages (renommer un rôle, modifier un profil). Sans cette règle, avoir `update` sur les rôles reviendrait à avoir tous les droits.
:::

## 2.4 `can()`

```php
if (!can('close', Task::class)) {
    http_response_code(403);
    return ['success' => false, 'detail' => 'forbidden'];
}
```

`can(string $action, string $entité): bool` :

1. refuse s'il n'y a pas d'administrateur en session ;
2. relit l'administrateur courant en base (une requête par processus, mise en cache) : un changement de rôle est pris en compte **dès la requête suivante, sans reconnexion** — `$_SESSION[ADMIN]` n'est qu'un instantané pris à la connexion ;
3. laisse passer le rôle `admin` ;
4. sinon, consulte les droits compilés du rôle.

Le nom d'entité peut être pleinement qualifié : seul le nom court est retenu.

## 2.5 Le cache compilé

`Dvups_role::compiledRights($roleId)` construit, une fois par rôle, un tableau `[entité][action] => true` à partir de `dvups_privilege`, et l'écrit dans `cache/rights/<roleId>.php`. `Dvups_role::clearRightsCache($roleId)` l'invalide : à chaque modification des privilèges du rôle et à sa suppression. Une page de liste qui appelle `can()` trois fois par ligne ne coûte donc qu'une lecture de fichier.

## 2.6 Où les droits sont vérifiés

| Endroit | Ce qui est vérifié |
|---|---|
| `Request::Controller()` | toute action CRUD d'entité, par une page ou par `admin/api/<entité>/<action>` |
| `Dvups_roleController`, `Dvups_adminController` | chacune de leurs méthodes, y compris par les routes historiques `services.php?path=...` |
| `Datatable` | l'affichage des boutons voir, modifier, supprimer |
| `/admin/apidocs` | `can('read', 'Documentation')` |
| une action métier | à la main, dans le contrôleur |
