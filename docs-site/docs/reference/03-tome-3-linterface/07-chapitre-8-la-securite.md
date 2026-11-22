---
title: "Chapitre 8 — La sécurité"
sidebar_position: 7
---

## 8.1 Les deux systèmes d'identité

Devups a deux mécanismes d'authentification indépendants.

| | API publique | Back-office |
|---|---|---|
| Support | jeton JWT | session PHP |
| Porté par | en-tête `Authorization: Bearer <jwt>` ou `Auth: Bearer <jwt>` | `$_SESSION[ADMIN]` |
| Identité | `Auth::$user_id` (un `User`) | `getadmin()` (un `Dvups_admin`) |
| Autorisation | `Auth::$restrictions`, `@Auth`, propriété | `can()` |

Dans une requête d'administration, `Auth::$user_id` est toujours vide. C'est pourquoi `Router::$admin_route` existe (§8.5).

## 8.2 Le JWT

| Élément | Valeur |
|---|---|
| Algorithme | HS512 |
| Secret | `JWT_SECRET` |
| Charge utile | `iss`, `userId`, plus `iat` / `nbf` / `exp` si `JWT_EXPIRE` est défini |

```php
$jwt   = Auth::getJWT($user);            // à la connexion
$token = Auth::decodeJWTOrFail($jwt);    // lève sur tout jeton absent, invalide ou expiré
```

:::warning Piège
**`JWT_EXPIRE=false` désactive toute expiration** : seule la signature est vérifiée, un jeton émis est valable indéfiniment. Fixer une durée (`+72 hours`) en production. Pour tout code neuf qui décode un jeton hors du flux par annotation, utiliser `decodeJWTOrFail()` plutôt que `decodeJWT()`, dont l'échec silencieux peut être oublié.
:::

## 8.3 `#[Auth]`, et où il est vérifié

| Dispatcher | Traitement |
|---|---|
| `Router::mapFunction()` (pages, style libre) | si la méthode porte `#[Auth]`, exige `Auth::$user_id` |
| `Request::FrontController()` (REST dédié) | si la méthode porte `#[Auth]`, vérifie le jeton |
| `FrontController::authorization()` (générique) | consulte `Auth::$restrictions` |

L'absence de `#[Auth]` sur une méthode `xxxServe()` signifie **aucun contrôle** : la route est anonyme.

## 8.4 `Auth::$restrictions` — protégé par défaut

C'est le mécanisme central, et il fonctionne par **liste d'exceptions** : toute entité de `$public_access` est protégée sur toutes ses méthodes, sauf celles nommées dans `$publicMethods`.

```php
// route/WebService.php
public $public_access = [
    'task' => \Task::class,
    'user' => \User::class,
];

public static $publicMethods = [
    // Lectures anonymes. Protégées : create, update, delete, close.
    'task' => ['index', 'detail'],
    'user' => ['registration', 'authentification', 'tokenize',
               'initresetpassword', 'resetpassword', 'googleAuth', 'appleAuth', 'detail'],
];
```

Le nom comparé est celui de la méthode **réellement résolue** : `index` pour `GET /api/task`, `detail` pour `GET /api/task/42`, le nom de l'action pour une route annotée. Une méthode absente de la liste répond 401 sans jeton.

:::note À retenir
**Comment construire `$publicMethods`.** Y mettre ce qu'un visiteur non connecté doit pouvoir atteindre, et rien de ce qui touche au compte, à l'écriture ou à l'argent. Nommer en commentaire les actions laissées protégées, pour qu'on ne les prenne pas pour des oublis : la liste porte sa propre justification.
:::

## 8.5 Le contrôle de propriété

`Auth::$restrictions` exige un jeton, pas que la ligne appartienne au porteur du jeton. Un second mécanisme s'en charge, dans `FrontController`, configurable par projet :

```php
// au démarrage de l'application (route/WebService.php, par exemple)
FrontController::setOwnerColumns([
    'task'    => 'user_id',
    'comment' => 'author_id',
]);
```

Pour une entité de cette liste, `updateCore()`, `deleteCore()` et `uploadCore()` vérifient, par une requête dédiée, que la ligne a bien `<colonne> = Auth::$user_id` ; sinon 403.

Trois décisions de conception :

1. **L'absence d'une entité est un choix.** Elle reste protégée par le jeton, sans contrôle de propriété supplémentaire.
2. **Le contrôle passe par une requête dédiée** (`where(...)->count()`), pas par la propriété de l'entité hydratée, qui n'expose pas toujours la colonne de clé étrangère.
3. **`Router::$admin_route` court-circuite le contrôle.** Ces méthodes sont partagées avec l'API du back-office, où `Auth::$user_id` est vide et où un administrateur ne doit évidemment pas être restreint à « ses » lignes.

## 8.6 Le profil public

`FrontController::restrictForRequester($entity, $id)` appelle `restrictToPublicProfile()` sur l'entité quand le demandeur n'en est pas le propriétaire (et hors back-office). `User` l'implémente : `GET /api/user/42` renvoie le profil public à un tiers, sans e-mail ni téléphone, et le profil complet au titulaire du jeton. `detailCore()` et le listing générique l'appliquent.

## 8.7 Mots de passe, sessions, codes

:::tip Nouveau
**Nouveau en 7.0.** Tout ce paragraphe.
:::

**Mots de passe.** `Dvups_admin` et `User` stockent `password_hash()` (`dv_hash_password()`), vérifié par `password_verify()` (`dv_verify_password()`). Un ancien hash `sha1()` encore en base est accepté une dernière fois, puis remplacé automatiquement au premier login réussi : aucune réinitialisation forcée.

