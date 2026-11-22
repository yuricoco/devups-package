---
title: "Chapitre 5 — Le moteur de formulaires"
sidebar_position: 4
---

## 5.1 Les trois classes

| Classe | Rôle |
|---|---|
| `FormManager` (abstraite) | la base d'un `<Entité>Form` ; définit le vocabulaire |
| `FormFactory` | le moteur de rendu : transforme une déclaration de champ en HTML |
| `Form` (extends `FormFactory`) | une API « à la main », hors contrat déclaratif |

## 5.2 Le contrat déclaratif

```php
class TaskForm extends FormManager
{
    public static function init(\Task $task, $action = "")
    {
        return new TaskForm($task, $action);
    }

    public function buildForm()
    {
        $this->title = t('Tâche');
        $this->width = 700;

        $this->fields['name'] = [
            FH_TYPE    => FORMTYPE_TEXT,
            FH_LABEL   => t('Nom'),
            FH_VALUE   => $this->entity->name,
            FH_REQUIRE => true,
        ];
        $this->fields['description'] = [
            FH_TYPE  => FORMTYPE_TEXTAREA,
            FH_LABEL => t('Description'),
            FH_VALUE => $this->entity->description,
        ];
        $this->fields['project.id'] = [
            FH_TYPE    => FORMTYPE_SELECT,
            FH_LABEL   => t('Projet'),
            FH_VALUE   => $this->entity->project->getId(),
            FH_OPTIONS => FormManager::Options_Helper('name', Project::all()),
        ];
        return $this;
    }
}
```

Côté contrôleur (`CrudTrait::formView()`) :

```php
TaskForm::init($task, $action)->buildForm()->addDformjs()->renderForm();
```

## 5.3 Les types de champ

| Constante | Valeur | Rendu |
|---|---|---|
| `FORMTYPE_TEXT` | `'text'` | `<input type="text">` |
| `FORMTYPE_EMAIL`, `FORMTYPE_NUMBER`, `FORMTYPE_PASSWORD` | | entrées typées |
| `FORMTYPE_DATE` / `FORMTYPE_DATETIME` | | `date` / `datetime-local` |
| `FORMTYPE_TEXTAREA` | `'textarea'` | `<textarea>` |
| `FORMTYPE_SELECT` / `FORMTYPE_SELECTGROUP` | | `<select>`, avec ou sans `<optgroup>` |
| `FORMTYPE_CHECKBOX` / `FORMTYPE_RADIO` | | cases, boutons radio |
| `FORMTYPE_FILE` / `FORMTYPE_FILEMULTIPLE` | | envoi d'un ou plusieurs fichiers |
| `FORMTYPE_INJECTION` | `'injection'` | injecte un bloc HTML |
| `FORMTYPE_INJECT_COLLECTIONFORM` | `'injectioncollection'` | déclaré, **non implémenté** — à ne pas utiliser |

Sous-types de fichier : `FILETYPE_IMAGE`, `FILETYPE_DOCUMENT`, `FILETYPE_VIDEO`, `FILETYPE_AUDIO`.

:::note À retenir
**Aucun type n'est un répéteur.** Il n'existe pas de champ « liste de valeurs modifiable ». La forme usuelle est un `FORMTYPE_TEXT` avec une consigne de séparation par virgules, validé dans le setter.
:::

## 5.4 Les clés d'un champ

| Constante | Valeur | Rôle |
|---|---|---|
| `FH_TYPE` | `'type'` | obligatoire : un `FORMTYPE_*` |
| `FH_LABEL`, `FH_VALUE` | | libellé, valeur courante |
| `FH_OPTIONS` | `'options'` | choix d'un `select`, `radio` ou `checkbox` |
| `FH_DIRECTIVE`, `FH_ID` | | attributs HTML, identifiant |
| `FH_FILETYPE` | `'filetype'` | `FILETYPE_*` pour un fichier |
| `FH_REQUIRE` | `'require'` | marque le champ obligatoire |
| `FH_IMBRICATE` | `'imbricate'` | formulaire imbriqué |

`FormFactory` lit en plus, directement : `values`, `src` (aperçu d'un fichier), `lang` (champ traduit, rendu une fois par langue), `placeholder`, `setter`, `hidden`, `callback`.

## 5.5 Les aides à la construction des options

| Méthode | Rôle |
|---|---|
| `FormManager::Options_Helper($value, $entitylist, $key = "id")` | `[id => libellé]` depuis une liste d'entités ; `$value` peut être pointé |
| `FormManager::Options_ToCollect_Helper(...)` | les options moins celles déjà sélectionnées |
| `FormManager::key_as_value(array $array)` | `[valeur => valeur]`, pour une énumération |

```php
FH_OPTIONS => FormManager::Options_Helper('name', Project::all())
FH_OPTIONS => FormManager::key_as_value(['brouillon', 'publié', 'archivé'])
```

`Options_Helper()` résout un libellé issu d'une colonne traduite dans la langue courante (corrigé en 7.0 : il affichait du HTML échappé).

## 5.6 Les méthodes de `FormManager`

| Méthode | Rôle |
|---|---|
| `buildForm()` | à implémenter |
| `addField($name, $option)` | équivalent de `$this->fields[$name] = $option` |
| `addJs($file)` / `addCss($file)` | ressources |
| `addDformjs()` | charge `dform.js`, requis pour la soumission sans rechargement |
| `renderForm()` | rend le HTML |

Propriétés : `$title`, `$width` (600 par défaut), `$entity`, `$fields`, `$formaction`, `$formbutton`.

## 5.7 L'API « à la main »

```php
Form::open($entity, ["action" => "#", "method" => "post"]);
Form::input('name', $entity->name, ['class' => 'form-control']);
Form::textarea('description', $entity->description);
Form::select('project', $options, $entity->project->getId());
Form::file('image', $entity->image, [], FILETYPE_IMAGE);
Form::submit(t('Enregistrer'));
Form::close();
```

Autres méthodes : `number()`, `email()`, `password()`, `selectGroup()`, `autocomplete()`, `radio()`, `checkbox()`, `filepreview()`, `inputarray()`, `imbricate()` / `closeimbricate()`, `reset()`.

## 5.8 Un champ traduit

Si l'entité est traduite et que le champ porte `'lang' => true`, `FormFactory` rend un champ par langue. Une fiche d'une entité traduite sans aucune ligne de traduction se chargeait vide avant 7.1 (défaut D1, Tome II §7.4, désormais corrigé) ; l'enregistrement, lui, refuse avec un motif clair.
