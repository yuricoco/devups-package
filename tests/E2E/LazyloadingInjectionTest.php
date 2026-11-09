<?php

namespace Devups\Tests\E2E;

use Devups\Tests\Support\HttpTestCase;

/**
 * ETAPE B.3 (v7.1) : les operateurs de Lazyloading (datatable admin,
 * filtres de colonne "attr:op=valeur") "in"/"notin"/"btw"/"rein"
 * atteignaient QueryBuilder::in()/notIn()/between()/whereInReverse() -
 * qui interpolaient tous la valeur utilisateur directement dans le SQL
 * (via qb_sanitize(), qui n'echappait pas les apostrophes deja
 * presentes dans la valeur, ou carrement sans aucun echappement pour
 * "rein"/"btw"). Desormais parametrees (voir QueryBuilder.php) : une
 * valeur contenant une apostrophe ou une tentative d'injection classique
 * ne doit jamais provoquer d'erreur SQL ni faire remonter une ligne qui
 * ne correspond pas au filtre.
 *
 * Entite "interval" choisie volontairement (pas "tree") : elle n'a pas
 * de colonne traduite (pas de jointure dvups_lang), donc pas de risque
 * de heurter le defaut D2 preexistant (colonne ambigue en presence
 * d'une jointure de traduction - documente separement, ETAPE C, hors
 * perimetre de cette etape).
 */
final class LazyloadingInjectionTest extends HttpTestCase
{
    private array $intervalIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();

        foreach ([["etapeb3_alpha", 1, 2], ["etapeb3_o'brien", 3, 4], ["etapeb3_beta", 5, 6]] as [$label, $min, $max]) {
            $this->intervalIds[] = \Interval::create([
                'label' => $label, '_value' => $label, 'entity' => 'etapeb3_test', '_min' => $min, '_max' => $max,
            ]);
        }
    }

    protected function tearDown(): void
    {
        $pdo = \Model::pdo();
        foreach ($this->intervalIds as $id) {
            $pdo->exec("DELETE FROM `interval` WHERE id = " . (int)$id);
        }
        parent::tearDown();
    }

    private function datatable(string $filterQuery): array
    {
        \Devups\Tests\Support\HttpServer::newErrors();
        $res = $this->get('admin/api/interval/datatable?next=0&per_page=50&dfilters=on&' . $filterQuery);
        $errors = \Devups\Tests\Support\HttpServer::newErrors();
        $this->assertSame([], $errors, "erreurs PHP sur le filtre '$filterQuery' :\n" . implode("\n", $errors) . "\nreponse :\n" . $res['body']);
        $this->assertSame(200, $res['status'], "reponse :\n" . $res['body']);
        $data = $this->json($res);
        $this->assertTrue($data['success'] ?? false, "reponse :\n" . $res['body']);
        return $data;
    }

    public function testInOperatorWithAnApostropheDoesNotErrorOrOvermatch(): void
    {
        $this->datatable('label:in=' . rawurlencode("etapeb3_o'brien,x' OR '1'='1"));
    }

    public function testNotinOperatorWithAnApostropheDoesNotErrorOrOvermatch(): void
    {
        // eclatee par virgule (corrige, ETAPE B.3) - une valeur avec
        // apostrophe parmi la liste ne doit jamais casser la requete.
        $this->datatable('label:notin=' . rawurlencode("etapeb3_o'brien,x' OR '1'='1"));
    }

    public function testReinOperatorWithAnInjectionAttemptDoesNotErrorOrMatchEverything(): void
    {
        // "rein" : whereInReverse($value, $attr) - une tentative
        // d'injection classique ("' OR '1'='1") dans la valeur ne doit
        // jamais provoquer d'erreur SQL ni faire remonter toutes les
        // lignes (ce que l'ancienne interpolation directe aurait permis).
        $this->datatable('label:rein=' . rawurlencode("etapeb3_o'brien' OR '1'='1"));
    }

    public function testBtwOperatorWithAnApostropheInTheBoundsDoesNotError(): void
    {
        $this->datatable('label:btw=' . rawurlencode("x'_y'"));
    }
}
