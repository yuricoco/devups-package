---
title: "Chapitre 7 — Les langues de l'interface"
sidebar_position: 6
---

:::tip Nouveau
**Nouveau en 7.0.** Les versions 4.x stockaient les traductions de l'interface en base (`Local_content`, `Local_content_key`, `Page_mapped`) et proposaient plusieurs fonctions concurrentes (`langt()`…). Tout cela a été retiré : les traductions vivent dans des fichiers, `t()` ne fait aucune requête SQL, et le code ISO à deux lettres est la seule référence de langue.
:::

## 7.1 Le code ISO à deux lettres, partout

La langue est identifiée par son code ISO 639-1 (`fr`, `en`…) en session (`local()`), dans l'URL (`?lang=` ou préfixe `/fr/`), en base (`dvups_lang.iso_code`) et dans le nom des fichiers. `header.php` compile une seule fois la correspondance code ↔ id des langues **actives** dans `cache/lang/active.php`, invalidé automatiquement à chaque création, modification ou suppression d'une langue depuis le back-office.

`dv_valid_lang($iso)` n'accepte qu'une valeur de la forme `^[a-z]{2}$` **et** correspondant à une langue active ; sinon elle renvoie `null` et l'appelant retombe sur la langue par défaut. `local()`, `setlang()`, `lang.php` et `t_lang()` passent tous par elle (§8.8).

## 7.2 Les fichiers de traduction

```php
<?php
// lang/fr.php
return [
    'Enregistrer' => 'Enregistrer',
    'Bonjour :nom' => 'Bonjour :nom',
    'Projet introuvable' => 'Projet introuvable',
];
```

```php
<?php
// lang/en.php
return [
    'Enregistrer' => 'Save',
    'Bonjour :nom' => 'Hello :name',
    'Projet introuvable' => 'Project not found',
];
```

Le dossier est `LANG_DIR` (`lang/` par défaut). `lang.php`, à la racine, charge le fichier de la langue courante **une seule fois par requête**.

La clé est le texte tel qu'il est écrit dans le code. Chaque fichier contient la version de sa langue.

## 7.3 Les fonctions

| Fonction | Rôle |
|---|---|
| `t($clé, $params = [])` | la traduction dans la langue courante, ou la clé elle-même si elle manque ; substitue `:param` |
| `tt($clé)` | alias de `t()`, utilisé dans les vues |
| `@tt('clé')` | directive Blade équivalente |
| `t_lang($clé, $iso, $params = [])` | la traduction dans une langue **explicite**, indépendante de la session |
| `local()` | la langue courante : `?lang=`, puis la session, puis `__lang` |
| `setlang($iso)` | fixe la langue en session |

```php
echo t('Bonjour :nom', ['nom' => $user->firstname]);

// Courriel envoyé à un destinataire dont la langue diffère de celle de l'expéditeur :
$subject = t_lang('Votre compte est activé', $user->lang ?? __default_lang);
```

`t_lang()` est faite pour les courriels (`web/views/mails/*.blade.php`) : la langue du destinataire n'est pas celle de la requête en cours.

## 7.4 La collecte des clés manquantes

Quand `LANG_COLLECT` est actif — par défaut en développement, **jamais en production** —, une clé absente rencontrée par `t()` est ajoutée à tous les fichiers `lang/*.php`, avec pour valeur la clé elle-même. L'écriture est sûre : verrou sur un fichier dédié, fichier temporaire, puis renommage. Le traducteur n'a plus qu'à remplir les valeurs.

## 7.5 Les commandes

```bash
php devups lang:scan       # parcourt le code et complète les fichiers en une fois
php devups lang:missing    # liste, par langue, les clés encore non traduites
```

`lang:scan` analyse le code avec le tokenizer de PHP (`token_get_all()`) : il reconnaît les appels `t('...')` et `tt('...')` dont la clé est littérale, y compris avec des apostrophes échappées. Une clé construite (`t('statut.' . $x)`) est ignorée et signalée.

:::note À retenir
**Règle de travail.** Écrire des clés littérales. Une clé construite échappe au scan et à la relecture ; préférer `t('Statut : :s', ['s' => $x])` à `t('statut.' . $x)`.
:::
