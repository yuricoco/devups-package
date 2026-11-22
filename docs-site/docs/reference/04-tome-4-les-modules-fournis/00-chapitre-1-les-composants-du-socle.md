---
title: "Chapitre 1 — Les composants du socle"
sidebar_position: 0
---

## 1.1 La règle

Avant de coder un mécanisme, vérifier qu'un module du socle ne le fait pas déjà. Les droits, le journal, les langues, les contenus éditables, les listes de référence, les statuts, les notifications et les courriels ont chacun leur module : les réinventer dans un projet, c'est perdre l'écran d'administration, la traduction, le cache et les tests qui vont avec.

## 1.2 `devups` — le noyau d'administration

| Module | Entités | Rôle |
|---|---|---|
| `ModuleAdmin` | `Dvups_admin`, `Dvups_role`, `Dvups_action`, `Dvups_privilege` | comptes d'administration, rôles et droits (chapitre 2) |
| `ModuleConfig` | `Configuration`, `Dvups_entity` | le registre de paramètres, le catalogue des entités |
| `ModuleLang` | `Dvups_lang` | les langues (Tome III, chapitre 7) |
| `ModuleLog` | `Dvups_log` | le journal applicatif (chapitre 3) |

## 1.3 `devupscms` — le contenu et la communication

| Module | Entités | Rôle |
|---|---|---|
| `ModuleCms` | `Cmscontent`, `Cmsresource`, `Cmscomment` | contenus éditables et traduits, leurs images, leurs commentaires (chapitre 4) |
| `ModuleTree` | `Tree`, `Tree_item`, `Tree_item_image` | les données de référence en arbre (chapitre 5) |
| `ModuleData` | `Status`, `Interval`, `Interval_group` | statuts, intervalles et barèmes (chapitre 6) |
| `ModuleLocation` | `Country` | les pays, traduits (chapitre 6) |
| `ModuleNotification` | `Notification`, `Notificationtype` | notifications aux utilisateurs et aux administrateurs (chapitre 7) |
| `ModulePushNotification` | `Push_subscription`, `Workers` | Web Push, FCM, file de tâches (chapitre 7) |
| `ModuleReporting` | `Reportingmodel`, `Emaillog`, `Push_email` | modèles de courriel et journal des envois (chapitre 8) |

## 1.4 `devupsfront` — le côté public

| Module | Entités | Rôle |
|---|---|---|
| `ModuleUser` | `User`, `Oauth_client` | inscription, connexion, profils, Google et Apple (chapitre 9) |

:::tip Nouveau
**Retirés en 7.0.** `ModuleGallery` (`Dv_image`) — remplacé par `ImageCore` porté directement par `Cmsresource` et `Tree_item_image` ; `ModuleDatabase` (`request_history`), jamais alimenté ; les entités de traduction en base `Local_content*` et `Page_mapped` ; dans `ModuleLocation`, `Continent`, `Region`, `Department`, `District` et `Town` ; `Notificationbroadcasted` ; `Dvups_right`, remplacé par `Dvups_action` et `Dvups_privilege`. Les migrations correspondantes sont livrées dans `database/migration/`.
:::
