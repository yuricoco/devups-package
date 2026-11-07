<?php
if (!defined('PROJECT_NAME') && (getenv('DEVUPS_CONSTANTS') || file_exists(__DIR__ . '/../config/constante.php')))
    require_once __DIR__ . '/../config/load_constants.php';
if (!defined('PROJECT_NAME'))
    define('PROJECT_NAME', 'Devups');

/**
 * Guide Développeur — documentation combinée DataCore + API FrontControllers
 *
 * Parsing 100 % statique (aucune classe PHP chargée).
 * ?format=json → JSON brut  |  (défaut) → UI interactive Stripe-style
 *
 * Annotations personnalisées reconnues dans les FrontControllers :
 *   @query  name type  Description du paramètre de query string
 *   @body   name type  Description du champ de body JSON
 *   @response {"success":true,...}   Exemple de réponse JSON
 */

// ─── Helpers ──────────────────────────────────────────────────────────────────

function gParseDoc(string $doc): array
{
  $r = ['summary' => '', 'method' => null, 'subpath' => null, 'auth' => false, 'api' => null, 'apiDescription' => null, 'query' => [], 'body' => [], 'response' => null];

  if (preg_match('/@(GET|POST|PUT|DELETE)(?:\s*\(([^)]*)\))?/i', $doc, $m)) {
    $r['method'] = strtolower($m[1]);
    $attrs = $m[2] ?? '';
    if (preg_match('/path=[\'"]([^\'"]*)[\'"]/',        $attrs, $pm)) $r['subpath'] = $pm[1];
    if (preg_match('/description=[\'"]([^\'"]+)[\'"]/', $attrs, $dm)) $r['summary'] = $dm[1];
  }
  if (preg_match('/@Api\s*\(([^)]+)\)/i', $doc, $m)) {
    $attrs = $m[1];
    if (preg_match('/name=[\'"]([^\'"]+)[\'"]/i',        $attrs, $nm)) $r['api']            = $nm[1];
    if (preg_match('/description=[\'"]([^\'"]+)[\'"]/i', $attrs, $dm)) $r['apiDescription'] = $dm[1];
  }
  if (preg_match('/@Auth\s*\(\s*authorized\s*=\s*1\s*\)/i', $doc))           $r['auth'] = true;

  preg_match_all('/@query\s+(\S+)\s+(\S+)\s+(.*)/im',  $doc, $qm, PREG_SET_ORDER);
  foreach ($qm as $q) $r['query'][] = ['name' => $q[1], 'type' => $q[2], 'description' => trim($q[3])];

  preg_match_all('/@body\s+(\S+)\s+(\S+)\s+(.*)/im',  $doc, $bm, PREG_SET_ORDER);
  foreach ($bm as $b) $r['body'][] = ['name' => $b[1], 'type' => $b[2], 'description' => trim($b[3])];

  if (preg_match('/@response\s+(\{[^@]*\})/s', $doc, $rm)) $r['response'] = trim($rm[1]);

  foreach (explode("\n", $doc) as $line) {
    $l = trim(ltrim($line, " \t/*"));
    if ($l && $l[0] !== '@') {
      $r['summary'] = $l;
      break;
    }
  }
  return $r;
}

function gToOaPath(string $p): string
{
  return preg_replace('/\/:([a-zA-Z_]\w*)/', '/{$1}', $p);
}

function gEntityExample(array $fields): array
{
  $ex = [];
  foreach ($fields as $f) {
    if ($f['auto'] ?? false) {
      $ex[$f['name']] = $f['name'] === 'id' ? 1 : null;
      continue;
    }
    $ex[$f['name']] = match ($f['datatype']) {
      'integer', 'int' => 0,
      'boolean', 'bool' => false,
      'text'    => 'Lorem ipsum…',
      'date'    => '2026-01-01',
      'datetime' => '2026-01-01 00:00:00',
      'float', 'decimal' => 0.0,
      default   => '',
    };
  }
  return $ex;
}

// ─── Parse WebService ─────────────────────────────────────────────────────────

$wsContent = file_get_contents(__DIR__ . '/../route/WebService.php');
preg_match('/\$public_access\s*=\s*\[(.*?)\];/s', $wsContent, $pam);
preg_match_all("/['\"](\w+)['\"]\s*=>/", $pam[1] ?? '', $pk);
$publicKeys = $pk[1] ?? [];

preg_match_all("/Auth::addRestriction\(['\"](\w+)['\"],\s*\[\]/", $wsContent, $arm);
$fullAuthEntities = $arm[1] ?? [];

// ─── Standard filter params (lazyloading) ─────────────────────────────────────

$stdFilters = [
  ['name' => 'dfilters', 'type' => 'string',  'description' => 'Activer les filtres dynamiques (`on`)'],
  ['name' => 'per_page', 'type' => 'integer', 'description' => 'Résultats par page (défaut 30)'],
  ['name' => 'next',     'type' => 'integer', 'description' => 'Numéro de page (commence à 1)'],
  ['name' => 'dsort',    'type' => 'string',  'description' => 'Champ de tri'],
  ['name' => 'direction', 'type' => 'string',  'description' => 'ASC | DESC'],
];
$idParam = [['name' => 'id', 'type' => 'integer', 'description' => 'ID de la ressource']];

