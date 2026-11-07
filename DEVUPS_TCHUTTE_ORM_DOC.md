# DEVUPS_TCHUTTE_ORM_DOC — QueryBuilder / DBAL / Model

Référence d'utilisation de la couche d'accès aux données du socle Devups
(`dclass/devups/Tchutte/DBAL.php`, `dclass/devups/Tchutte/QueryBuilder.php`,
`dclass/devups/model/Model.php`, `dclass/devups/model/ModelTrait.php`).
Le nom vient de l'auteur du socle (« Tchutte »), pas d'un produit externe.

**Objectif de ce fichier** : documentation de référence, pas un tutoriel.
Écrit pour qu'un futur passage (moi-même ou quiconque reprend ce dépôt)
puisse retrouver rapidement « comment on fait X avec le query builder »
sans re-fouiller `dclass/`. Chaque affirmation ci-dessous a été vérifiée à
l'exécution pendant la phase F (2026-09-03) — voir `PLAN.md` F-01 à F-05b
pour le détail des vérifications et `aiMemory/memory_2026-09-03_v1.md` pour
les pièges trouvés en les cherchant.

**Ne pas confondre avec** `DEVUPS_GUIDE.md` (générateur, datacores, styles
d'API, pièges de `schema:update`) : ce fichier-ci ne couvre QUE la
construction de requêtes et l'écriture de données une fois les entités déjà
générées.

---

## Sommaire

1. [Architecture en trois classes](#1-architecture-en-trois-classes)
2. [Points d'entrée statiques sur une entité](#2-points-dentrée-statiques-sur-une-entité)
3. [Construire une clause WHERE](#3-construire-une-clause-where)
4. [Relations et hydratation](#4-relations-et-hydratation)
5. [Récupérer des résultats — le piège null vs entité vide](#5-récupérer-des-résultats--le-piège-null-vs-entité-vide)
6. [Écrire (create/update/delete)](#6-écrire-createupdatedelete)
7. [Transactions (Model::beginTransaction/commit/rollBack/transaction)](#7-transactions)
8. [La connexion partagée (Model::pdo())](#8-la-connexion-partagée)
9. [Mode debug — prévisualiser le SQL sans exécuter](#9-mode-debug)
10. [Pièges et vulnérabilités connus](#10-pièges-et-vulnérabilités-connus)
11. [Cas d'usage prêts à copier](#11-cas-dusage-prêts-à-copier)

---

## 1. Architecture en trois classes

```
Model (dclass/devups/model/Model.php)
  └─ use ModelTrait (dclass/devups/model/ModelTrait.php)
       → points d'entrée STATIQUES : Entity::find(), ::where(), ::create(), ...
       → chaque point d'entrée fabrique un new QueryBuilder($entity) et lui délègue

QueryBuilder (dclass/devups/Tchutte/QueryBuilder.php)
  extends DBAL
       → construit le SQL (SELECT/INSERT/UPDATE/WHERE/JOIN/ORDER BY...)
       → API fluide, chaque méthode `return $this`

DBAL (dclass/devups/Tchutte/DBAL.php)
  extends Database (config/Database.php)
       → exécute le SQL construit par QueryBuilder, hydrate les entités
       → EXPOSE la connexion PDO partagée du socle (DBAL::pdo(), F-01)
```

`Doctrine\ORM\EntityManager` (`DBAL::getEntityManager()`) est une **quatrième
brique, séparée**, qui ne sert que pour :
- lire les métadonnées d'annotation (`@Column`, `@ManyToOne`, ...) — c'est
  comme ça que `mappingProd()` sait quelle est la table, les colonnes, les
  associations d'une entité ;
- `schema:update` (migrations).

**Doctrine n'exécute jamais une requête métier dans ce framework.** Toute
lecture, écriture, agrégation passe par DBAL/QueryBuilder. Confirmé avec
l'auteur du projet pendant F-01 — ne pas supposer l'inverse en lisant le
mot « ORM » dans le nom de ce fichier.

Depuis F-01/F-03 (2026-09-03) : **une seule connexion PDO** pour tout le
projet (`DBAL::pdo()`/`Model::pdo()`), quel que soit le point d'entrée. Avant
F-01, `DBAL::__construct()` mettait en cache une connexion différente par
nom d'entité — un `Ticket::find()` et un `Print_job::find()` utilisaient
deux connexions MySQL distinctes. Voir §8.

---

## 2. Points d'entrée statiques sur une entité

Toute entité (`class Ticket extends Model`) hérite de `ModelTrait`, qui
expose ces méthodes statiques directement sur le nom de l'entité :

| Méthode | Rôle | Retour sur 0 résultat |
|---|---|---|
| `Entity::find($id)` | lecture par id (ou tableau d'ids/de conditions) | entité **vide** (jamais null), sauf id un tableau numérique → `[]` |
| `Entity::findOrNull($id)` | comme `find()` mais nullable | `null` |
| `Entity::findrow($id)` | comme `find()`, forme historique | entité vide |
| `Entity::where($col, $op, $val)` | fabrique un `QueryBuilder` déjà filtré | — (chaîner `->get()`/`->firstOrNull()`/...) |
| `Entity::first()` | premier enregistrement (sans filtre) | entité vide |
| `Entity::firstOrNull()` | idem, nullable | `null` |
| `Entity::firstOrCreate($constraint, $data)` | lit ou crée si absent | — (crée toujours quelque chose) |
| `Entity::firstOrNew($constraint, $data)` | lit ou instancie (sans insérer) | — |
| `Entity::count($col=null, $val=null)` | `COUNT(*)` filtré ou non | `int` |
| `Entity::create([...])` / `Entity::create([...], [...])` | insertion(s), retourne le(s) id(s) | — |
| `Entity::initQb($defaultjoin = true)` | `QueryBuilder` vierge sur cette entité, prêt à chaîner n'importe quelle méthode du §3 | — |
| `Entity::lazyloading($order = "")` | listing paginé style API générique (`?dfilters=on&...`, `?next=&per_page=`) — voir `CLAUDE.md` §3.4, pas répété ici | — |

`Entity::where(...)` est le point d'entrée le plus utilisé au quotidien :
il équivaut à `Entity::initQb()->where(...)` et retourne un `QueryBuilder`
sur lequel chaîner tout le §3.

`$defaultjoin = false` (sur `initQb()`/le 3ᵉ argument du constructeur
`QueryBuilder`) désactive les LEFT JOIN automatiques des relations
ManyToOne (§4) — utile pour une requête qui n'a besoin d'aucune relation et
où le JOIN serait juste du bruit.

---

## 3. Construire une clause WHERE

Toutes ces méthodes vivent sur `QueryBuilder`, chacune `return $this`
(chaînables à volonté) :

### Comparaisons simples

```php
->where('this.status', '=', 'active')     // avec opérateur explicite
->where('this.id', $id)                    // 2 arguments = égalité implicite
->where(['status' => 'active', 'type' => 'X'])   // tableau = plusieurs égalités en AND
->andwhere($col, $op, $val)                 // alias explicite de where() en AND
->orWhere($col, $op, $val)                  // même chose, lié en OR
->whereId($id)                              // where('this.id', '=', $id), ou whereIn si $id est un tableau numérique
```

`$column` accepte aussi directement un objet entité :
`->where($someAgencyInstance)` génère `agency.id = :agency_id` (ou
`IS NULL` si l'entité n'a pas d'id) — pratique pour filtrer par relation
sans écrire `->where('this.agency_id', '=', $agency->getId())`.

Opérateurs reconnus par `where()` (propriété privée `$operators`) :
`= < > <= >= <> != <=> like 'like binary' 'not like' between ilike is
& | ^ << >> rlike regexp 'not regexp' ~ ~* !~ !~* 'similar to'
'not similar to' 'not ilike' ~~* !~~*`. Un opérateur **absent** de cette
liste tombe dans une branche qui **ignore silencieusement `$value`** et lie
l'opérateur lui-même comme valeur (`$this->parameters[$attrib] = $operator`)
— bug de framework, pas une utilisation à chercher à reproduire. Rester sur
la liste ci-dessus, ou utiliser une méthode dédiée (`whereLike`, `whereIn`,
`whereGroup`...) pour tout le reste.

### Recherche « contient » (LIKE)

```php
->whereLike('label', $q)              // LIKE '%$q%', lié en AND
->whereLike('label', $q, 'l')         // LIKE '$q%'   (préfixe)
->whereLike('label', $q, 'r')         // LIKE '%$q'   (suffixe)
->orWhereLike('city', $q)             // même chose, lié en OR
```

Paramétrées depuis F-05b (2026-09-03) — voir §10, c'était une injection SQL
avant. `like($v)` / `_like($v)` / `like_($v)` existent aussi (forme
chaînée après un `->where('col')` sans opérateur, pour construire
`col LIKE '%v%'` en deux appels) — même correctif appliqué, à préférer
`whereLike()` en général, plus lisible.

### Groupe de conditions en OR (`whereGroup`, nouveau F-05b)

Le mécanisme générique de filtrage de l'API (`?dfilters=on`) ne combine
qu'en ET (`CLAUDE.md` §3.4) — pour une recherche multi-champs en OR,
`whereGroup()` pose une closure qui construit ses propres conditions sur le
même builder, puis les regroupe entre parenthèses :

```php
Agency::where('this.status', '=', 'active')
    ->whereGroup(function ($qb) use ($q) {
        $qb->whereLike('label', $q)
           ->orWhereLike('city', $q)
           ->orWhereLike('address', $q);
    })
    ->orderBy('this.label')
    ->get();
// génère : WHERE ... AND status = :x AND (label LIKE :a OR city LIKE :b OR address LIKE :c)
```

Deuxième argument optionnel `$outerLink` (`'AND'` par défaut) pour rattacher
le groupe entier en OR au reste s'il le faut. **Ne pas confondre avec**
`whereRaw($constraint, $link)` / `where_str($constraint, $link)` (identiques
entre elles, existent déjà) qui prennent une **chaîne SQL brute**, jamais
paramétrée — dernier recours seulement, jamais avec une valeur venant d'un
utilisateur (voir §10).

### Listes de valeurs

```php
->whereIn('this.id', [1, 2, 3])              // this.id IN (1,2,3)
->whereIn('this.status', ['a','b'], 'NOT')   // this.status NOT IN ('a','b')
->whereInReverse($value, ['col1','col2'])    // $value IN (col1, col2) - sens inverse, rare
```

⚠️ Voir §10 : `whereIn()` n'échappe pas les chaînes qu'elle quote (`qb_sanitize()`
fait juste `"'$value'"`) — un tableau de valeurs contrôlées par l'utilisateur
y est aussi injectable, exactement comme l'était `whereLike()` avant F-05b.
**Non corrigé** à ce jour (trouvé pendant F-05b, hors périmètre de cette
tâche) — passer par `whereGroup()`+`where('col','=',$v)` répété si la
valeur n'est pas de confiance.

### Autres filtres

```php
->whereNull('col') / ->whereNotNull('col')
->whereBetween('col', [$min, $max])   // ⚠️ interpolation directe, voir §10 - mêmes réserves que whereIn()
```

### Tri, regroupement, limite

```php
->orderBy('this.label')                 // ASC implicite (défaut MySQL)
->orderBy('this.label', 'DESC')
->orderBy('col1')->orderBy('col2')      // cumulables, virgule ajoutée automatiquement
->rand()                                 // ORDER BY RAND()
->groupBy('this.agency_id')
->limit(10)                              // LIMIT 10
->limit(0, 10)                           // LIMIT 0, 10 (offset, taille)
->limit(-5)                              // les 5 DERNIERS (calcule le compte total en interne)
```

`orderBy('service.position')` (colonne d'une relation JOINée, pas de
l'entité de base) fonctionne directement **si** la relation est
LEFT-JOINée par défaut (`§4`) — vérifié empiriquement en F-05b.

---

## 4. Relations et hydratation

Chaque `@ManyToOne(targetEntity="\X")` de l'entité de base est **auto
LEFT-JOINée** dès qu'un `QueryBuilder` est construit avec `$defaultjoin =
true` (par défaut). Alias de jointure = nom de la table cible en minuscule
(`LEFT JOIN \`service\` ON \`service\`.id = agency_service.service_id`).

**Piège vérifié empiriquement (pas supposé)** : ce LEFT JOIN **ne charge
que l'id** de l'entité liée. Accéder à `$row->service->label` sans rien de
plus renvoie une chaîne vide, pas une erreur, pas de retard perceptible —
silencieux. Constaté en construisant F-05b :

```php
// SANS with() : $row->service existe (objet non-null), mais TOUTES ses
// colonnes hors id sont vides.
$rows = Agency_service::where('this.agency_id', '=', $id)->get();
echo $rows[0]->service->label; // "" - pas d'erreur, juste vide

// AVEC with('service', [...colonnes...]) : hydrate reellement les colonnes demandées
$rows = Agency_service::where('this.agency_id', '=', $id)
    ->with('service', ['code', 'label', 'icon'])
    ->get();
echo $rows[0]->service->label; // "Versement"
```

Signature : `with(string $entityName, array $columns = [])`. `$entityName`
est le nom de la relation tel que déclaré dans l'entité (`public $service;`
→ `'service'`), pas le nom de la classe cible si les deux diffèrent.

`without(...$entity)` fait l'inverse : exclut des relations du JOIN par
défaut (utile pour une requête qui n'a besoin d'aucune des relations
auto-jointes, sans pour autant désactiver TOUT le mécanisme via
`initQb(false)`).

---

## 5. Récupérer des résultats — le piège null vs entité vide

**Le piège le plus coûteux à ne pas refaire.** Deux familles de méthodes
terminales, avec un comportement différent sur « aucun résultat » :

| Méthode | 0 résultat |
|---|---|
| `->get()` | tableau vide `[]` |
| `->getInstance()` / `->first()` | **objet entité VIDE** (`new $this->objectName`, jamais `null`) |
| `->firstOrNull()` | `null` |
| `Entity::find($id)` (id scalaire) | objet entité VIDE |
| `Entity::findOrNull($id)` | `null` |
| `->getRows($callback)` | tableau de lignes brutes (pas d'hydratation d'entité) |
| `->getValue()` / `->count()` / `->sum()` / `->avg()` / `->max()` | valeur scalaire (`0`/`null` selon le cas SQL) |

**Conséquence directe** : après un `find()`/`getInstance()`/`first()` sans
le suffixe `OrNull`, ne JAMAIS tester `if ($entity)` seul — un objet
PHP est toujours "truthy", entité vide ou pas. Le test correct est
`if ($entity && $entity->getId())`, motif déjà systématique dans ce
projet (`AgencyFrontController::create()/update()`, entre autres) :

```php
// FAUX - ne detecte jamais une entite vide
$existing = Agency::where('this.code', '=', $code)->getInstance();
if ($existing) { /* toujours vrai */ }

// CORRECT
$existing = Agency::where('this.code', '=', $code)->firstOrNull();
if ($existing && $existing->getId()) { /* vraiment trouvé */ }
```

`get()`/`getInstance()`/`first()` acceptent un premier argument `$column`
(`"*"` par défaut) pour restreindre les colonnes SQL projetées — l'objet
retourné reste une entité complète, les colonnes non sélectionnées restent
juste à leur valeur PHP par défaut (pas testé si ça vaut le détour plutôt
que de mapper explicitement le tableau de sortie, voir §11 exemple
« réponse API au contrat figé »).

`QueryBuilder::$debug = true` transforme `get()`/`getInstance()`/`first()`
(et d'autres) en un simple retour du SQL/params sans exécuter — voir §9.

---

## 6. Écrire (create/update/delete)

```php
$id = Entity::create(['col1' => 'val', 'col2' => 42]);   // INSERT, retourne l'id
$ids = Entity::create(['a'=>1], ['a'=>2]);                 // plusieurs lignes, retourne un tableau d'ids

$entity = Entity::find($id);
$entity->col1 = 'nouvelle valeur';   // magic __set (ModelTrait), même sur une propriété `protected`
$entity->__update();                  // UPDATE de TOUTES les colonnes actuellement sur l'objet
$entity->__update(['col1' => 'x']);   // UPDATE cible, seulement les colonnes passées

$entity->__insert();                  // forme bas niveau de create() sur une instance déjà remplie
$entity->__delete();                  // DELETE (ou soft-delete si dvsoftdelete)
```

⚠️ `Entity::create([...])` (API basse, `Model::create()`/`DBAL::_createDbal()`)
attend le **nom de colonne physique** pour une relation
(`'customer_id' => 3`), jamais le nom de propriété (`'customer' => 3` échoue
silencieusement avec `Unknown column 'customer'`, message qui ne nomme pas
la vraie cause). Vérifié P2-A, voir `aiMemory/memory_2026-08-31_v1.md` §2.

Pas de méthode fluide `setXxx()` générée — l'affectation directe
(`$entity->col = valeur`) est LE mécanisme, via le `__set` magique de
`ModelTrait`.

---

## 7. Transactions

Depuis F-02 (2026-09-03), sur `Model` (héritée par toute entité) :

```php
Model::beginTransaction();   // niveau 0 : vraie transaction PDO. niveau >0 : SAVEPOINT
try {
    $ticket->__insert();
    $printJob->__insert();
    Model::commit();          // niveau 0 : COMMIT reel. niveau >0 : rien (le SAVEPOINT survit)
} catch (\Throwable $e) {
    Model::rollBack();        // niveau 0 : ROLLBACK reel. niveau >0 : ROLLBACK TO SAVEPOINT (annule CE niveau seul)
    throw $e;
}
```

**Forme recommandée par défaut** (celle qui protège contre un `rollBack()`
oublié sur un retour anticipé) :

```php
Model::transaction(function () use ($ticket, $printJob) {
    $ticket->__insert();
    $printJob->__insert();
    return $ticket->getId();   // un retour ANTICIPE ici commit quand meme - PHP rend juste
                                 // la main a ce bloc, qui enchaine sur commit() normalement.
});
```

**Imbrication transparente** : deux méthodes qui appellent chacune
`Model::transaction()` (ou `beginTransaction()`/`commit()`/`rollBack()` à la
main) peuvent s'appeler l'une l'autre sans jamais lever PDO's "There is
already an active transaction" — seul le niveau 0 touche réellement la
connexion, les niveaux internes posent/libèrent un `SAVEPOINT`. Voir
`model_transaction_smoke.php` pour les scénarios vérifiés (annulation d'un
niveau interne seul, annulation externe qui efface aussi ce qu'un niveau
interne avait « validé », exception, retour anticipé).

⚠️ Contrepartie du F-01 (connexion unique) : une transaction laissée ouverte
(jamais commit/rollback) empoisonne TOUT ce qui suit dans la même requête
HTTP/le même process. Toujours passer par `try/catch` (ou, mieux,
`Model::transaction()`), jamais un `beginTransaction()` isolé sans son
`commit()`/`rollBack()` garanti.

---

## 8. La connexion partagée

```php
$pdo = Model::pdo();   // ou \DBAL::pdo() - Model::pdo() delegue a DBAL::pdo()
```

Retourne le **même objet PDO** que celui utilisé par `Entity::find()`,
`->__insert()`, `->__update()`, tout le query builder — vérifié par
`spl_object_id()` depuis plusieurs points d'entrée différents pendant F-01.
À utiliser pour toute requête SQL qui ne passe pas (encore, ou par choix
délibéré) par le query builder — jamais `new PDO(...)`, qui ouvrirait une
connexion MySQL séparée, invisible à toute transaction ouverte via
`Model::beginTransaction()`.

`DBAL::getEntityManager()` (Doctrine) reste une connexion différente,
volontairement : elle ne sert qu'aux métadonnées/migrations (§1), jamais à
une requête métier — ne pas y aller chercher une connexion à partager.

---

## 9. Mode debug

```php
QueryBuilder::$debug = true;

$result = Agency::where('this.status', '=', 'active')->get();
// $result n'est PAS le résultat de la requête : c'est
// ['query' => "... valeurs inline (lisible) ...",
//  'sql'   => "... avec les :placeholders ...",
//  'parameters' => ['this_status' => 'active', ...]]

QueryBuilder::$debug = false;   // ne pas oublier de le remettre a false
```

Utile pour vérifier une requête complexe (jointures, `whereGroup()`) avant
de l'exécuter réellement, ou pour confirmer qu'une valeur est bien **liée**
et non interpolée (comme fait en F-05b pour prouver que `whereLike()` était
devenue sûre — une valeur contenant une apostrophe reste dans `parameters`,
jamais dans le texte SQL).

`$debug` est une propriété **statique** de `QueryBuilder` — elle affecte
TOUT `QueryBuilder` construit tant qu'elle reste à `true`, pas seulement
l'instance courante. La remettre à `false` immédiatement après usage dans
un script/smoke test, jamais la laisser traîner.

---

## 10. Pièges et vulnérabilités connus

| # | Méthode | Statut | Détail |
|---|---|---|---|
| 1 | `whereLike()`/`like()`/`_like()`/`like_()` | **corrigé F-05b** | interpolaient `$value` directement dans le SQL — injection SQL. Paramétrées depuis, même signature. |
| 2 | `whereIn()` (valeurs string) | **non corrigé** | `qb_sanitize()` (`dclass/dvups_functions.php`) enveloppe une chaîne entre quotes SANS l'échapper — un tableau de valeurs venant d'un utilisateur reste injectable. Trouvé en F-05b, hors périmètre de cette tâche. |
| 3 | `whereBetween()` | **non corrigé** | même interpolation directe que l'ancien `whereLike()`, jamais revisitée. |
| 4 | `where($col, $operateur_inconnu, $val)` | comportement de framework, pas un bug isolé | un opérateur absent de la liste `$operators` (§3) ignore silencieusement `$val` et lie l'opérateur lui-même comme paramètre. Toujours vérifier qu'un opérateur est dans la liste avant de l'utiliser. |
| 5 | Relations ManyToOne | **documenté, pas un bug** | hydratation paresseuse par défaut (§4) - `with()` requis pour les colonnes au-delà de l'id. |
| 6 | `getInstance()`/`first()`/`find($id)` sur 0 résultat | **documenté, pas un bug** | renvoient une entité VIDE, jamais `null` - toujours `if ($x && $x->getId())`, jamais `if ($x)` seul (§5). |
| 7 | `whereRaw()` / `where_str()` | duplication, pas un bug | deux méthodes strictement identiques (même corps). Laissées telles quelles en F-05b (renommer/fusionner casserait des appelants existants pour un gain cosmétique). |
| 8 | Connexion PDO | **corrigé F-01/F-03** | avant, une connexion différente par entité/par contrôleur - voir §1/§8. |

**Règle pratique qui découle de 1-3** : toute valeur venant d'une requête
HTTP (paramètre GET/POST, en-tête) qui doit entrer dans un `whereIn()` ou
un `whereBetween()` doit être validée/castée AVANT (ex. `(int)`, ou
vérifiée contre une liste blanche) plutôt que passée telle quelle - le
query builder ne le fait pas à sa place sur ces deux méthodes précises,
contrairement à `where()`/`whereLike()`/`whereGroup()`.

---

## 11. Cas d'usage prêts à copier

### Lecture par id, avec gestion explicite du « non trouvé »

```php
$agency = Agency::findOrNull($id);
if (!$agency) {
    return ['success' => false, 'detail' => 'agence introuvable'];
}
```

### Liste filtrée, triée

```php
$activeAgencies = Agency::where('this.status', '=', 'active')
    ->orderBy('this.label')
    ->get();
```

### Recherche multi-champs en OR (motif `AgencyFrontController::directory()`)

```php
public function directory(string $q = ''): array
{
    $qb = Agency::where('this.status', '=', 'active');

    $q = trim($q);
    if ($q !== '') {
        $qb->whereGroup(function ($sub) use ($q) {
            $sub->whereLike('label', $q)
                ->orWhereLike('city', $q)
                ->orWhereLike('address', $q);
        });
    }

    $agencies = $qb->orderBy('this.label')->get();

    // Mise en forme explicite : garder le contrat de reponse API figé,
    // jamais un jsonSerialize() de l'entite qui exposerait toutes ses
    // colonnes (y compris celles jamais documentees comme publiques).
    return ['success' => true, 'agencies' => array_map(fn($a) => [
        'id' => $a->getId(), 'code' => $a->code, 'label' => $a->label,
        'address' => $a->address, 'city' => $a->city, 'phone' => $a->phone,
    ], $agencies)];
}
```

### Jointure + eager load (motif `AgencyFrontController::publicDetail()`)

```php
$offeredServices = Agency_service::where('this.agency_id', '=', $id)
    ->where('this.active', '=', 1)
    ->with('service', ['code', 'label', 'icon'])
    ->orderBy('service.position')
    ->get();

foreach ($offeredServices as $row) {
    // $row->service->label EST hydraté ici grace au with() ci-dessus
    echo $row->service->code . ' : ' . $row->service->label;
}
```

### Vérifier l'existence avant une écriture (contrainte unique)

```php
// DBAL::executeDbal() n'est jamais rattrapable par un try/catch de
// controleur (CLAUDE.md) - toujours verifier AVANT d'ecrire, pas apres.
$existing = Agency::where('this.code', '=', $code)->firstOrNull();
if ($existing && $existing->getId()) {
    return ['success' => false, 'detail' => "code deja utilise"];
}
```

### Compter / agréger

```php
$total = Ticket::where('this.agency_id', '=', $agencyId)
    ->where('this.status', '=', 'waiting')
    ->count();
```

### Écriture transactionnelle multi-tables (motif `issueForKiosk()`)

```php
Model::transaction(function () use ($agencyId, $serviceId, ...) {
    $ticketId = Ticket::create([...]);
    Ticket_event::create(['ticket_id' => $ticketId, ...]);
    (new Print_jobFrontController())->enqueue(...);
    return $ticketId;
});
```

### Prévisualiser une requête complexe avant de l'exécuter

```php
QueryBuilder::$debug = true;
$preview = Agency::where('this.status', '=', 'active')
    ->whereGroup(fn($qb) => $qb->whereLike('label', $q)->orWhereLike('city', $q))
    ->get();
QueryBuilder::$debug = false;

// $preview['sql'] : SQL avec :placeholders
// $preview['parameters'] : valeurs réellement liées
```

### Listing paginé pour l'API générique (`GET /api/agency`)

```php
public function index()
{
    $qb = Agency::initQb();
    return $qb->lazyloading();
}
// gère automatiquement ?dfilters=on&col:op=val, ?next=&per_page=
// (voir CLAUDE.md §3.4 pour les règles d'appel côté client - non répétées ici)
```
