<?php

/**
 * ETAPE N.2 : generation du Guide Developpeur extraite de
 * documentation/guide.php (parsing statique DataCore + FrontControllers,
 * aucune classe PHP chargee) pour etre partagee entre :
 *  - documentation/guide.php (page interactive / ?format=json) ;
 *  - `php devups doc:guide`, qui ecrit une page par module/entite.
 *
 * Comportement inchange - seul l'emplacement du code a change.
 */

if (!function_exists('dv_guide_parse_doc')) {
    function dv_guide_parse_doc(string $doc): array
    {
        $r = ['summary' => '', 'method' => null, 'subpath' => null, 'auth' => false, 'api' => null, 'apiDescription' => null, 'query' => [], 'body' => [], 'response' => null];

        if (preg_match('/@(GET|POST|PUT|DELETE)(?:\s*\(([^)]*)\))?/i', $doc, $m)) {
            $r['method'] = strtolower($m[1]);
            $attrs = $m[2] ?? '';
            if (preg_match('/path=[\'"]([^\'"]*)[\'"]/', $attrs, $pm)) $r['subpath'] = $pm[1];
            if (preg_match('/description=[\'"]([^\'"]+)[\'"]/', $attrs, $dm)) $r['summary'] = $dm[1];
        }
        if (preg_match('/@Api\s*\(([^)]+)\)/i', $doc, $m)) {
            $attrs = $m[1];
            if (preg_match('/name=[\'"]([^\'"]+)[\'"]/i', $attrs, $nm)) $r['api'] = $nm[1];
            if (preg_match('/description=[\'"]([^\'"]+)[\'"]/i', $attrs, $dm)) $r['apiDescription'] = $dm[1];
        }
        if (preg_match('/@Auth\s*\(\s*authorized\s*=\s*1\s*\)/i', $doc)) $r['auth'] = true;

        preg_match_all('/@query\s+(\S+)\s+(\S+)\s+(.*)/im', $doc, $qm, PREG_SET_ORDER);
        foreach ($qm as $q) $r['query'][] = ['name' => $q[1], 'type' => $q[2], 'description' => trim($q[3])];

        preg_match_all('/@body\s+(\S+)\s+(\S+)\s+(.*)/im', $doc, $bm, PREG_SET_ORDER);
        foreach ($bm as $b) $r['body'][] = ['name' => $b[1], 'type' => $b[2], 'description' => trim($b[3])];

        if (preg_match('/@response\s+(\{[^@]*\})/s', $doc, $rm)) $r['response'] = trim($rm[1]);

        foreach (explode("\n", $doc) as $line) {
            $l = trim(ltrim($line, " \t/*"));
            if ($l && $l[0] !== '@') { $r['summary'] = $l; break; }
        }
        return $r;
    }
}

if (!function_exists('dv_guide_to_oa_path')) {
    function dv_guide_to_oa_path(string $p): string
    {
        return preg_replace('/\/:([a-zA-Z_]\w*)/', '/{$1}', $p);
    }
}

if (!function_exists('dv_guide_entity_example')) {
    function dv_guide_entity_example(array $fields): array
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
                'text' => 'Lorem ipsum…',
                'date' => '2026-01-01',
                'datetime' => '2026-01-01 00:00:00',
                'float', 'decimal' => 0.0,
                default => '',
            };
        }
        return $ex;
    }
}

if (!function_exists('dv_guide_legacy_api')) {
    function dv_guide_legacy_api(string $ek, bool $fullAuth, array $idP, array $stdF): array
    {
        return ['type' => 'legacy', 'fc' => null, 'base' => "/$ek", 'endpoints' => [
            ['method' => 'GET', 'path' => "/lazyloading/$ek", 'name' => 'list', 'summary' => "Lister $ek (paginé)", 'auth' => $fullAuth, 'query' => $stdF, 'pathParams' => [], 'body' => [], 'response' => null],
            ['method' => 'GET', 'path' => "/detail/$ek", 'name' => 'detail', 'summary' => "Détail d'un $ek", 'auth' => $fullAuth, 'query' => $idP, 'pathParams' => [], 'body' => [], 'response' => null],
            ['method' => 'POST', 'path' => "/create/$ek", 'name' => 'create', 'summary' => "Créer un $ek", 'auth' => true, 'query' => [], 'pathParams' => [], 'body' => [['name' => $ek, 'type' => 'object', 'description' => "Champs de l'entité"]], 'response' => null],
            ['method' => 'POST', 'path' => "/update/$ek", 'name' => 'update', 'summary' => "Modifier un $ek", 'auth' => true, 'query' => $idP, 'pathParams' => [], 'body' => [['name' => $ek, 'type' => 'object', 'description' => 'Champs à modifier']], 'response' => null],
            ['method' => 'GET', 'path' => "/delete/$ek", 'name' => 'delete', 'summary' => "Supprimer un $ek", 'auth' => true, 'query' => $idP, 'pathParams' => [], 'body' => [], 'response' => null],
        ]];
    }
}