// ─── Parse FrontControllers ───────────────────────────────────────────────────

$fcMap   = [];
$fcFiles = glob(__DIR__ . '/../src/*/*/Controller/*FrontController.php');
sort($fcFiles);

foreach ($fcFiles as $fcFile) {
  $code = file_get_contents($fcFile);
  if (!preg_match('/class\s+(\w+FrontController)/i', $code, $cm)) continue;
  $fcName = $cm[1];

  preg_match('/\/\*\*(.*?)\*\/\s*class\s+' . preg_quote($fcName, '/') . '$/ms', $code, $cdm);
  $classAnnot = gParseDoc($cdm[0] ?? '');
  if (!($basePath = $classAnnot['api'])) continue;

  $entityKey = ltrim($basePath, '/');
  if (!in_array($entityKey, $publicKeys)) continue;

  $endpoints = [];
  preg_match_all('/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+)\s*\(([^)]*)\)/s', $code, $mms, PREG_SET_ORDER);

  foreach ($mms as $mm) {
    $a = gParseDoc('/**' . $mm[1] . '*/');
    if ($a['method'] === null) continue;

    $fullPath = gToOaPath($basePath . $a['subpath']);
    preg_match_all('/\{([a-zA-Z_]\w*)\}/', $fullPath, $ppm);
    $pathParams = array_map(fn($p) => ['name' => $p, 'type' => 'integer', 'description' => 'Identifiant'], $ppm[1]);

    $qParams = $a['query'];
    if ($mm[2] === 'index' && $a['method'] === 'get') {
      $qParams = array_merge($qParams, $stdFilters);
    }

    $endpoints[] = [
      'method'    => strtoupper($a['method']),
      'path'      => $fullPath,
      'name'      => $mm[2],
      'summary'   => $a['summary'] ?: ucfirst($mm[2]) . ' ' . $entityKey,
      'auth'      => $a['auth'] || in_array($entityKey, $fullAuthEntities),
      'query'     => $qParams,
      'pathParams' => $pathParams,
      'body'      => $a['body'],
      'response'  => $a['response'],
    ];
  }

  $fcMap[$entityKey] = ['type' => 'rest', 'fc' => $fcName, 'base' => $basePath, 'description' => $classAnnot['apiDescription'], 'endpoints' => $endpoints];
}

function gLegacyApi(string $ek, bool $fullAuth, array $idP, array $stdF): array
{
  return ['type' => 'legacy', 'fc' => null, 'base' => "/$ek", 'endpoints' => [
    ['method' => 'GET', 'path' => "/lazyloading/$ek", 'name' => 'list',  'summary' => "Lister $ek (paginé)", 'auth' => $fullAuth, 'query' => $stdF, 'pathParams' => [], 'body' => [], 'response' => null],
    ['method' => 'GET', 'path' => "/detail/$ek",     'name' => 'detail', 'summary' => "Détail d'un $ek",   'auth' => $fullAuth, 'query' => $idP, 'pathParams' => [], 'body' => [], 'response' => null],
    ['method' => 'POST', 'path' => "/create/$ek",     'name' => 'create', 'summary' => "Créer un $ek",       'auth' => true,     'query' => [], 'pathParams' => [], 'body' => [['name' => $ek, 'type' => 'object', 'description' => "Champs de l'entité"]], 'response' => null],
    ['method' => 'POST', 'path' => "/update/$ek",     'name' => 'update', 'summary' => "Modifier un $ek",    'auth' => true,     'query' => $idP, 'pathParams' => [], 'body' => [['name' => $ek, 'type' => 'object', 'description' => 'Champs à modifier']], 'response' => null],
    ['method' => 'GET', 'path' => "/delete/$ek",     'name' => 'delete', 'summary' => "Supprimer un $ek",   'auth' => true,     'query' => $idP, 'pathParams' => [], 'body' => [], 'response' => null],
  ]];
}

// ─── Parse DataCore ───────────────────────────────────────────────────────────

$srcDir     = __DIR__ . '/../src';
$components = [];

