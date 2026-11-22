---
title: "Chapitre 5 — Écrire"
sidebar_position: 4
---

## 5.1 Les formes

```php
// création
$id  = Task::create(['name' => 'x', 'project_id' => 3]);       // un id
$ids = Task::create(['name' => 'a'], ['name' => 'b']);          // plusieurs lignes
$t   = Task::createInstance([...]);                              // l'instance

// mise à jour
$task = Task::find($id);
$task->name = 'nouveau nom';    // __set magique, même sur une propriété protected
$task->__update();              // UPDATE des colonnes modifiées
$task->__update(['name' => 'x']);   // UPDATE ciblé
$task->__save();                // insert ou update selon la présence d'un id

// bas niveau
$task->__insert();
$task->__delete();              // DELETE, ou suppression douce si dvsoftdelete
$task->__dclone($update);       // duplique la ligne

// statiques
Task::update($valeurs, $clé, $where, $classname);
Task::delete($id, $force = false);
```

Il n'y a pas de `setXxx()` fluide généré : l'affectation directe est le mécanisme. Un `setXxx()` écrit à la main a un autre rôle : il est appelé par le traitement de formulaire (§5.4).

## 5.2 Un piège d'écriture

**`Entity::create()` attend le nom de colonne physique pour une relation.**

```php
Task::create(['project_id' => 3]);   // correct
Task::create(['project' => 3]);      // échoue : "Unknown column 'project'"
```

La forme `'project.id' => 3` fonctionne dans la charge utile d'API (Tome III), pas dans `create()`.

## 5.3 Les transactions

```php
Model::beginTransaction();   // niveau 0 : vraie transaction PDO ; niveau > 0 : SAVEPOINT
try {
    $task->__insert();
    $history->__insert();
    Model::commit();          // niveau 0 : COMMIT réel ; niveau > 0 : libère le SAVEPOINT
} catch (\Throwable $e) {
    Model::rollBack();        // niveau 0 : ROLLBACK ; niveau > 0 : ROLLBACK TO SAVEPOINT
    throw $e;
}
```

La forme recommandée protège contre un `rollBack()` oublié sur un retour anticipé :

```php
$taskId = Model::transaction(function () use ($data) {
    $taskId = Task::create($data);
    Task_history::create(['task_id' => $taskId, 'event' => 'created']);
    return $taskId;
});
```

L'imbrication est transparente : deux méthodes qui appellent chacune `Model::transaction()` peuvent s'appeler l'une l'autre sans lever le « There is already an active transaction » de PDO. `Model::transactionDepth()` donne le niveau courant.

:::warning Piège
**Contrepartie de la connexion unique.** Une transaction laissée ouverte empoisonne tout ce qui suit dans la même requête HTTP. Toujours `Model::transaction()`, ou un `try/catch` avec `rollBack()` garanti.
:::

## 5.4 Les accesseurs et le traitement de formulaire

Un `setXxx()` écrit à la main est un point de validation appelé par `Controller::formWithPost()`. Son contrat de retour est particulier : il n'y a aucun `try/catch` autour, et sa valeur de retour, si elle n'est pas vide, est traitée comme un message d'erreur.

```php
public function setCode($code)
{
    $code = strtoupper(trim($code));
    if (!preg_match('/^[A-Z0-9_]{2,20}$/', $code)) {
        return t('Le code doit faire 2 à 20 caractères : lettres, chiffres, _');
    }
    $this->code = $code;
    return null;   // pas d'erreur
}
```

Un setter de validation retourne une chaîne d'erreur, il ne lève pas d'exception. Conséquence agréable : exposer un nouveau champ validé ne demande aucune modification du contrôleur.

## 5.5 Sérialisation

`ModelSerializer::jsonSerialize()` remonte `$this->dvserialised` plus, pour chaque clé de `$this->with`, la sérialisation de la relation correspondante. Une entité surcharge en ajoutant, puis en déléguant (§2.2).

:::note À retenir
**Pour une réponse d'API, préférer une mise en forme explicite.** Sérialiser l'entité expose toutes ses colonnes, y compris celles qui n'ont jamais été documentées comme publiques, et fige le contrat de réponse sur la structure de la table. `User` montre la forme à suivre : `restrictToPublicProfile()` retire l'e-mail et le téléphone quand le demandeur n'est pas le propriétaire (Tome III, §8.6).
:::
