---
title: "Chapitre 8 — Courriels : modèles et journal"
sidebar_position: 7
---

## 8.1 `DMail`

`dclass/lib/DMail.php` envoie un courriel par PHPMailer, avec les paramètres SMTP de `.env`, à partir d'un gabarit Blade de `web/views/mails/` (notation pointée : `mails.welcome` → `web/views/mails/welcome.blade.php`). Chaque envoi, réussi ou non, est consigné dans `Emaillog`.

Les gabarits fournis (`welcome`, `otp`, `email`, `invitation`) sont neutres et traduits par `t_lang()` dans la langue du destinataire.

## 8.2 `Reportingmodel`

Un `Reportingmodel` décrit un modèle de courriel : nom (le gabarit), objet, description, aperçu du contenu attendu (`payload`), libellés traduits.

| Écran ou action | Effet |
|---|---|
| « Collecter les modèles » | parcourt `web/views/mails/*.blade.php` et crée les `Reportingmodel` manquants ; ne supprime jamais rien |
| créer un modèle | crée aussi son fichier gabarit s'il n'existe pas ; n'écrase jamais un fichier existant |
| aperçu | rend le gabarit avec un payload d'exemple, sans envoi |
| envoi de test | envoie réellement, via `DMail` |
| `Reportingmodel::sendMail()` | envoi depuis le code, consigné dans `Emaillog` |

Le lien entre les deux sens — du fichier vers la base, de la base vers le fichier — fait du dossier `web/views/mails/` et de l'écran « Reporting » deux vues du même catalogue.

:::note À retenir
**Le mot de passe SMTP n'est jamais affiché.** L'aide intégrée des écrans de courriel montre l'hôte, le port et l'expéditeur, mais masque le mot de passe.
:::

## 8.3 `Emaillog`

| Colonne | Contenu |
|---|---|
| `destinataire`, `sujet`, `modele` | l'envoi |
| `statut` | `envoye` ou `echec` |
| `erreur` | le message d'erreur, en cas d'échec |

```php
Emaillog::logSuccess($sujet, $destinataires, 'mails.welcome');
Emaillog::logFailure($sujet, $destinataires, $erreur, 'mails.welcome');
```

`DMail` et `Reportingmodel::sendMail()` appellent ces deux méthodes. L'écran est en lecture seule.