foreach (array_diff(scandir($srcDir), ['.', '..', 'requires.php']) as $compDir) {
  $compPath = "$srcDir/$compDir";
  if (!is_dir($compPath) || !file_exists("$compPath/{$compDir}Core.json")) continue;

  $component = ['name' => $compDir, 'modules' => []];

  foreach (array_diff(scandir($compPath), ['.', '..']) as $modDir) {
    $modPath = "$compPath/$modDir";
    if (!is_dir($modPath) || !file_exists("$modPath/" . strtolower($modDir) . "Core.json")) continue;

    $module   = ['name' => $modDir, 'entities' => []];
    $coreDir  = "$modPath/Core";
    if (!is_dir($coreDir)) continue;

    foreach (glob("$coreDir/*.json") as $coreFile) {
      $ec = json_decode(file_get_contents($coreFile), true);
      if (!$ec || !isset($ec['name'])) continue;
      $ek = strtolower($ec['name']);

      $fields = [['name' => 'id', 'datatype' => 'integer', 'nullable' => false, 'auto' => true, 'size' => null, 'formtype' => null]];
      foreach ($ec['attribut'] ?? [] as $attr) {
        $fields[] = ['name' => $attr['name'], 'datatype' => $attr['datatype'], 'size' => ($attr['size'] ?: null), 'nullable' => ($attr['nullable'] ?? '') !== 'not', 'formtype' => ($attr['formtype'] ?? null), 'auto' => false];
      }
      $fields[] = ['name' => 'created_at', 'datatype' => 'datetime', 'nullable' => true, 'auto' => true, 'size' => null, 'formtype' => null];
      $fields[] = ['name' => 'updated_at', 'datatype' => 'datetime', 'nullable' => true, 'auto' => true, 'size' => null, 'formtype' => null];

      $relations = [];
      foreach ($ec['relation'] ?? [] as $rel) {
        $relations[] = ['entity' => $rel['entity'], 'cardinality' => $rel['cardinality'], 'nullable' => ($rel['nullable'] ?? '') !== 'not', 'ondelete' => ($rel['ondelete'] ?? null)];
      }

      $api = $fcMap[$ek] ?? null;

      $module['entities'][] = [
        'name'      => ucfirst($ec['name']),
        'key'       => $ek,
        'public'    => in_array($ek, $publicKeys),
        'fields'    => $fields,
        'relations' => $relations,
        'api'       => $api,
        'example'   => gEntityExample($fields),
      ];
    }

    if ($module['entities']) $component['modules'][] = $module;
  }
  if ($component['modules']) $components[] = $component;
}

// ─── Services + Auth ─────────────────────────────────────────────────────────

$services = [];
preg_match_all('/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+Serve)\s*\(/s', $wsContent, $sms, PREG_SET_ORDER);
foreach ($sms as $sm) {
  $rk = preg_replace('/Serve$/', '', $sm[2]);
  if ($rk === 'system') continue;
  $a = gParseDoc('/**' . $sm[1] . '*/');
  $services[] = ['method' => 'GET', 'path' => "/$rk", 'name' => $rk, 'summary' => $a['summary'] ?: ucfirst($rk), 'auth' => false, 'query' => $a['query'], 'pathParams' => [], 'body' => $a['body'], 'response' => $a['response']];
}

$authEndpoints = [
  ['method' => 'POST', 'path' => '/user/googleAuth', 'name' => 'googleAuth', 'summary' => 'Connexion / inscription via Google OAuth', 'auth' => false, 'query' => [], 'pathParams' => [], 'body' => [['name' => 'idToken', 'type' => 'string', 'description' => 'ID Token fourni par Google Sign-In']], 'response' => '{"success":true,"jwt":"eyJhbGc...","user":{"id":1,"username":"..."},"isNewAccount":true}'],
  ['method' => 'POST', 'path' => '/user/appleAuth', 'name' => 'appleAuth', 'summary' => 'Connexion / inscription via Apple Sign-In', 'auth' => false, 'query' => [], 'pathParams' => [], 'body' => [['name' => 'identityToken', 'type' => 'string', 'description' => 'Identity Token Apple'], ['name' => 'user', 'type' => 'string', 'description' => 'Identifiant unique Apple (1er appel)']], 'response' => '{"success":true,"jwt":"eyJhbGc...","user":{"id":2,"username":"Apple User"},"isNewAccount":true}'],
];

$guideData = ['project' => PROJECT_NAME, 'components' => $components, 'services' => $services, 'auth' => $authEndpoints];

