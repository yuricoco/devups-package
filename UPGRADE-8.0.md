# Guide de migration vers Devups 8.0

Ce fichier documente, lot par lot, chaque rupture introduite par le
chantier v8.0 (voir TODO.md, section "Chantier v8.0") : ce qui change,
comment migrer un projet existant, et la commande de migration
automatique quand il en existe une.

## LOT 1 - Socle

### PHP 8.3 minimum

**Avant** : `composer.json` exigeait `"php": ">=8.2"`.

**Apres** : `"php": ">=8.3"`. La CI GitHub (workflow `tests`) tourne
desormais sur la matrice PHP 8.3/8.4/8.5 (8.2 retire).

**Comment migrer** : mettre a jour l'interpreteur PHP du projet vers
8.3 ou plus avant de passer a cette version de Devups. Aucun
changement de code applicatif requis pour ce point seul.

### Doctrine ORM 3 (DBAL 4)

**Avant** : `doctrine/orm` ^2.1, mapping par annotations docblock
(`@Entity`, `@Column`, ...), `Doctrine\ORM\Tools\Setup::
createAnnotationMetadataConfiguration()`, `EntityManager::create()`
(methode statique).

**Apres** : `doctrine/orm` ^3.7 (`doctrine/dbal` suit en ^4, transitif).
Mapping par attributs PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`, ...),
configuration via `ORMSetup::createAttributeMetadataConfig()`,
construction explicite `new EntityManager(DriverManager::
getConnection($conn, $config), $config)`.

Consequences pour un projet qui etendait directement l'API Doctrine du
framework (rare - l'ORM d'execution de Devups reste Tchutte, Doctrine
n'est utilise que pour les metadonnees/schema) :

- `doctrine/cache` et `doctrine/annotations` ne sont plus des
  dependances (retires de `composer.json`) - Doctrine ORM 3 n'en a
  plus besoin pour ses propres caches de metadonnees (PSR-6 natif).
  `symfony/cache` est ajoute a la place (requis par
  `ORMSetup::createAttributeMetadataConfig()` pour son cache de
  developpement).
- `bin/doctrine` (et `.php`/`.bat`) : doctrine/orm 3 ne fournit plus de
  binaire console pre-construit (l'ancien `vendor/doctrine/orm/bin/
  doctrine` a disparu). Ces fichiers, versionnes dans le depot comme
  repli de `dv_doctrine_command()` (voir `dclass/generator/
  builder.php`), ont ete reecrits : ils construisent eux-memes
  l'application console via `ConsoleRunner::run()` +
  `SingleManagerProvider`. `cli-config.php` retourne desormais ce
  provider (et non plus un `HelperSet`, API supprimee).
- Toute commande CLI custom basee sur `ConsoleRunner::
  createHelperSet()` doit migrer vers `EntityManagerProvider` (voir
  `cli-config.php`).

**Comment migrer** : executer `composer update` (le `composer.json`
fourni epingle les bonnes versions). Aucune action manuelle requise
pour un projet qui ne touche pas directement l'API Doctrine - toute la
lecture de metadonnees interne au framework (DBAL, Model, Controller,
generateur) continue de fonctionner a l'identique (les objets
`FieldMapping`/`AssociationMapping` de l'ORM 3 restent accessibles par
cle de tableau, compatibilite ArrayAccess conservee par Doctrine).

### Entites : annotations -> attributs

**Avant** : les entites Doctrine (`src/*/Entity/*.php`, classes de base
`dclass/extends/*Core*.php`/`Dvups_migration.php`) declaraient leur
mapping via des annotations dans un docblock :

```php
/**
 * @Entity @Table(name="status", uniqueConstraints={
 *     @UniqueConstraint(name="uq_status_entity_key", columns={"entity", "_key"})
 * })
 * */
class Status extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
}
```

**Apres** : le meme mapping s'exprime en attributs PHP 8 (le docblock,
lui, ne garde que le texte libre/`@var` restant) :

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "status", uniqueConstraints: [new ORM\UniqueConstraint(name: "uq_status_entity_key", columns: ["entity", "_key"])])]
class Status extends Model
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
}
```

Toutes les entites du framework (41 fichiers : `src/**/Entity/*.php`,
`dclass/extends/{Dv_langCore,Dvups_config_item,Dvups_migration,
ImageCore,UserCore}.php`, trait `dclass/devups/model/DateTimeTrait.php`)
ont ete converties. **Aucun changement de schema** : un test
d'integration dedie (`tests/Integration/
TreeStatusUniqueConstraintAttributesTest::
testSchemaUpdateReportsNoPendingDifference`) verifie que
`php devups schema:update --dump-sql` reste vide sur une base
installee en 7.1.

Piege rencontre et corrige pendant la conversion : une entite qui
redeclarait elle-meme une propriete deja fournie par sa classe parente
(`MappedSuperclass` - ex. `$id` sur `Dvups_entity`/`User`/
`Tree_item_image`/`Cmsresource`, ou `created_at`/`updated_at`/
`deleted_at` herites via `DateTimeTrait` sur les 3 `MappedSuperclass`
qui l'utilisent) etait tolere par l'ancien `AnnotationDriver` mais leve
desormais une `MappingException: Duplicate definition of column` sous
`AttributeDriver` (Doctrine ORM 3). Corrige en supprimant la
redeclaration redondante dans les entites filles, et en redeclarant
explicitement (jamais via un simple `use` du meme trait, insuffisant -
verifie) `created_at`/`updated_at`/`deleted_at` sur les 3
`MappedSuperclass` concernees (`Dvups_config_item`, `ImageCore`,
`UserCore`) pour que Doctrine retrouve une classe declarante coherente
entre l'heritage de mapped-superclass et le scan direct de l'entite
fille.

**Comment migrer un projet utilisateur** : la commande

```bash
php devups upgrade:entities [--dry-run] [chemin]
```

convertit automatiquement tous les fichiers PHP d'un repertoire
(`src/` par defaut) en appliquant le MEME convertisseur
(`dclass/generator/EntityAttributeConverter.php`) que celui utilise
pour migrer le framework lui-meme. `--dry-run` liste les fichiers qui
seraient modifies sans rien ecrire. La commande est idempotente (un
second passage sur des fichiers deja en attributs ne modifie rien) et
preserve le reste du docblock (texte libre, `@var`, `@param`) ainsi
que tout le code autour.

Formes reconnues par le convertisseur (voir TODO.md ETAPE 1.0 pour
l'inventaire complet) : `@Entity @Table(name=...[, uniqueConstraints=
{...}])`, `@MappedSuperclass`, `@Id @GeneratedValue
@Column(type="integer")`, `@Column(...)` (name/type/length/nullable/
options), `@Id @ManyToOne(...)`, `@ManyToOne(...)`, `@JoinColumn(...)`.
Une annotation Doctrine non reconnue (forme non rencontree dans
Devups - `@OneToMany`, `@ManyToMany`, `@Embeddable`, ...) est laissee
telle quelle dans le docblock, jamais supprimee silencieusement -
a convertir a la main si rencontree, puis verifier avec
`php devups schema:update --dump-sql` (doit rester vide).

### Generateur (entites generees)

`dclass/generator/BackendGenerator.php` (cree une entite/une entite de
traduction a partir d'un datacore) emet desormais directement des
attributs PHP 8 pour toute NOUVELLE entite generee - aucune action
requise, mais un composant genere avant ce chantier et jamais
regenere depuis reste en annotations tant qu'il n'est pas repasse par
`upgrade:entities` ou regenere.