if (!function_exists('dv_build_doc_guide')) {
    /**
     * Construit $guideData = ['project', 'components', 'services', 'auth'].
     * $root est la racine du projet (ROOT, ou un dossier different en test).
     */
    function dv_build_doc_guide(string $root): array
    {
        $root = rtrim($root, '/\\') . '/';

        $wsContent = file_get_contents($root . 'route/WebService.php');
        preg_match('/\$public_access\s*=\s*\[(.*?)\];/s', $wsContent, $pam);
        preg_match_all("/['\"](\w+)['\"]\s*=>/", $pam[1] ?? '', $pk);
        $publicKeys = $pk[1] ?? [];

        preg_match_all("/Auth::addRestriction\(['\"](\w+)['\"],\s*\[\]/", $wsContent, $arm);
        $fullAuthEntities = $arm[1] ?? [];

        $stdFilters = [
            ['name' => 'dfilters', 'type' => 'string', 'description' => 'Activer les filtres dynamiques (`on`)'],
            ['name' => 'per_page', 'type' => 'integer', 'description' => 'Résultats par page (défaut 30)'],
            ['name' => 'next', 'type' => 'integer', 'description' => 'Numéro de page (commence à 1)'],
            ['name' => 'dsort', 'type' => 'string', 'description' => 'Champ de tri'],
            ['name' => 'direction', 'type' => 'string', 'description' => 'ASC | DESC'],
        ];
        $idParam = [['name' => 'id', 'type' => 'integer', 'description' => 'ID de la ressource']];

        $fcMap = [];
        $fcFiles = glob($root . 'src/*/*/Controller/*FrontController.php');
        sort($fcFiles);

        foreach ($fcFiles as $fcFile) {
            $code = file_get_contents($fcFile);
            if (!preg_match('/class\s+(\w+FrontController)/i', $code, $cm)) continue;
            $fcName = $cm[1];

            preg_match('/\/\*\*(.*?)\*\/\s*class\s+' . preg_quote($fcName, '/') . '$/ms', $code, $cdm);
            $classAnnot = dv_guide_parse_doc($cdm[0] ?? '');
            if (!($basePath = $classAnnot['api'])) continue;

            $entityKey = ltrim($basePath, '/');
            if (!in_array($entityKey, $publicKeys)) continue;

            $endpoints = [];
            preg_match_all('/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+)\s*\(([^)]*)\)/s', $code, $mms, PREG_SET_ORDER);

            foreach ($mms as $mm) {
                $a = dv_guide_parse_doc('/**' . $mm[1] . '*/');
                if ($a['method'] === null) continue;

                $fullPath = dv_guide_to_oa_path($basePath . $a['subpath']);
                preg_match_all('/\{([a-zA-Z_]\w*)\}/', $fullPath, $ppm);
                $pathParams = array_map(fn($p) => ['name' => $p, 'type' => 'integer', 'description' => 'Identifiant'], $ppm[1]);

                $qParams = $a['query'];
                if ($mm[2] === 'index' && $a['method'] === 'get') {
                    $qParams = array_merge($qParams, $stdFilters);
                }

                $endpoints[] = [
                    'method' => strtoupper($a['method']),
                    'path' => $fullPath,
                    'name' => $mm[2],
                    'summary' => $a['summary'] ?: ucfirst($mm[2]) . ' ' . $entityKey,
                    'auth' => $a['auth'] || in_array($entityKey, $fullAuthEntities),
                    'query' => $qParams,
                    'pathParams' => $pathParams,
                    'body' => $a['body'],
                    'response' => $a['response'],
                ];
            }

            $fcMap[$entityKey] = ['type' => 'rest', 'fc' => $fcName, 'base' => $basePath, 'description' => $classAnnot['apiDescription'], 'endpoints' => $endpoints];
        }

        $srcDir = $root . 'src';
        $components = [];

        foreach (array_diff(scandir($srcDir), ['.', '..', 'requires.php']) as $compDir) {
            $compPath = "$srcDir/$compDir";
            if (!is_dir($compPath) || !file_exists("$compPath/{$compDir}Core.json")) continue;

            $component = ['name' => $compDir, 'modules' => []];

            foreach (array_diff(scandir($compPath), ['.', '..']) as $modDir) {
                $modPath = "$compPath/$modDir";
                if (!is_dir($modPath) || !file_exists("$modPath/" . strtolower($modDir) . "Core.json")) continue;

                $module = ['name' => $modDir, 'entities' => []];
                $coreDir = "$modPath/Core";
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
                        'name' => ucfirst($ec['name']),
                        'key' => $ek,
                        'public' => in_array($ek, $publicKeys),
                        'fields' => $fields,
                        'relations' => $relations,
                        'api' => $api,
                        'example' => dv_guide_entity_example($fields),
                    ];
                }

                if ($module['entities']) $component['modules'][] = $module;
            }
            if ($component['modules']) $components[] = $component;
        }

        $services = [];
        preg_match_all('/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+Serve)\s*\(/s', $wsContent, $sms, PREG_SET_ORDER);
        foreach ($sms as $sm) {
            $rk = preg_replace('/Serve$/', '', $sm[2]);
            if ($rk === 'system') continue;
            $a = dv_guide_parse_doc('/**' . $sm[1] . '*/');
            $services[] = ['method' => 'GET', 'path' => "/$rk", 'name' => $rk, 'summary' => $a['summary'] ?: ucfirst($rk), 'auth' => false, 'query' => $a['query'], 'pathParams' => [], 'body' => $a['body'], 'response' => $a['response']];
        }

        $authEndpoints = [
            ['method' => 'POST', 'path' => '/user/googleAuth', 'name' => 'googleAuth', 'summary' => 'Connexion / inscription via Google OAuth', 'auth' => false, 'query' => [], 'pathParams' => [], 'body' => [['name' => 'idToken', 'type' => 'string', 'description' => 'ID Token fourni par Google Sign-In']], 'response' => '{"success":true,"jwt":"eyJhbGc...","user":{"id":1,"username":"..."},"isNewAccount":true}'],
            ['method' => 'POST', 'path' => '/user/appleAuth', 'name' => 'appleAuth', 'summary' => 'Connexion / inscription via Apple Sign-In', 'auth' => false, 'query' => [], 'pathParams' => [], 'body' => [['name' => 'identityToken', 'type' => 'string', 'description' => 'Identity Token Apple'], ['name' => 'user', 'type' => 'string', 'description' => 'Identifiant unique Apple (1er appel)']], 'response' => '{"success":true,"jwt":"eyJhbGc...","user":{"id":2,"username":"Apple User"},"isNewAccount":true}'],
        ];

        return ['project' => PROJECT_NAME, 'components' => $components, 'services' => $services, 'auth' => $authEndpoints];
    }
}

