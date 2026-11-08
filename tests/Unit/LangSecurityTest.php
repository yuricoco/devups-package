<?php

namespace Devups\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ETAPE H (securite langue) : dv_valid_lang($iso) est la SEULE porte
 * d'entree pour une langue venant de l'utilisateur. Elle doit rejeter
 * (retourner null) tout ce qui n'est pas exactement deux lettres
 * minuscules appartenant a une langue ACTIVE - en particulier toute
 * tentative de traversee de repertoire ou d'injection de balise, qui ne
 * doit jamais atteindre LANG_DIR . $iso . '.php'.
 */
final class LangSecurityTest extends TestCase
{
    public static function invalidLangsProvider(): array
    {
        return [
            'traversee simple' => ['../config/constante'],
            'traversee encodee (deja decodee par le serveur HTTP)' => ['../../cron'],
            'traversee profonde' => ['../../../../etc/passwd'],
            'balise script' => ['<script>alert(1)</script>'],
            'injection sql grossiere' => ["fr' OR '1'='1"],
            'majuscules' => ['FR'],
            'une seule lettre' => ['f'],
            'trois lettres' => ['fra'],
            'chiffres' => ['f1'],
            'chaine vide' => [''],
            'espace' => [' '],
            'point seul' => ['.'],
            'slash seul' => ['/'],
            'nul encode' => ["fr\0"],
            'tableau au lieu d une chaine' => [['fr']],
            'booleen' => [false],
            'entier' => [42],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider invalidLangsProvider
     */
    public function testRejectsAnythingThatIsNotTwoLowercaseLettersOrNotActive($iso): void
    {
        $this->assertNull(dv_valid_lang($iso), 'dv_valid_lang() doit renvoyer null pour : ' . var_export($iso, true));
    }

    public function testAcceptsAnActiveTwoLetterLanguage(): void
    {
        // fr/en sont actives dans le seed de test (config_data.sql).
        $this->assertSame('fr', dv_valid_lang('fr'));
        $this->assertSame('en', dv_valid_lang('en'));
    }

    public function testRejectsAWellFormedButInactiveOrUnknownLanguage(): void
    {
        // deux lettres valides selon le format, mais aucune langue 'zz'
        // n'existe dans dvups_lang : doit rester rejete malgre le format
        // correct - preuve que le controle "langue active" joue bien un
        // role, pas seulement la regex.
        $this->assertNull(dv_valid_lang('zz'));
    }

    public function testLangFilePathStaysInsideLangDir(): void
    {
        $this->assertSame(LANG_DIR . 'fr.php', dv_lang_file_path('fr'));
        $this->assertSame(LANG_DIR . 'en.php', dv_lang_file_path('en'));
    }

    public function testLangFilePathRejectsATraversalEvenIfPassedDirectly(): void
    {
        // appel direct de dv_lang_file_path() (sans passer par
        // dv_valid_lang()) avec une valeur qui contient un '..' : le
        // controle realpath doit refuser, par securite "en plus" (le
        // format de dv_valid_lang() l'empecherait deja en amont).
        $this->assertNull(dv_lang_file_path('../config/constante'));
        $this->assertNull(dv_lang_file_path('../../cron'));
    }

    public function testLocalFallsBackToTheDefaultLanguageWhenTheRequestedLangIsAPathTraversalAttempt(): void
    {
        \Request::set('lang', '../../../../cron');
        $this->assertSame(__lang, local(), 'une tentative de traversee doit retomber sur __lang, jamais etre utilisee telle quelle');
        unset(\Request::$uri_get_param['lang']);
    }

    public function testLocalFallsBackToTheDefaultLanguageWhenTheRequestedLangIsAScriptTag(): void
    {
        \Request::set('lang', '<script>alert(1)</script>');
        $this->assertSame(__lang, local());
        unset(\Request::$uri_get_param['lang']);
    }

    public function testLocalAcceptsAValidActiveLanguageFromTheRequest(): void
    {
        \Request::set('lang', 'en');
        $this->assertSame('en', local());
        unset(\Request::$uri_get_param['lang']);
    }

    public function testSetlangNeverStoresAnInvalidValueInSession(): void
    {
        setlang('../../../../cron');
        $this->assertSame(__lang, $_SESSION[LANG]);

        setlang('en');
        $this->assertSame('en', $_SESSION[LANG]);

        // remet la session dans l'etat attendu par le reste de la suite.
        setlang(__lang);
    }

    public function testTLangFallsBackToTheDefaultLanguageForAnInvalidIso(): void
    {
        $cle = 'cle_test_t_lang_securite_' . uniqid();
        // '../../../../cron' echoue au typehint `string` de t_lang() ?
        // non : string accepte n'importe quelle chaine, seule
        // dv_valid_lang() a l'interieur la rejette - on verifie donc
        // juste qu'aucune erreur ne survient et que la cle retombe sur
        // elle-meme (comportement de repli normal de t_lang() pour une
        // cle absente de la langue par defaut).
        $this->assertSame($cle, t_lang($cle, '../../../../cron'));

        // t_lang() met en cache les traductions de __default_lang en
        // memoire pour le reste du PROCESSUS (performance - un seul require
        // par iso et par requete) : sans ce nettoyage, ce test figerait le
        // contenu actuel de lang/<__default_lang>.php pour le reste de la
        // suite, cassant tout test ulterieur qui modifie ce fichier sur le
        // disque et s'attend a ce que t_lang() le relise (ex. LangTest).
        global $dv_translations_by_lang;
        unset($dv_translations_by_lang[__default_lang]);
    }
}
