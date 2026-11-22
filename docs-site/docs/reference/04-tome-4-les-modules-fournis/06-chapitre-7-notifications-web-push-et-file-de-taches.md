---
title: "Chapitre 7 — Notifications, Web Push et file de tâches"
sidebar_position: 6
---

## 7.1 Une seule entité, deux destinataires

`Notification` porte soit un `user_id` (utilisateur du site ou de l'application), soit un `admin_id` (administrateur du back-office). Son type (`Notificationtype`, identifié par `_key`) sert au filtrage et à la redirection.

```php
Notification::toUser($user, 'task.assigned', ['task' => $task->name]);
Notification::toAdmin($admin, 'task.closed', ['task' => $task->name]);
Notification::toRole($roleId, 'task.overdue', ['n' => $count]);   // tous les admins d'un rôle
```

Le type est **à la fois** la clé de `Notificationtype` et une clé de traduction : le texte est `t($type, $params)`, donc rédigé dans `lang/<iso>.php` :

```php
// lang/fr.php
'task.assigned' => 'La tâche :task vous a été assignée',
```

Un `Notificationtype` absent est créé à la volée.

:::tip Nouveau
**API unifiée en 7.0.** `toUser()`, `toAdmin()`, `toRole()` et `broadcast()` sont l'API publique. `on()` et `braodcast()` (faute historique) restent comme alias dépréciés : ils journalisent un avertissement au premier appel. `Notificationbroadcasted` a été supprimée.
:::

## 7.2 Lecture

| Accès | Rôle |
|---|---|
| `GET /api/user/notifications` (JWT) | les notifications de l'utilisateur connecté |
| `PUT /api/user/notified` (JWT) | les marquer lues |
| cloche du back-office | non lues de l'administrateur ; `?notified=<id>` marque lue |
| `Notification::unreaded($user)`, `unreadedcount($user)`, `unreadedadmin(...)`, `readed($id)` | en PHP |

## 7.3 Web Push

`Push_subscription` enregistre un abonnement navigateur (`endpoint`, `public_key`, `auth_token`, `content_type`, `user_id`). `Push_subscription::sendWebPush()` envoie une notification à cet abonnement via `minishlink/web-push`, signée avec les clés VAPID de `.env` :

```bash
php -r "require 'vendor/autoload.php'; print_r(Minishlink\WebPush\VAPID::createVapidKeys());"
```

Un navigateur qui répond `410 Gone` (abonnement expiré) fait supprimer l'abonnement automatiquement. Sans clés VAPID configurées, l'envoi ne fait rien et renvoie `false`. Les envois FCM et Expo restent disponibles (`fcmPushNotification()`, `expoPushNotification()`).

## 7.4 La file de tâches

`Workers` est une file de tâches différées (`type`, `payload`, `callback`, `timer` en minutes). `WorkersController::work()`, appelé par `cron.php`, traite les tâches en attente dont le délai est écoulé — notamment les envois de notifications poussées.

```bash
# toutes les minutes, en CLI
* * * * * php /chemin/monprojet/cron.php
# ou par HTTP
curl "https://monprojet.example.com/cron.php?token=<CRON_TOKEN>"
```
