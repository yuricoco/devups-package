<?php

namespace Devups\Tests\Integration;

use Devups\Tests\Support\DatabaseTestCase;

/**
 * ETAPE F.2 (v7.1) : deux bugs du framework trouves en executant la suite
 * complete sous sql_mode strict (celui de MySQL 8/la CI), au-dela des
 * causes A/B initialement rapportees - tous deux invisibles sous MariaDB
 * (sql_mode tolerant, utilise en local avant ce chantier).
 */
final class StrictModeFrameworkInsertUpdateTest extends DatabaseTestCase
{
    /**
     * DBAL::_createDbal() (Model::create(), chemin statique) transmettait
     * tel quel '' pour une colonne entiere (ex. Country::$phonecode) :
     * MySQL non strict convertissait silencieusement en 0, sql_mode strict
     * refuse avec l'erreur 1366 "Incorrect integer value".
     */
    public function testCreateCoercesAnEmptyStringToZeroForANumericColumn(): void
    {
        $id = \Country::create([
            'name' => 'Phpunit strict test',
            'iso' => 'ZZ',
            'iso3' => 'ZZZ',
            'numcode' => '999',
            'phonecode' => '',
            'status' => '',
        ]);

        $country = \Country::find($id);
        $this->assertSame(0, (int)$country->getPhonecode());
    }

    /**
     * ModelTrait::entityKeyUpdate() : une entite construite par id seul
     * (new Entite($id), jamais hydratee - le chemin utilise par
     * CrudTrait::updateAction() et Dvups_adminController::updateAction()
     * pour toute mise a jour partielle) incluait dans l'UPDATE TOUTES les
     * colonnes mappees, y compris celles jamais renseignees par
     * l'appelant (valeur PHP par defaut null) - ecrasant silencieusement
     * en base une colonne NOT NULL comme `login` des qu'un formulaire ne
     * la soumettait pas. MySQL non strict substituait une valeur par
     * defaut implicite (avec un warning), sql_mode strict refuse avec
     * l'erreur 1048 "Column 'login' cannot be null".
     */
    public function testUpdateOfUnhydratedEntityDoesNotNullOtherNotNullColumns(): void
    {
        $roleId = \Dvups_role::create(['name' => 'phpunit-strict-role' . uniqid(), 'alias' => 'x']);
        $login = 'phpunit-strict' . uniqid();
        $id = \Dvups_admin::create([
            'name' => 'avant', 'login' => $login, 'password' => sha1('secret'),
            'firstconnexion' => 0, 'dvups_role_id' => $roleId,
        ]);

        $admin = new \Dvups_admin($id);
        $admin->setName('apres');
        $admin->__update();

        $reloaded = \Dvups_admin::find($id);
        $this->assertSame('apres', $reloaded->getName());
        $this->assertSame($login, $reloaded->getLogin());
    }
}
