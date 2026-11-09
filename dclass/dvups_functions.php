<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

global $__lang;
if(!isset($_SESSION[LANG]))
    $_SESSION[LANG] = __lang;

//if(!isset($_SESSION[PREVIOUSPAGE]))
//    $_SESSION[PREVIOUSPAGE] = $_SERVER["url"];
//elseif ($_SESSION[PREVIOUSPAGE] != $_SERVER["url"])
//    $_SESSION[PREVIOUSPAGE] = $_SERVER["url"];

/**
 * ETAPE H (securite langue) : SEULE porte d'entree pour valider un code
 * langue venant de l'utilisateur (parametre ?lang, session, cookie...)
 * avant de l'utiliser pour construire un chemin de fichier
 * (LANG_DIR . $iso . '.php') ou de l'exposer dans une URL generee
 * (d_url()). N'accepte que deux lettres minuscules (^[a-z]{2}$ - exclut
 * par construction tout '/', '.', balise ou espace : aucune traversee de
 * repertoire ni injection n'est possible avec un tel format) ET une
 * langue ACTIVE (cache de Dvups_lang::cachedActiveDlangs(), jamais une
 * requete SQL directe ici). Retourne null si invalide - jamais la valeur
 * d'entree telle quelle.
 */
function dv_valid_lang($iso): ?string
{
    if (!is_string($iso) || !preg_match('/^[a-z]{2}$/', $iso))
        return null;

    try {
        $active = \Dvups_lang::cachedActiveDlangs();
    } catch (\Throwable $e) {
        // base indisponible ou pas encore migree (outillage CLI, amorcage
        // des tests avant creation du schema...) : on refuse plutot que
        // de planter - jamais de repli permissif sur le seul format.
        return null;
    }

    return isset($active[$iso]) ? $iso : null;
}

/**
 * Chemin du fichier de traduction pour $iso, UNIQUEMENT s'il reste a
 * l'interieur de LANG_DIR une fois resolu (realpath) : defense en
 * profondeur en plus du format verifie par dv_valid_lang() - $iso ne
 * devrait deja plus jamais pouvoir faire sortir le chemin de LANG_DIR,
 * mais ce controle supplementaire est gratuit et couvre un eventuel appel
 * direct avec un $iso non passe par dv_valid_lang(). Retourne null si le
 * dossier resultant n'est pas LANG_DIR lui-meme (ou si LANG_DIR est
 * introuvable).
 */
function dv_lang_file_path(string $iso): ?string
{
    $dir = realpath(LANG_DIR);
    if ($dir === false)
        return null;

    $path = LANG_DIR . $iso . '.php';
    // dirname() plutot que realpath($path) : le fichier peut ne pas
    // encore exister (nouvelle langue jamais sauvegardee sur le disque).
    if (realpath(dirname($path)) !== $dir)
        return null;

    return $path;
}

function local() {
    if ($lang = dv_valid_lang(Request::get('lang')))
        return $lang;
    if (isset($_SESSION[LANG]) && ($lang = dv_valid_lang($_SESSION[LANG])))
        return $lang;

    return __lang;
}

function setlang($lang) {
    $_SESSION[LANG] = dv_valid_lang($lang) ?? __lang;
}

function redirect($url = "", $admin = false){
    header('location: '. $url );
    die;
}

if(!isset($_SESSION["__lang"] )){
    $_SESSION["__lang"] = "fr";
}


function d_assets($src){
    return __env .'web/assets/' . $src;
}

function path($src){
    return __env . $src;
}

function services($service){
    return __env ."admin/api/". $service;
}

function url_format($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
    $str = str_replace(' ', '-', $str); // supprime les autres caractères
    $str = str_replace('+', '', $str); // supprime les autres caractères

    return strtolower($str);
}

function remove_accents($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
//    $str = str_replace('\'', '.', $str); // supprime les autres caractères
//    $str = str_replace('"', '.', $str); // supprime les autres caractères

    return strtolower($str);
}

function clean($string) {
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}
/**
 * @return \Dvups_admin Description
 */
function getadmin() {

    if(isset($_SESSION[ADMIN]))
        return unserialize($_SESSION[ADMIN]);

    return new Dvups_admin();
}

function setadmin($id) {

    $admin = Dvups_admin::find($id);
    $_SESSION[ADMIN] = serialize($admin);
    return $admin;

}

/**
 * API unique d'autorisation : can('update', Dvups_role::class) ou
 * can('export', 'Dvups_role'). Utilisee par les controleurs (CrudTrait),
 * les boutons des datatables (Datatable::actionListView()) et l'API REST
 * (memes controleurs). Le role "admin" passe toujours (bypass maitre,
 * coherent avec Dvups_role::updateprivilegeAction()) ; sans session admin,
 * refuse toujours.
 */
