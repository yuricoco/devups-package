---
title: "Chapitre 3 — Le journal"
sidebar_position: 2
---

:::tip Nouveau
**Nouveau en 7.0.** `dvups_log` était une table fourre-tout, et `email_log` servait aussi à consigner des erreurs. Le journal est désormais un module dédié, avec une API unique et un gestionnaire global d'erreurs.
:::

## 3.1 L'API

```php
Log::error($messageOuThrowable, $contexte = []);
Log::warning(...);
Log::info(...);
```

Utilisable partout : noyau, front, back-office, CLI. Elle n'interrompt jamais l'appelant : une erreur d'écriture du journal, en base ou sur disque, est elle-même avalée.

```php
try {
    $client->send($payload);
} catch (\Throwable $e) {
    Log::error($e, ['task_id' => $task->getId()]);
    return ['success' => false, 'detail' => t('Envoi impossible pour le moment')];
}
```

## 3.2 Le regroupement par empreinte

Les occurrences identiques sont regroupées en une seule ligne. L'empreinte (`sha1`) est calculée sur la classe, le fichier, la ligne et le message normalisé — les nombres variables, comme un identifiant, sont fusionnés. La ligne garde un compteur d'occurrences (incrémenté de façon atomique), la date de première et de dernière occurrence, le niveau, le contexte.

| Colonne | Contenu |
|---|---|
| `fingerprint` | l'empreinte |
| `level` | `error`, `warning`, `info` |
| `entity_class`, `file`, `line` | l'origine |
| `message`, `context` | le message (aperçu) et le contexte JSON |
| `occurrences`, `first_at`, `last_at` | le regroupement |
| `log_file` | le fichier de débordement, s'il existe |

Un message ou une trace de plus de 1 000 caractères (`Log::INLINE_MAX_LENGTH`) débordent vers `cache/log/<AAAA-MM>/<empreinte>.log`, non versionné, écrasé à chaque nouvelle occurrence. La base ne garde que le chemin et un aperçu.

`Log::purge()` supprime les lignes, et leurs fichiers, plus anciennes que `LOG_RETENTION_DAYS`. Elle n'est jamais appelée automatiquement : la planifier dans `cron.php`.

## 3.3 Le gestionnaire global

`Log::installGlobalHandler()`, appelé une seule fois par `header.php` et `admin/header.php`, installe `set_exception_handler`, `set_error_handler` et une fonction d'arrêt pour les erreurs fatales.

- Le fichier et la ligne enregistrés sont ceux que PHP transmet : l'erreur est attribuée à son vrai point d'origine.
- `error_reporting()` est respecté : une erreur masquée par `@` n'est jamais enregistrée.
- Seuls les niveaux supérieurs ou égaux à `LOG_MIN_LEVEL` (`warning` par défaut) sont écrits : `E_NOTICE` et `E_DEPRECATED` sont ignorés par défaut, sans empêcher PHP de les traiter par ailleurs.
- Une garde contre la récursion empêche une erreur survenue pendant l'écriture du journal de reboucler.
- En production, la réponse est générique : une page, du JSON selon le contexte, ou du texte brut en CLI. En développement, le détail est affiché.

Le gestionnaire n'est pas installé par `dependanceInjection.php`, partagé avec la CLI et PHPUnit, dont il perturberait la gestion d'erreurs.

## 3.4 L'écran

L'écran « Logs » du back-office est en lecture seule : liste, filtres par niveau et par date, fiche de détail qui affiche le fichier de débordement complet. Il est protégé par `can('read', Dvups_log::class)`.

:::note À retenir
**Journal et courriels sont séparés.** `Emaillog` ne consigne que les courriels réellement envoyés (chapitre 8). Une erreur, même liée à un envoi, va dans `Log::`.
:::