// ─── JSON mode ────────────────────────────────────────────────────────────────

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *');
  echo json_encode($guideData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

header('Content-Type: text/html; charset=utf-8');
$dataJson = json_encode($guideData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= PROJECT_NAME ?> – Guide Développeur</title>
  <style>
    :root {
      --bg: #0b1120;
      --bg2: #111827;
      --bg3: #1e293b;
      --border: #1e2d45;
      --text: #e2e8f0;
      --text2: #94a3b8;
      --text3: #64748b;
      --accent: #3b82f6;
      --green: #22c55e;
      --amber: #f59e0b;
      --purple: #8b5cf6;
      --red: #ef4444;
      --get: var(--green);
      --post: var(--amber);
      --put: var(--purple);
      --delete: var(--red);
      --patch: #06b6d4;
      --sidebar-w: 270px;
      --code-w: 380px
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 16px;
      background: var(--bg);
      color: var(--text);
      display: flex;
      height: 100vh;
      overflow: hidden
    }

    /* ── Sidebar ── */
    #sidebar {
      width: var(--sidebar-w);
      min-width: var(--sidebar-w);
      background: var(--bg2);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      overflow: hidden
    }

    #brand {
      padding: 16px 16px 12px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0
    }

    #brand h1 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: .3px
    }

    #brand p {
      font-size: 11px;
      color: var(--text3);
      margin-top: 2px
    }

    #search-wrap {
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0
    }

    #search {
      width: 100%;
      background: var(--bg3);
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 7px 10px;
      color: var(--text);
      font-size: 13px;
      outline: none
    }

    #search::placeholder {
      color: var(--text3)
    }

    #search:focus {
      border-color: var(--accent)
    }

    #nav-tree {
      flex: 1;
      overflow-y: auto;
      padding: 8px 0
    }

    #nav-tree::-webkit-scrollbar {
      width: 4px
    }

    #nav-tree::-webkit-scrollbar-thumb {
      background: var(--bg3);
      border-radius: 2px
    }

    /* Nav items */
    .nav-comp-head {
      padding: 8px 14px 4px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .8px;
      text-transform: uppercase;
      color: var(--text3);
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      gap: 6px
    }

    .nav-comp-head:hover {
      color: var(--text2)
    }

    .nav-comp-head .toggle {
      margin-left: auto;
      transition: transform .15s;
      display: inline-block
    }

    .nav-comp-head.open .toggle {
      transform: rotate(90deg)
    }

    .nav-mod-head {
      padding: 5px 14px 5px 20px;
      font-size: 11px;
      font-weight: 600;
      color: var(--text2);
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      gap: 5px
    }

    .nav-mod-head:hover {
      color: var(--text)
    }

    .nav-mod-head .toggle {
      margin-left: auto;
      transition: transform .15s;
      display: inline-block;
      font-size: 10px
    }

    .nav-mod-head.open .toggle {
      transform: rotate(90deg)
    }

    .nav-entity-head {
      padding: 4px 14px 4px 28px;
      font-size: 12px;
      font-weight: 500;
      color: var(--text2);
      cursor: pointer;
      user-select: none;
      display: flex;
      align-items: center;
      gap: 5px;
      border-radius: 4px;
      margin: 0 6px
    }

    .nav-entity-head:hover {
      background: var(--bg3);
      color: var(--text)
    }

    .nav-link {
      padding: 4px 14px 4px 36px;
      font-size: 12px;
      color: var(--text3);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      border-radius: 4px;
      margin: 0 6px
    }

    .nav-link:hover {
      background: var(--bg3);
      color: var(--text2)
    }

    .nav-link.active {
      background: rgba(59, 130, 246, .12);
      color: var(--text)
    }

    .nav-schema {
      font-size: 11px;
      color: var(--text3);
      font-style: italic
    }

    .badge {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: .3px;
      padding: 2px 5px;
      border-radius: 3px;
      flex-shrink: 0;
      text-transform: uppercase
    }

    .badge-get {
      background: rgba(34, 197, 94, .15);
      color: var(--get)
    }

    .badge-post {
      background: rgba(245, 158, 11, .15);
      color: var(--post)
    }

    .badge-put {
      background: rgba(139, 92, 246, .15);
      color: var(--put)
    }

    .badge-delete {
      background: rgba(239, 68, 68, .15);
      color: var(--delete)
    }

    .badge-patch {
      background: rgba(6, 182, 212, .15);
      color: var(--patch)
    }

    .nav-group-special .nav-comp-head {
      color: var(--text2)
    }

    .hidden {
      display: none !important
    }

    /* ── Content ── */
    #content {
      flex: 1;
      overflow-y: auto;
      padding: 40px 48px;
      background: var(--bg);
      min-width: 0
    }

    #content::-webkit-scrollbar {
      width: 6px
    }

    #content::-webkit-scrollbar-thumb {
      background: var(--bg3);
      border-radius: 3px
    }

    .c-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: var(--text3);
      gap: 12px
    }

    .c-empty svg {
      opacity: .2
    }

    .c-empty h2 {
      font-size: 18px;
      font-weight: 600;
      color: var(--text2)
    }

    .ep-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap
    }

    .ep-method {
      font-size: 13px;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 6px;
      letter-spacing: .5px;
      flex-shrink: 0
    }

    .ep-method.GET {
      background: rgba(34, 197, 94, .15);
      color: var(--get);
      border: 1px solid rgba(34, 197, 94, .3)
    }

    .ep-method.POST {
      background: rgba(245, 158, 11, .15);
      color: var(--post);
      border: 1px solid rgba(245, 158, 11, .3)
    }

    .ep-method.PUT {
      background: rgba(139, 92, 246, .15);
      color: var(--put);
      border: 1px solid rgba(139, 92, 246, .3)
    }

    .ep-method.DELETE {
      background: rgba(239, 68, 68, .15);
      color: var(--delete);
      border: 1px solid rgba(239, 68, 68, .3)
    }

    .ep-path {
      font-size: 16px;
      font-weight: 600;
      font-family: 'SFMono-Regular', Consolas, monospace;
      color: var(--text);
      word-break: break-all
    }

    .ep-summary {
      font-size: 15px;
      color: var(--text2);
      margin-bottom: 24px;
      line-height: 1.6
    }

    .auth-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 12px;
      background: rgba(245, 158, 11, .1);
      color: var(--amber);
      border: 1px solid rgba(245, 158, 11, .2);
      margin-bottom: 20px
    }

    .section-title {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .8px;
      text-transform: uppercase;
      color: var(--text3);
      margin-bottom: 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid var(--border)
    }

    .params-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 28px;
      font-size: 13px
    }

    .params-table th {
      text-align: left;
      padding: 8px 12px;
      font-size: 11px;
      font-weight: 600;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .5px;
      border-bottom: 1px solid var(--border)
    }

    .params-table td {
      padding: 10px 12px;
      border-bottom: 1px solid rgba(30, 41, 59, .5);
      vertical-align: top
    }

    .params-table tr:last-child td {
      border-bottom: none
    }

    .params-table .pname {
      font-family: monospace;
      color: var(--text);
      font-weight: 500
    }

    .params-table .ptype {
      font-size: 11px;
      color: var(--text3);
      font-family: monospace
    }

    .params-table .pdesc {
      color: var(--text2)
    }

    .params-table .req {
      font-size: 10px;
      color: var(--delete);
      margin-left: 4px
    }

    /* Schema */
    .schema-entity {
      margin-bottom: 32px
    }

    .schema-entity h2 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 6px
    }

    .schema-entity p {
      color: var(--text2);
      margin-bottom: 20px
    }

    .chip-rest {
      display: inline-block;
      font-size: 10px;
      font-weight: 600;
      padding: 2px 7px;
      border-radius: 10px;
      background: rgba(59, 130, 246, .15);
      color: var(--accent);
      border: 1px solid rgba(59, 130, 246, .3);
      margin-left: 8px;
      vertical-align: middle
    }

    .chip-legacy {
      display: inline-block;
      font-size: 10px;
      font-weight: 600;
      padding: 2px 7px;
      border-radius: 10px;
      background: rgba(100, 116, 139, .15);
      color: var(--text3);
      border: 1px solid rgba(100, 116, 139, .3);
      margin-left: 8px;
      vertical-align: middle
    }

    .chip-no-api {
      display: inline-block;
      font-size: 10px;
      padding: 2px 7px;
      border-radius: 10px;
      background: transparent;
      color: var(--text3);
      border: 1px solid var(--border);
      margin-left: 8px;
      vertical-align: middle
    }

    .rel-table td .rt {
      font-size: 11px;
      font-family: monospace;
      padding: 2px 6px;
      border-radius: 4px;
      background: var(--bg3);
      color: var(--accent)
    }

    /* ── Code panel ── */
    #code-panel {
      width: var(--code-w);
      min-width: var(--code-w);
      background: var(--bg2);
      border-left: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      overflow: hidden
    }

    #code-panel-inner {
      flex: 1;
      overflow-y: auto;
      padding: 20px
    }

    #code-panel-inner::-webkit-scrollbar {
      width: 4px
    }

    #code-panel-inner::-webkit-scrollbar-thumb {
      background: var(--bg3);
      border-radius: 2px
    }

    .code-block-wrap {
      margin-bottom: 20px
    }

    .code-block-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .6px;
      text-transform: uppercase;
      color: var(--text3);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .copy-btn {
      cursor: pointer;
      font-size: 10px;
      padding: 2px 8px;
      border-radius: 4px;
      background: var(--bg3);
      color: var(--text3);
      border: 1px solid var(--border);
      transition: all .15s
    }

    .copy-btn:hover {
      background: var(--bg);
      color: var(--text)
    }

    .copy-btn.copied {
      color: var(--green)
    }

    pre.code {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px;
      font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace;
      font-size: 12px;
      line-height: 1.6;
      overflow-x: auto;
      white-space: pre;
      color: var(--text2)
    }

    pre.code .kw {
      color: #f472b6
    }

    pre.code .str {
      color: #86efac
    }

    pre.code .num {
      color: #fb923c
    }

    pre.code .bool {
      color: #67e8f9
    }

    pre.code .null {
      color: #94a3b8;
      font-style: italic
    }

    pre.code .key {
      color: #e2e8f0
    }

    /* Responsive toggle code panel on small screens */
    @media(max-width:1100px) {
      #code-panel {
        display: none
      }
    }
  </style>
