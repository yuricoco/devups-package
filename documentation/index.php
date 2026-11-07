<?php
if (!defined('PROJECT_NAME') && (getenv('DEVUPS_CONSTANTS') || file_exists(__DIR__ . '/../config/constante.php')))
    require_once __DIR__ . '/../config/load_constants.php';
if (!defined('PROJECT_NAME'))
    define('PROJECT_NAME', 'Devups');
// Serve the Swagger UI — no framework bootstrap needed.
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= PROJECT_NAME ?> – Documentation API</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
  <style>
    /* ── Brand bar ── */
    #brand-bar {
      background: #1a1a2e;
      color: #fff;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      font-family: sans-serif;
    }
    #brand-bar .logo {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #e94560;
    }
    #brand-bar .subtitle {
      font-size: 13px;
      opacity: 0.7;
    }
    /* ── Swagger UI overrides ── */
    .swagger-ui .topbar { display: none; }
    .swagger-ui .info .title { font-size: 26px; }
    body { margin: 0; }
  </style>
</head>
<body>

<div id="brand-bar">
  <span class="logo"><?= PROJECT_NAME ?></span>
  <span class="subtitle">Documentation API REST — v1.0</span>
</div>

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
window.onload = () => {
  SwaggerUIBundle({
    url: './api.php',
    dom_id: '#swagger-ui',
    deepLinking: true,
    displayRequestDuration: true,
    defaultModelsExpandDepth: -1,
    docExpansion: 'none',
    filter: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset,
    ],
    plugins: [SwaggerUIBundle.plugins.DownloadUrl],
    layout: 'StandaloneLayout',
  });
};
</script>
</body>
</html>