**« Se souvenir de moi » (back-office).** Le cookie ne contient jamais le mot de passe ni son hash : il contient un jeton aléatoire dont seul le hash est stocké en base, avec une date d'expiration. Le jeton est renouvelé quand il sert à restaurer une session, et invalidé à la déconnexion comme au changement de mot de passe. Cookies `HttpOnly`, `SameSite=Lax`, et `Secure` en HTTPS.

**Réinitialisation du mot de passe (API).** `POST /api/user/initresetpassword` émet un jeton dédié (`random_bytes(32)`, encodé en base64url), dont seul le `sha256` est stocké, valable `RESET_PASSWORD_TOKEN_TTL_MINUTES` et à usage unique. Le lien envoyé par courriel ne contient ni l'identifiant de l'utilisateur ni le code court. La réponse est identique que l'adresse existe ou non : pas d'énumération de comptes. `POST /api/user/resetpassword` consomme le jeton.

**Codes à 6 chiffres** (activation de compte, changement de téléphone). Ils expirent après `ACTIVATION_CODE_TTL_MINUTES` et sont invalidés après `RATE_LIMIT_CODE_MAX_ATTEMPTS` essais erronés.

**Limitation de fréquence.** `RateLimiter` (`dclass/devups/RateLimiter.php`) compte les tentatives par clé dans une fenêtre glissante (stockage fichier, `cache/ratelimit/`) :

```php
$limiter = new RateLimiter('login_admin:' . strtolower($login),
                           RATE_LIMIT_LOGIN_WINDOW_SECONDS, RATE_LIMIT_LOGIN_MAX_ATTEMPTS);
if ($limiter->tooManyAttempts()) {
    http_response_code(429);
    return ['success' => false, 'detail' => t('Trop de tentatives, réessayez plus tard.'),
            'retry_after' => $limiter->retryAfter()];
}
// … en cas d'échec :  $limiter->hit();   en cas de succès :  $limiter->clear();
```

Il protège la connexion au back-office et à l'API (par compte et par IP), la vérification et le renvoi des codes.

**L'adresse IP du client.** `Util::get_IP_address()` ne fait confiance à `X-Forwarded-For` (et aux en-têtes assimilés) que si la connexion vient d'une adresse listée dans `TRUSTED_PROXIES`. Sans cette liste, l'en-tête est ignoré : un client ne peut pas se forger une IP pour contourner la limitation. Derrière un reverse proxy ou un équilibreur de charge, renseigner son adresse, sinon tous les clients partageront l'IP du proxy.

## 8.8 Le reste de la surface

**Les droits du back-office.** `can($action, $entité)`, vérifié par `Request::Controller()` pour toute action CRUD, par les boutons des listes et par les contrôleurs d'administration eux-mêmes (Tome IV, chapitre 2).

**Les points d'entrée de module.** `services.php` exige une session d'administration, ainsi que l'`index.php` des composants `devups` et `devupscms` (Tome I, §2.4).

**La racine du site.** Le `.htaccess` racine refuse l'accès direct aux fichiers cachés (dont `.env`), à `config/`, `database/`, `cache/`, `vendor/`, `tests/`, et au code PHP de `src/` et `dclass/` hors des points d'entrée prévus.

**Les tâches planifiées.** `cron.php` ne s'exécute qu'en ligne de commande, ou par HTTP avec `?token=<CRON_TOKEN>`, comparé en temps constant.

**La langue.** Une valeur de `?lang=` n'entre jamais dans un chemin de fichier sans passer par `dv_valid_lang()` (§7.1), puis par un contrôle `realpath()` qui vérifie que le fichier est bien dans `LANG_DIR`. Une valeur invalide retombe sur la langue par défaut, sans erreur.

**Le chemin générique et les entités sensibles.** `WebService` propose deux listes, vides par défaut :

| Liste | Effet |
|---|---|
| `$genericPathMoneyEntities` | le chemin générique ne sert pas du tout ces entités, lectures comprises : elles passent par leur `FrontController` dédié |
| `$genericPathWriteBlockedEntities` | le chemin générique ne sert que leurs lectures |

Le refus répond 403 sans dire si la ligne existe, et **termine la requête** : un refus suivi de la poursuite de l'exécution a déjà existé, et l'écriture se faisait quand même.

**CORS.** Un en-tête personnalisé nouveau doit être ajouté à la liste codée en dur dans `Router.php` (Tome I, §5.4), sinon le navigateur refuse la requête au vol préliminaire.

## 8.9 Liste de vérification

| Point | À vérifier |
|---|---|
| nouvelle entité exposée | dans `$public_access` ? dans `$publicMethods` à bon escient ? |
| nouvelle action d'écriture d'API | `#[Auth(authorized: 1)]` ? absente de `$publicMethods` ? |
| écriture sur une ressource d'un utilisateur | l'entité est-elle dans `setOwnerColumns()` ? sinon, est-ce un choix documenté ? |
| nouvelle action d'administration | `can()` vérifié — automatiquement pour le CRUD, à la main pour une action métier ? `rights:sync` lancé ? |
| nouveau refus | la requête s'arrête-t-elle vraiment ? le message révèle-t-il l'existence de la ressource ? |
| entrée utilisateur dans un chemin de fichier | validée par liste blanche, puis `basename()` ou `realpath()` ? |
| valeur utilisateur dans `whereIn()` / `whereBetween()` | castée ou validée ? |
| nouvel en-tête HTTP | ajouté à `Access-Control-Allow-Headers` ? |
| méthode de `FrontController` | porte-t-elle une annotation de verbe ? sinon elle est morte |
| secret | dans `.env`, jamais dans un fichier versionné |
| production | `APP_PROD=true`, `JWT_EXPIRE` défini, `TRUSTED_PROXIES` renseigné derrière un proxy |