</head>

<body>

  <!-- ── SIDEBAR ── -->
  <nav id="sidebar">
    <div id="brand">
      <h1><?= PROJECT_NAME ?></h1>
      <p>Guide Développeur</p>
    </div>
    <div id="search-wrap">
      <input id="search" type="text" placeholder="Rechercher…" autocomplete="off">
    </div>
    <div id="nav-tree"></div>
  </nav>

  <!-- ── CONTENT ── -->
  <main id="content">
    <div class="c-empty" id="welcome">
      <svg width="64" height="64" fill="none" viewBox="0 0 24 24">
        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="#475569" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <h2>Sélectionner un élément</h2>
      <p style="color:var(--text3);font-size:13px">Choisissez une route API ou une entité dans le menu de gauche.</p>
    </div>
    <div id="main-body" class="hidden"></div>
  </main>

  <!-- ── CODE PANEL ── -->
  <aside id="code-panel">
    <div id="code-panel-inner">
      <div id="code-content">
        <p style="color:var(--text3);font-size:12px;margin-top:40px;text-align:center">Sélectionnez un élément pour voir les exemples.</p>
      </div>
    </div>
  </aside>

  <script>
    const DATA = <?= $dataJson ?>;

    // ── Helpers ───────────────────────────────────────────────────────────────────

    const $ = id => document.getElementById(id);

    function h(tag, attrs = {}, ...children) {
      const el = document.createElement(tag);
      for (const [k, v] of Object.entries(attrs)) {
        if (k === 'className') el.className = v;
        else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v);
        else el.setAttribute(k, v);
      }
      children.flat().forEach(c => c && el.append(typeof c === 'string' ? document.createTextNode(c) : c));
      return el;
    }

    function colorJson(json) {
      return json
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\btrue\b|\bfalse\b|\bnull\b|-?\d+(\.\d+)?([eE][+-]?\d+)?)/g, match => {
          if (/^"/.test(match)) {
            return /:$/.test(match) ?
              `<span class="key">${match}</span>` :
              `<span class="str">${match}</span>`;
          }
          if (/true|false/.test(match)) return `<span class="bool">${match}</span>`;
          if (/null/.test(match)) return `<span class="null">${match}</span>`;
          return `<span class="num">${match}</span>`;
        });
    }

    function codeBlock(label, code, lang = 'json') {
      const pre = h('pre', {
        className: 'code'
      });
      pre.innerHTML = lang === 'json' ? colorJson(code) : code;

      const copyBtn = h('button', {
        className: 'copy-btn',
        onclick() {
          navigator.clipboard.writeText(code).then(() => {
            copyBtn.textContent = '✓ Copié';
            copyBtn.classList.add('copied');
            setTimeout(() => {
              copyBtn.textContent = 'Copier';
              copyBtn.classList.remove('copied');
            }, 1600);
          });
        }
      }, 'Copier');

      return h('div', {
          className: 'code-block-wrap'
        },
        h('div', {
          className: 'code-block-label'
        }, label, copyBtn),
        pre
      );
    }

    function badgeEl(method) {
      return h('span', {
        className: `badge badge-${method.toLowerCase()}`
      }, method);
    }

    function curlExample(ep) {
      const base = '/api';
      let url = base + ep.path;
      const qParts = [];
      if (ep.query && ep.query.length) qParts.push('dfilters=on');
      if (qParts.length) url += '?' + qParts.join('&');
      const lines = [];
      if (ep.auth) lines.push(`curl -X ${ep.method} '${url}' \\`);
      else lines.push(`curl '${url}' \\`);
      if (ep.auth) lines.push(`  -H 'Authorization: Bearer <jwt>' \\`);
      lines.push(`  -H 'Content-Type: application/json'`);
      if (ep.body && ep.body.length) {
        const bodyEx = {};
        ep.body.forEach(b => {
          bodyEx[b.name] = b.type === 'integer' ? 0 : (b.type === 'object' ? {} : '...');
        });
        lines[lines.length - 1] += ' \\';
        lines.push(`  -d '${JSON.stringify(bodyEx)}'`);
      }
      return lines.join('\n');
    }

    function responseExample(ep, entity) {
      if (ep.response) return ep.response;
      if (!entity) return '{"success":true}';
      const name = ep.name || '';
      const ek = entity.key;
      if (name === 'list' || name === 'index') {
        return JSON.stringify({
          success: true,
          listEntity: [entity.example],
          nb_element: 1,
          per_page: 30,
          next: 1,
          remain: 0
        }, null, 2);
      }
      if (name === 'create' || name === 'detail') {
        return JSON.stringify({
          success: true,
          [ek]: entity.example,
          detail: ''
        }, null, 2);
      }
      if (name === 'delete') return JSON.stringify({
        success: true,
        detail: ''
      }, null, 2);
      return JSON.stringify({
        success: true,
        [ek]: entity.example
      }, null, 2);
    }

    // ── State ─────────────────────────────────────────────────────────────────────

    let selectedId = null;

    // Build flat items index
    const items = {};

    function addEndpoint(id, ep, entity) {
      items[id] = {
        type: 'endpoint',
        ep,
        entity
      };
    }

    function addSchema(id, entity) {
      items[id] = {
        type: 'schema',
        entity
      };
    }

    // ── Navigation tree ───────────────────────────────────────────────────────────

    function buildNavTree() {
      const root = $('nav-tree');
      root.innerHTML = '';
      const q = $('search').value.trim().toLowerCase();

      function matchStr(s) {
        return !q || s.toLowerCase().includes(q);
      }

      function addToggleGroup(parent, labelEl, bodyEl, defaultOpen = true) {
        const wrap = h('div', {});
        const arrow = h('span', {
          className: 'toggle'
        }, '▶');
        labelEl.append(arrow);
        let open = defaultOpen;

        function toggle() {
          open = !open;
          bodyEl.style.display = open ? '' : 'none';
          labelEl.classList.toggle('open', open);
        }
        labelEl.onclick = toggle;
        labelEl.classList.toggle('open', open);
        if (!open) bodyEl.style.display = 'none';
        wrap.append(labelEl, bodyEl);
        parent.append(wrap);
        return bodyEl;
      }

      function navLink(id, label, method) {
        const isActive = id === selectedId;
        const link = h('div', {
            className: 'nav-link' + (isActive ? ' active' : ''),
            'data-nav-id': id,
            onclick() {
              selectItem(id);
            }
          },
          method ? badgeEl(method) : null,
          h('span', {}, method ? label.replace(method + ' ', '') : label)
        );
        return link;
      }

      // Services + Auth groups
      const groups = [{
          id: 'services',
          icon: '⚡',
          label: 'Services',
          items: DATA.services.map(s => ({
            id: `svc/${s.name}`,
            ep: s,
            entity: null
          }))
        },
        {
          id: 'auth',
          icon: '🔐',
          label: 'Auth',
          items: DATA.auth.map(a => ({
            id: `auth/${a.name}`,
            ep: a,
            entity: null
          }))
        },
      ];
      for (const g of groups) {
        const filtered = g.items.filter(i => matchStr(i.ep.summary) || matchStr(i.ep.path));
        if (!filtered.length && q) continue;
        const labelEl = h('div', {
          className: 'nav-comp-head'
        }, g.icon + ' ', g.label);
        const bodyEl = h('div', {});
        filtered.forEach(i => {
          addEndpoint(i.id, i.ep, null);
          bodyEl.append(navLink(i.id, `${i.ep.method} ${i.ep.path}`, i.ep.method));
        });
        addToggleGroup(root, labelEl, bodyEl, !!q && filtered.length > 0);
      }

      // Components
      DATA.components.forEach(comp => {
        const compLabel = h('div', {
          className: 'nav-comp-head'
        }, '📚 ', comp.name);
        const compBody = h('div', {});
        let compHasMatch = false;

        comp.modules.forEach(mod => {
          const modLabel = h('div', {
            className: 'nav-mod-head'
          }, '📁 ', mod.name);
          const modBody = h('div', {});
          let modHasMatch = false;

          mod.entities.forEach(entity => {
            const entId = `e/${comp.name}/${mod.name}/${entity.key}`;
            const schemaId = `${entId}/schema`;
            addSchema(schemaId, entity);

            const entityNameMatches = matchStr(entity.name) || matchStr(entity.key);
            const endpointMatches = entity.api?.endpoints?.some(ep => matchStr(ep.summary) || matchStr(ep.path));
            if (!entityNameMatches && !endpointMatches && q) return;

            modHasMatch = compHasMatch = true;

            const entLabel = h('div', {
              className: 'nav-entity-head',
              onclick() {
                selectItem(schemaId);
              }
            }, '📄 ', entity.name);
            if (entity.api) {
              const chip = h('span', {
                  className: entity.api.type === 'rest' ? 'chip-rest' : 'chip-legacy',
                  style: 'font-size:9px;padding:1px 5px;border-radius:8px;margin-left:auto;'
                },
                entity.api.type === 'rest' ? 'REST' : 'Legacy');
              entLabel.append(chip);
            }

            const entBody = h('div', {});
            entBody.append(navLink(schemaId, 'Schéma'));

            if (entity.api?.endpoints) {
              entity.api.endpoints.forEach((ep, idx) => {
                const epId = `${entId}/ep/${idx}`;
                addEndpoint(epId, ep, entity);
                if (!entityNameMatches && !matchStr(ep.summary) && !matchStr(ep.path) && q) return;
                entBody.append(navLink(epId, `${ep.method} ${ep.path}`, ep.method));
              });
            }

            addToggleGroup(modBody, entLabel, entBody, entityNameMatches && !!q);
          });

          if (modHasMatch || !q) addToggleGroup(compBody, modLabel, modBody, !!q);
        });

        if (compHasMatch || !q) addToggleGroup(root, compLabel, compBody, !!q);
      });
    }

    // ── Render ────────────────────────────────────────────────────────────────────

    function paramsTable(rows, cols) {
      if (!rows.length) return null;
      const thead = h('tr', {}, ...cols.map(c => h('th', {}, c)));
      const tbody = rows.map(r => h('tr', {},
        h('td', {}, h('span', {
          className: 'pname'
        }, r.name), r.required ? h('span', {
          className: 'req'
        }, '*req') : null),
        h('td', {}, h('span', {
          className: 'ptype'
        }, r.type)),
        h('td', {}, h('span', {
          className: 'pdesc'
        }, r.description || ''))
      ));
      return h('table', {
        className: 'params-table'
      }, thead, ...tbody);
    }

    function renderEndpoint(item) {
      const {
        ep,
        entity
      } = item;
      const body = $('main-body');
      body.innerHTML = '';
      $('welcome').classList.add('hidden');
      body.classList.remove('hidden');

      // Header
      body.append(
        h('div', {
            className: 'ep-header'
          },
          h('span', {
            className: `ep-method ${ep.method}`
          }, ep.method),
          h('span', {
            className: 'ep-path'
          }, ep.path)
        ),
        h('p', {
          className: 'ep-summary'
        }, ep.summary),
        entity?.api?.description ? h('p', {style:'font-size:13px;color:var(--text3);margin-top:-16px;margin-bottom:20px'}, entity.api.description) : null
      );

      if (ep.auth) body.append(h('div', {
        className: 'auth-tag'
      }, '🔒 Authentification requise (JWT)'));

      // Path params
      if (ep.pathParams?.length) {
        body.append(h('div', {
          className: 'section-title'
        }, 'Paramètres de chemin'));
        body.append(paramsTable(ep.pathParams, ['Nom', 'Type', 'Description']));
      }

      // Query params
      if (ep.query?.length) {
        body.append(h('div', {
          className: 'section-title'
        }, 'Paramètres de query'));
        body.append(paramsTable(ep.query, ['Nom', 'Type', 'Description']));
      }

      // Body params
      if (ep.body?.length) {
        body.append(h('div', {
          className: 'section-title'
        }, 'Corps de la requête (JSON)'));
        body.append(paramsTable(ep.body, ['Champ', 'Type', 'Description']));
      }

      // Code panel
      const curlCode = curlExample(ep);
      const respCode = responseExample(ep, entity);

      $('code-content').innerHTML = '';
      $('code-content').append(
        codeBlock('Requête cURL', curlCode, 'bash'),
        codeBlock('Réponse (200)', respCode, 'json')
      );
    }

    function renderSchema(item) {
      const {
        entity
      } = item;
      const body = $('main-body');
      body.innerHTML = '';
      $('welcome').classList.add('hidden');
      body.classList.remove('hidden');

      const apiChip = entity.api ?
        h('span', {
          className: entity.api.type === 'rest' ? 'chip-rest' : 'chip-legacy'
        }, entity.api.type === 'rest' ? 'REST' : 'Legacy') :
        h('span', {
          className: 'chip-no-api'
        }, 'Pas d\'API publique');

      body.append(h('div', {
          className: 'schema-entity'
        },
        h('h2', {}, entity.name, ' ', apiChip),
        h('p', {
          style: 'color:var(--text2);margin-bottom:20px'
        }, entity.api?.description || `Entité ${entity.public?'publique':'interne'}${entity.api? ' — API: '+entity.api.base : ''}`)
      ));

      // Fields
      body.append(h('div', {
        className: 'section-title'
      }, 'Champs'));
      const fieldRows = entity.fields.map(f => h('tr', {},
        h('td', {}, h('span', {
          className: 'pname'
        }, f.name), (f.auto ? h('span', {
          className: 'ptype',
          style: 'margin-left:6px'
        }, 'auto') : null)),
        h('td', {}, h('span', {
          className: 'ptype'
        }, f.datatype + (f.size ? `(${f.size})` : '')), ),
        h('td', {}, h('span', {
          className: 'ptype'
        }, f.nullable ? 'nullable' : 'NOT NULL')),
        h('td', {}, h('span', {
          className: 'pdesc'
        }, f.formtype || ''))
      ));
      body.append(h('table', {
          className: 'params-table'
        },
        h('tr', {}, ...['Champ', 'Type', 'Nullable', 'Form type'].map(c => h('th', {}, c))),
        ...fieldRows
      ));

      // Relations
      if (entity.relations.length) {
        body.append(h('div', {
          className: 'section-title',
          style: 'margin-top:28px'
        }, 'Relations'));
        const relRows = entity.relations.map(r => h('tr', {},
          h('td', {}, h('span', {
            className: 'pname'
          }, r.entity)),
          h('td', {}, h('span', {
            className: 'rt rel-table'
          }, r.cardinality)),
          h('td', {}, r.nullable ? 'nullable' : 'not null'),
          h('td', {}, r.ondelete || '')
        ));
        body.append(h('table', {
            className: 'params-table'
          },
          h('tr', {}, ...['Entité liée', 'Cardinalité', 'Nullable', 'On delete'].map(c => h('th', {}, c))),
          ...relRows
        ));
      }

      // Code panel: JSON schema example
      $('code-content').innerHTML = '';
      $('code-content').append(
        codeBlock('Exemple JSON', JSON.stringify(entity.example, null, 2), 'json')
      );
    }

    function selectItem(id) {
      selectedId = id;
      const item = items[id];
      if (!item) return;

      // Update active class in-place — no full nav rebuild
      document.querySelectorAll('.nav-link.active').forEach(el => el.classList.remove('active'));
      const link = document.querySelector(`.nav-link[data-nav-id="${id.replace(/"/g,'&quot;')}"]`);
      if (link) link.classList.add('active');

      if (item.type === 'endpoint') renderEndpoint(item);
      else renderSchema(item);
    }

    // ── Init ──────────────────────────────────────────────────────────────────────

    $('search').addEventListener('input', () => buildNavTree());
    buildNavTree();
  </script>
</body>

</html>