<?php

/**
 * ETAPE N.1 : generation OpenAPI 3 extraite de documentation/api.php (parsing
 * statique des annotations Devups dans les FrontControllers/WebService.php,
 * sans charger de classe PHP) pour etre partagee entre :
 *  - documentation/api.php (JSON servi en direct, Swagger UI) ;
 *  - `php devups api:doc`, qui ecrit un instantane openapi.json (cf.
 *    builder.php) - utile pour un outillage externe (CI, clients generes),
 *    la source de verite restant la generation en direct.
 *
 * Le comportement (sortie JSON) est inchange - seul l'emplacement du code a
 * change. N'importe quel fichier PHP du depot (aucune classe chargee).
 */

if (!function_exists('dv_openapi_parse_doc')) {
    function dv_openapi_parse_doc(string $doc): array
    {
        $r = ['method' => null, 'subpath' => null, 'auth' => false, 'summary' => '', 'api' => null, 'apiDescription' => null];

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
        if (preg_match('/@Auth\s*\(\s*authorized\s*=\s*1\s*\)/i', $doc)) {
            $r['auth'] = true;
        }
        foreach (explode("\n", $doc) as $line) {
            $line = trim(ltrim($line, " \t/*"));
            if ($line !== '' && $line[0] !== '@') { $r['summary'] = $line; break; }
        }
        return $r;
    }
}

if (!function_exists('dv_openapi_to_oa_path')) {
    function dv_openapi_to_oa_path(string $path): string
    {
        return preg_replace('/\/:([a-zA-Z_][a-zA-Z0-9_]*)/', '/{$1}', $path);
    }
}

