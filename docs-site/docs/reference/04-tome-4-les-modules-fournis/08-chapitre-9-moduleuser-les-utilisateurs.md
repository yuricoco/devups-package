---
title: "Chapitre 9 — `ModuleUser` : les utilisateurs"
sidebar_position: 8
---

## 9.1 L'entité

`User` étend `UserCore` : identité, `username`, `email`, `phonenumber`, `password` (haché), `lang`, état d'activation, code d'activation et son expiration, jeton de réinitialisation et son expiration, jetons de session.

`User::restrictToPublicProfile()` retire l'e-mail et le téléphone : c'est ce que voit un tiers (Tome III, §8.6).

## 9.2 Les routes

`UserFrontController` (`@Api(name='/user')`) :

| Route | Accès | Rôle |
|---|---|---|
| `POST /api/user/registration` | public | inscription ; envoie un code d'activation |
| `POST /api/user/authentification` | public | connexion ; renvoie un JWT |
| `POST /api/user/googleAuth`, `/appleAuth` | public | connexion par fournisseur |
| `POST /api/user/tokenize` | public | renouvellement de jeton |
| `GET /api/user/:id` | public | profil, public ou complet selon le demandeur |
| `GET /api/user/me` | JWT | le profil de l'utilisateur connecté |
| `POST /api/user/activateaccount`, `GET /resentactivationcode` | JWT | activation par code à 6 chiffres |
| `POST /api/user/initresetpassword`, `/resetpassword` | public | réinitialisation par jeton dédié (Tome III, §8.7) |
| `POST /api/user/changepassword` | JWT | changement de mot de passe |
| `GET /checktelephone`, `POST /changetelephone` | JWT | changement de téléphone par code |
| `DELETE /delete-account`, `/cancel-delete-account` | JWT | suppression du compte, annulable |
| `GET /notifications`, `PUT /notified`, `GET /alert` | JWT | notifications |
| `POST /update-push-token` | JWT | jeton de notification poussée |

Les liens envoyés par courriel pointent vers l'application front : `Configuration::get('APP_DEEPLINK')` s'il est défini, sinon `FRONT_URL`.

## 9.3 `Configuration`

Le registre de paramètres du back-office (`ModuleConfig`) :

```php
$deeplink = Configuration::get('APP_DEEPLINK', __env_front);
Configuration::set('MAINTENANCE', 0, 'integer', 'Mode maintenance du site');
```

Un paramètre modifiable par un administrateur va dans `Configuration`. Un secret ou une valeur propre à l'environnement va dans `.env`.