function can(string $action, string $entity): bool
{
    // Une page back-office appelle can() une fois par ligne de datatable (3
    // actions) en plus de la garde de Request::Controller() : sans ce cache,
    // chaque appel refaisait un hydrate() SQL du role. Cle sur l'id de
    // l'admin (stable) plutot que sur $_SESSION[ADMIN] (le blob serialise) :
    // reste correct si la session change pendant le process (vrai en tests,
    // qui appellent can() avec plusieurs sessions successives dans le meme
    // process PHP - jamais le cas en prod, une requete = un process).
    static $roleOfSession = [];
    static $rightsByRole = [];

    if (empty($_SESSION[ADMIN]))
        return false;

    $sessionAdmin = getadmin();
    if (!$sessionAdmin || !$sessionAdmin->getId())
        return false;

    $adminId = $sessionAdmin->getId();
    if (!isset($roleOfSession[$adminId])) {
        // $_SESSION[ADMIN] est un snapshot serialise a la connexion : son
        // dvups_role reste celui d'alors meme si le role de cet admin
        // change ensuite en base. On relit l'admin courant (1 requete,
        // mise en cache pour le reste du process) pour qu'un changement de
        // role soit pris en compte sans reconnexion.
        $admin = \Dvups_admin::find($adminId);
        $role = ($admin && $admin->getId()) ? $admin->dvups_role : null;
        $entry = ['id' => null, 'isAdmin' => false];
        if ($role && $role->getId()) {
            $role = $role->hydrate();
            $entry = ['id' => $role->getId(), 'isAdmin' => $role->is('admin')];
        }
        $roleOfSession[$adminId] = $entry;
    }

    $role = $roleOfSession[$adminId];
    if ($role['id'] === null)
        return false;
    if ($role['isAdmin'])
        return true;

    $pos = strrpos($entity, '\\');
    if ($pos !== false)
        $entity = substr($entity, $pos + 1);

    if (!array_key_exists($role['id'], $rightsByRole))
        $rightsByRole[$role['id']] = Dvups_role::compiledRights($role['id']);

    return !empty($rightsByRole[$role['id']][$entity][$action]);
}

function d_url($path, $id = "", $title = "") {
    global $__lang;

    if(Request::$system != "admin") {
        $lang = local();
        $path = $lang . "/" . $path;
    }
    if ($id) {
        $path .= "/" . $id;
    }

    if ($title) {
        $path .= url_format($title);
    }

    $mode = "";
    if (Request::get('mode')) {
        $mode = "?mode=" . Request::get('mode');
    }

    return __env . $path . $mode;
}

function route($path, $id = "", $title = "") {
    return d_url($path, $id , $title);
}

function dv_dump(... $args){
    dump($args);
    die(1);
}

function randomtoken(){
    $randomtoken = base64_encode( openssl_random_pseudo_bytes(32));
}

/**
 * @param $value used in the querybuilder to sanitize select where in constraint
 * @return mixed|string
 */
/**
 * @deprecated ETAPE B.4 (v7.1) : entourait une chaine de guillemets SANS
 * echapper les guillemets qu'elle contiendrait deja ("'$value'") -
 * injection SQL pour toute valeur utilisateur avec une apostrophe. Plus
 * aucun usage interne (QueryBuilder lie desormais ses valeurs en
 * parametres - whereIn()/in()/notIn()/whereBetween()/between()). Gardee
 * pour compatibilite d'un appelant externe eventuel, mais echappe
 * desormais via PDO::quote() (toujours correct, jamais une simple
 * concatenation de guillemets).
 */
function qb_sanitize($value){
    if(is_string($value))
        return \Model::pdo()->quote($value);

    return $value;
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

// langt() retiree (Phase 3, Langues) : remplacee par t_lang($cle, $iso, $params)
// (lang.php), qui lit lang/<iso>.php au lieu de paires fr/en codees en dur.

/**
 * ETAPE K.1 (Phase 7, Mots de passe) : hash d'un mot de passe EN CLAIR pour
 * stockage. A utiliser partout ou un mot de passe est defini/modifie
 * (creation, reset, changement) - plus jamais sha1()/md5() pour un secret.
 */
function dv_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifie un mot de passe EN CLAIR contre un hash stocke en base.
 *
 * Accepte aussi l'ancien format sha1() (40 caracteres hexadecimaux) pour
 * permettre une migration TRANSPARENTE au prochain login reussi, sans
 * reinitialisation forcee : $upgraded (parametre par reference) est mis a
 * true si et seulement si le hash fourni est l'ancien format ET que le mot
 * de passe est correct - l'appelant doit alors remplacer le hash stocke par
 * dv_hash_password($password) et sauvegarder l'entite (voir
 * Dvups_admin::verifyPassword()/UserCore::verifyPassword()).
 */
function dv_verify_password(string $password, ?string $hash, bool &$upgraded = false): bool
{
    $upgraded = false;
    if ($password === '' || !$hash)
        return false;

    $info = password_get_info($hash);
    if ($info['algoName'] !== 'unknown')
        return password_verify($password, $hash);

    if (preg_match('/^[a-f0-9]{40}$/i', $hash) && hash_equals($hash, sha1($password))) {
        $upgraded = true;
        return true;
    }

    return false;
}
// Voir web/views/mails/{invitation,otp,welcome}.blade.php.