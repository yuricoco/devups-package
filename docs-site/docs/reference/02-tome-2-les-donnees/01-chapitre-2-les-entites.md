---
title: "Chapitre 2 — Les entités"
sidebar_position: 1
---

## 2.1 La chaîne d'héritage

```
stdClass
 └─ ModelSerializer      (dclass/devups/model/ModelSerializer.php)
     └─ Model            (dclass/devups/model/Model.php)
         │  use ModelTrait, DateTimeTrait
         └─ <Entité>     (src/<composant>/<Module>/Entity/)
```

Entre `Model` et l'entité, une classe de base réutilisable de `dclass/extends/` peut s'intercaler. En 7.0 :

| Classe | Porte | Entités du socle qui en héritent |
|---|---|---|
| `UserCore` | les colonnes communes d'un utilisateur : identité, `email`, `phonenumber`, `password`, activation, réinitialisation, `lang`, jetons de session | `User` |
| `ImageCore` | une image : `reference`, `name`, `description`, `image`, `uploaddir`, dimensions | `Cmsresource`, `Tree_item_image` |
| `Dv_langCore` | une langue | `Dvups_lang` |
| `Dvups_migration` | une migration jouée | (table `dvups_migration`) |
| `Dvups_config_item` | déclarée traduite, sans table ni appelant | — (code mort) |

Toutes portent l'attribut `#[ORM\MappedSuperclass]` : sans lui, Doctrine ignore les colonnes héritées et la table de l'entité enfant est créée incomplète.

:::warning Piège
**Piège converti au LOT 1 du chantier v8.0.** Une entité fille qui redéclarait elle-même une propriété déjà fournie par sa `MappedSuperclass` (ex. `$id`, ou `created_at`/`updated_at`/`deleted_at` hérités via un trait partagé) était tolérée par l'ancien lecteur de docblock, mais lève désormais `MappingException: Duplicate definition of column` sous l'`AttributeDriver` de Doctrine ORM 3. Ne jamais redéclarer une colonne déjà portée par la `MappedSuperclass` ; un trait partagé par plusieurs `MappedSuperclass` doit redéclarer lui-même ses propres attributs sur chacune (un simple `use` du trait ne suffit pas).
:::

:::warning Piège
**`StatusTrait` est inutilisable.** Ce trait de `dclass/extends/` interroge une entité `Status_entity` qui n'existe plus dans le socle. Pour les statuts, utiliser l'API de `Status` (Tome IV, chapitre 6).
:::

## 2.2 L'anatomie d'une entité

```php
<?php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "task")]
class Task extends Model implements JsonSerializable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Column(name: "name", type: "string", length: 255)]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: "description", type: "text", nullable: true)]
    protected $description;

    /**
     * @var \Project
     */
    #[ORM\ManyToOne(targetEntity: "\Project")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $project;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->project = new Project();
    }

    public function getId() { return $this->id; }

    public function jsonSerialize()
    {
        return ['id' => $this->id, 'name' => $this->name] + parent::jsonSerialize();
    }
}
```

Quatre conventions, toutes mesurées dans le code du framework :

**1. Une colonne est `protected`, une relation est `public`.** `ModelTrait::__get()` traite différemment une propriété déclarée et une propriété absente. Sur un attribut inconnu, il émet une notice puis arrête l'exécution (`die`) : l'écran entier disparaît. Deux exceptions : un attribut préfixé `_` (chargement paresseux d'une relation, ci-dessous) et une colonne traduite déclarée dans `$dvtranslated_columns`, qui renvoie `null` au lieu d'arrêter.

:::note À retenir
**Le préfixe `_`.** `$task->_project` hydrate la relation à la demande et met le résultat en cache sur l'objet. C'est la seule forme d'attribut non déclaré que `__get()` accepte.
:::

**2. Le constructeur instancie les relations.** `$this->project = new Project();` — sans cela, `$task->project->getName()` échoue sur `null`.

**3. L'attribut doit toucher la classe.** Depuis le passage aux attributs PHP 8 (chantier v8.0), `#[ORM\Entity]` doit précéder directement `class Xxx` - mais, contrairement à l'ancien lecteur de docblock, un docblock de prose intercalé entre l'attribut et la classe ne casse rien : un attribut est lu par réflexion (`getAttributes()`), indépendamment des commentaires.

**4. `jsonSerialize()` ajoute, puis délègue.** `+ parent::jsonSerialize()` récupère la sérialisation du socle, qui remonte `$this->dvserialised` et les relations déclarées dans `$this->with`.

## 2.3 L'attribut de référence

| Attribut PHP 8 | Portée | Rôle |
|---|---|---|
| `#[ORM\Entity]` | classe | déclare une entité Doctrine |
| `#[ORM\MappedSuperclass]` | classe | déclare une classe de base dont les colonnes sont héritées |
| `#[ORM\Table(name: "...")]` | classe | le nom de table |
| `#[ORM\Table(..., uniqueConstraints: [new ORM\UniqueConstraint(name: ..., columns: [...])])]` | classe | contrainte unique nommée |
| `#[ORM\Id]`, `#[ORM\GeneratedValue]` | propriété | identité, auto-incrément |
| `#[ORM\Column(name:, type:, length:, nullable:)]` | propriété | une colonne |
| `#[ORM\ManyToOne(targetEntity: "\X")]` | propriété | relation propriétaire |
| `#[ORM\OneToMany(targetEntity: "\X", mappedBy: "y")]` | propriété | relation inverse |
| `#[ORM\ManyToMany(targetEntity: "\X")]` | propriété | relation n-n |
| `#[ORM\JoinColumn(onDelete:, nullable:)]` | propriété | la clé étrangère |
| `@var` | propriété | le type PHP, lu par l'hydratation (reste en docblock - pas un attribut Doctrine) |

## 2.4 Les propriétés `dv*`

`ModelTrait` déclare des propriétés de contrôle sur toute entité. Les connaître évite de les prendre pour des colonnes.

| Propriété | Défaut | Rôle |
|---|---|---|
| `$dvfetched` | `false` | l'entité a déjà été hydratée depuis la base |
| `$dvold` | `false` | une **copie** de l'état « avant », pour calculer le `SET` d'un `UPDATE` |
| `$dvsoftdelete` | `false` | si vrai, `__delete()` écrit `deleted_at` au lieu de supprimer |
| `$dvinrelation` | `false` | l'entité est chargée comme relation d'une autre |
| `$dvtranslate` | `false` | l'entité est traduite (chapitre 7) |
| `$dvid_lang` | `false` | la langue courante de l'instance |
| `$dvtranslated_columns` | `[]` | la liste des colonnes traduites |
| `$dvpersistence` | `[]` | les valeurs à écrire |
| `$dvserialised` | `[]` | les valeurs à sérialiser en JSON |
| `$with` (protégée) | `[]` | les relations à inclure dans `jsonSerialize()` |

:::tip Nouveau
**Corrigé en 7.0 — `$dvold` est un vrai clone.** `Model::hydrate()` posait autrefois `$this->dvold = $this`, une référence au même objet : modifier un champ modifiait aussi sa valeur « avant », aucune différence n'était détectée et l'`UPDATE` n'écrivait rien. `hydrate()` pose désormais `clone $this`, comme le faisait déjà `find()`. Hydrater depuis `new X($id)` ou depuis `X::find($id)` donne le même résultat à l'écriture.
:::