if (!function_exists('dv_render_doc_guide_entity_page')) {
    /**
     * ETAPE N.2 : page HTML statique autonome pour une entite - utilisee par
     * `php devups doc:guide` ("une page par module/entite"). Rendu simple
     * (pas de JS), reutilise les memes donnees que la page interactive.
     */
    function dv_render_doc_guide_entity_page(array $guideData, array $entity): string
    {
        $esc = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $fieldRows = '';
        foreach ($entity['fields'] as $f) {
            $fieldRows .= '<tr><td><code>' . $esc($f['name']) . '</code>' . ($f['auto'] ? ' <em>(auto)</em>' : '') . '</td>'
                . '<td>' . $esc($f['datatype'] . ($f['size'] ? "({$f['size']})" : '')) . '</td>'
                . '<td>' . ($f['nullable'] ? 'nullable' : 'NOT NULL') . '</td>'
                . '<td>' . $esc($f['formtype'] ?? '') . '</td></tr>';
        }

        $relRows = '';
        foreach ($entity['relations'] as $r) {
            $relRows .= '<tr><td><code>' . $esc($r['entity']) . '</code></td>'
                . '<td>' . $esc($r['cardinality']) . '</td>'
                . '<td>' . ($r['nullable'] ? 'nullable' : 'not null') . '</td>'
                . '<td>' . $esc($r['ondelete'] ?? '') . '</td></tr>';
        }

        $endpointRows = '';
        foreach ($entity['api']['endpoints'] ?? [] as $ep) {
            $endpointRows .= '<tr><td><strong>' . $esc($ep['method']) . '</strong></td>'
                . '<td><code>' . $esc($ep['path']) . '</code></td>'
                . '<td>' . $esc($ep['summary']) . '</td>'
                . '<td>' . ($ep['auth'] ? 'JWT' : '-') . '</td></tr>';
        }

        $apiType = $entity['api']['type'] ?? null;
        $apiBadge = $apiType ? ('<span>' . $esc(strtoupper($apiType)) . '</span>') : '<span>Pas d\'API publique</span>';

        $exampleJson = $esc(json_encode($entity['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{$esc($guideData['project'])} - {$esc($entity['name'])}</title>
</head>
<body>
<h1>{$esc($entity['name'])} {$apiBadge}</h1>
<p>Entité {$esc($entity['public'] ? 'publique' : 'interne')}</p>

<h2>Champs</h2>
<table border="1" cellpadding="6">
<tr><th>Champ</th><th>Type</th><th>Nullable</th><th>Form type</th></tr>
{$fieldRows}
</table>

<h2>Relations</h2>
<table border="1" cellpadding="6">
<tr><th>Entité liée</th><th>Cardinalité</th><th>Nullable</th><th>On delete</th></tr>
{$relRows}
</table>

<h2>Routes API</h2>
<table border="1" cellpadding="6">
<tr><th>Méthode</th><th>Chemin</th><th>Description</th><th>Auth</th></tr>
{$endpointRows}
</table>

<h2>Exemple JSON</h2>
<pre>{$exampleJson}</pre>
</body>
</html>
HTML;
    }
}