if (!function_exists('dv_openapi_path_params')) {
    function dv_openapi_path_params(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $m);
        return array_map(fn($p) => ['name' => $p, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']], $m[1]);
    }
}

if (!function_exists('dv_openapi_make_op')) {
    function dv_openapi_make_op(string $tag, string $summary, bool $auth, array $pParams = [], array $qParams = []): array
    {
        $op = [
            'tags' => [$tag],
            'summary' => $summary,
            'responses' => ['200' => ['description' => 'Succès'], '401' => ['description' => 'Non authentifié']],
        ];
        if ($auth) $op['security'] = [['BearerAuth' => []]];
        $params = array_merge($pParams, $qParams);
        if ($params) $op['parameters'] = $params;
        return $op;
    }
}

if (!function_exists('dv_openapi_json_body')) {
    function dv_openapi_json_body(string $entity): array
    {
        return ['required' => true, 'content' => ['application/json' => [
            'schema' => ['type' => 'object', 'properties' => [$entity => ['type' => 'object']]],
        ]]];
    }
}

if (!function_exists('dv_build_admin_openapi_paths')) {
    /**
     * ETAPE N.3 : routes back-office (session admin, CRUD standard genere
     * par ModuleController - voir tests/E2E/AdminScreensTest.php::screens()
     * pour le meme calcul de chemin), pour "distinguer les routes front
     * (JWT) et admin (session)" - absentes de dv_build_openapi_spec() par
     * defaut (ces routes ne sont pas exposees publiquement, contrairement
     * aux routes front documentees par documentation/api.php).
     */
    function dv_build_admin_openapi_paths(string $root): array
    {
        $root = rtrim($root, '/\\') . '/';
        $config = require $root . 'config/dvups_configurations.php';

        $paths = [];
        $tags = [['name' => 'Admin – session', 'description' => "Back-office (authentification par session, cookie `PHPSESSID`), pas par JWT."]];

        foreach ($config as $key => $entity) {
            if (strpos($key, '\\') !== false || !is_array($entity) || empty($entity['component']))
                continue;

            $slug = str_replace('_', '-', strtolower($entity['name']));
            $ekLower = strtolower($entity['name']);
            $base = $entity['component'] . '/' . $entity['module'] . '/' . $slug;

            $paths["/{$base}/list"]['get'] = [
                'tags' => ['Admin – session'],
                'summary' => "Liste back-office de {$entity['name']} (HTML)",
                'security' => [['SessionAuth' => []]],
                'responses' => ['200' => ['description' => 'Page HTML complète'], '401' => ['description' => 'Session admin requise']],
            ];
            $paths["/api/{$ekLower}/form"]['get'] = [
                'tags' => ['Admin – session'],
                'summary' => "Formulaire back-office de {$entity['name']} (JSON)",
                'security' => [['SessionAuth' => []]],
                'responses' => ['200' => ['description' => '{"success":true,"form":"<form>…</form>"}'], '401' => ['description' => 'Session admin requise']],
            ];
        }

        return ['tags' => $tags, 'paths' => $paths];
    }
}

if (!function_exists('dv_build_openapi_spec')) {
    /**
     * Construit le document OpenAPI 3 complet. $root est la racine du
     * projet (ROOT, ou un dossier different en test) - permet d'appeler
     * cette fonction sans dependre du constant ROOT du bootstrap complet.
     *
     * $includeAdmin (ETAPE N.3, defaut false pour ne pas changer le
     * comportement de documentation/api.php, verifie byte-identique) ajoute
     * les routes back-office (session) en plus des routes front (JWT) -
     * utilise par la page Swagger UI admin, protegee par can().
     */
    function dv_build_openapi_spec(string $root, bool $includeAdmin = false): array
    {
        $root = rtrim($root, '/\\') . '/';

        $wsFile = $root . 'route/WebService.php';
        $wsContent = file_exists($wsFile) ? file_get_contents($wsFile) : '';

        preg_match('/\$public_access\s*=\s*\[(.*?)\];/s', $wsContent, $pam);
        preg_match_all("/['\"](\w+)['\"]\s*=>/", $pam[1] ?? '', $pakm);
        $publicKeys = $pakm[1] ?? [];

        preg_match_all("/Auth::addRestriction\(['\"](\w+)['\"],\s*\[\]/", $wsContent, $arm);
        $fullAuthEntities = $arm[1] ?? [];

        $dfilterParams = [
            ['name' => 'dfilters', 'in' => 'query', 'schema' => ['type' => 'string'], 'example' => 'on', 'description' => 'Activer les filtres (`dfilters=on&champ:eq=valeur`)'],
            ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer'], 'example' => 30, 'description' => 'Résultats par page (défaut 30)'],
            ['name' => 'next', 'in' => 'query', 'schema' => ['type' => 'integer'], 'example' => 1, 'description' => 'Numéro de page'],
            ['name' => 'dsort', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Champ de tri'],
            ['name' => 'direction', 'in' => 'query', 'schema' => ['type' => 'string'], 'enum' => ['ASC', 'DESC']],
        ];
        $idQueryParam = ['name' => 'id', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']];

        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => PROJECT_NAME . ' – API',
                'version' => '1.0.0',
                'description' =>
                    "API REST de la plateforme **" . PROJECT_NAME . "**.\n\n"
                    . "## Styles de routes\n\n"
                    . "### Style REST (`/{entité}`, `/{entité}/{id}`)\n"
                    . "Entités dotées d'un **FrontController dédié** — verbes HTTP stricts. "
                    . "C'est le style principal utilisé par l'application mobile.\n\n"
                    . "### Style Legacy (`/action/{entité}`) — entités sans FrontController\n"
                    . "Routes génériques : `/create/{e}`, `/detail/{e}?id=X`, `/update/{e}?id=X`, "
                    . "`/delete/{e}?id=X`, `/lazyloading/{e}`\n\n"
                    . "**Filtres dynamiques** : `?dfilters=on&champ:op=valeur`  \n"
                    . "Opérateurs : `eq neq gt lt get let lk in notin isNull notNull btw`  \n"
                    . "Pagination : `?next=<page>&per_page=<n>` — inspecter `remain` dans la réponse.\n\n"
                    . "## Authentification\n"
                    . "`Authorization: Bearer <jwt>`",
            ],
            'servers' => [['url' => '/api', 'description' => 'http://127.0.0.1:3333/api']],
            'components' => ['securitySchemes' => ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT']]],
            'tags' => [],
            'paths' => [],
        ];

        if ($includeAdmin) {
            $spec['components']['securitySchemes']['SessionAuth'] = ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'PHPSESSID'];
        }

        $paths = [];
        $tags = [];
        $entitiesWithFC = [];

        // PASSE 1 - routes REST (FrontControllers)
        $fcFiles = glob($root . 'src/*/*/Controller/*FrontController.php');
        sort($fcFiles);

        foreach ($fcFiles as $fcFile) {
            $content = file_get_contents($fcFile);

            if (!preg_match('/class\s+(\w+FrontController)/i', $content, $cm)) continue;
            $fcName = $cm[1];

            preg_match('/\/\*\*(.*?)\*\/\s*class\s+' . preg_quote($fcName, '/') . '/s', $content, $cdm);
            $classAnnot = dv_openapi_parse_doc($cdm[0] ?? '');
            $basePath = $classAnnot['api'];
            if (!$basePath) continue;

            $entityKey = ltrim($basePath, '/');
            if (!in_array($entityKey, $publicKeys)) continue;

            $entitiesWithFC[] = $entityKey;

            $fullAuth = in_array($entityKey, $fullAuthEntities);
            $tag = "REST – $entityKey";
            $apiDesc = $classAnnot['apiDescription'] ?? null;
            $tags[] = ['name' => $tag, 'description' => $apiDesc ?: "Routes REST `$basePath` — `$fcName`"];

            preg_match_all(
                '/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+)\s*\(([^)]*)\)/s',
                $content, $mmatches, PREG_SET_ORDER
            );

            foreach ($mmatches as $mm) {
                $annot = dv_openapi_parse_doc('/**' . $mm[1] . '*/');
                if ($annot['method'] === null) continue;

                $fullPath = dv_openapi_to_oa_path($basePath . $annot['subpath']);
                $pParams = dv_openapi_path_params($fullPath);
                $summary = $annot['summary'] ?: ucfirst($mm[2]) . ' ' . $entityKey;
                $auth = $annot['auth'] || $fullAuth;
                $verb = $annot['method'];

                $op = dv_openapi_make_op($tag, $summary, $auth, $pParams);
                if (in_array($verb, ['post', 'put'])) $op['requestBody'] = dv_openapi_json_body($entityKey);
                if ($mm[2] === 'index' && $verb === 'get') {
                    $op['parameters'] = array_merge($op['parameters'] ?? [], $dfilterParams);
                }

                $paths[$fullPath][$verb] = $op;
            }
        }

        // PASSE 2 - routes legacy CRUD (entites sans FrontController)
        $legacyEntities = array_diff($publicKeys, $entitiesWithFC);

        if ($legacyEntities) {
            $tags[] = [
                'name' => 'Legacy CRUD',
                'description' => 'Routes génériques `/action/{entité}` — entités sans FrontController dédié.',
            ];
        }

        foreach ($legacyEntities as $ek) {
            $tag = "Legacy – $ek";
            $fullAuth = in_array($ek, $fullAuthEntities);
            $tags[] = ['name' => $tag, 'description' => "Routes legacy pour `$ek`"];

            $paths["/lazyloading/$ek"]['get'] = dv_openapi_make_op($tag, "Lister $ek (paginé)", $fullAuth, [], $dfilterParams);
            $paths["/detail/$ek"]['get'] = dv_openapi_make_op($tag, "Détail d'un $ek", $fullAuth, [], [$idQueryParam]);

            $op = dv_openapi_make_op($tag, "Créer un $ek", true);
            $op['requestBody'] = dv_openapi_json_body($ek);
            $paths["/create/$ek"]['post'] = $op;

            $op = dv_openapi_make_op($tag, "Modifier un $ek", true, [], [$idQueryParam]);
            $op['requestBody'] = dv_openapi_json_body($ek);
            $paths["/update/$ek"]['post'] = $op;

            $paths["/delete/$ek"]['get'] = dv_openapi_make_op($tag, "Supprimer un $ek", true, [], [$idQueryParam]);
        }

        // PASSE 3 - services speciaux WebService::*Serve()
        $tags[] = ['name' => 'Services', 'description' => 'Routes de service — méthodes `*Serve()` de `WebService`'];

        preg_match_all(
            '/\/\*\*(.*?)\*\/\s*public\s+function\s+(\w+Serve)\s*\(/s',
            $wsContent, $smatches, PREG_SET_ORDER
        );
        foreach ($smatches as $sm) {
            $routeKey = preg_replace('/Serve$/', '', $sm[2]);
            if ($routeKey === 'system') continue;
            $annot = dv_openapi_parse_doc('/**' . $sm[1] . '*/');
            $paths["/$routeKey"]['get'] = dv_openapi_make_op('Services', $annot['summary'] ?: ucfirst($routeKey), false);
        }

        // PASSE 4 - routes explicites (auth sociale)
        $tags[] = ['name' => 'Auth', 'description' => 'Authentification sociale (Google, Apple)'];

        $paths['/user/googleAuth']['post'] = [
            'tags' => ['Auth'],
            'summary' => 'Connexion / inscription via Google OAuth',
            'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => [
                'type' => 'object', 'required' => ['idToken'],
                'properties' => ['idToken' => ['type' => 'string', 'description' => 'ID Token Google Sign-In']],
            ]]]],
            'responses' => [
                '200' => ['description' => 'JWT + user + `isNewAccount`', 'content' => ['application/json' => ['schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean'],
                        'jwt' => ['type' => 'string'],
                        'user' => ['type' => 'object'],
                        'isNewAccount' => ['type' => 'boolean', 'description' => 'true si compte créé à cet appel'],
                    ],
                ]]]],
                '401' => ['description' => 'Token invalide'],
            ],
        ];

        $paths['/user/appleAuth']['post'] = [
            'tags' => ['Auth'],
            'summary' => 'Connexion / inscription via Apple Sign-In',
            'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => [
                'type' => 'object', 'required' => ['identityToken'],
                'properties' => [
                    'identityToken' => ['type' => 'string', 'description' => 'Identity Token Apple'],
                    'user' => ['type' => 'string', 'description' => 'Identifiant Apple unique (1er appel seulement)'],
                ],
            ]]]],
            'responses' => [
                '200' => ['description' => 'JWT + user + `isNewAccount`'],
                '401' => ['description' => 'Token invalide'],
            ],
        ];

        if ($includeAdmin) {
            $admin = dv_build_admin_openapi_paths($root);
            $paths = array_merge($paths, $admin['paths']);
            $tags = array_merge($tags, $admin['tags']);
        }

        ksort($paths);

        $seenTags = [];
        $finalTags = [];
        foreach ($tags as $t) {
            if (!isset($seenTags[$t['name']])) { $seenTags[$t['name']] = true; $finalTags[] = $t; }
        }

        $spec['tags'] = $finalTags;
        $spec['paths'] = $paths;

        return $spec;
    }
}
