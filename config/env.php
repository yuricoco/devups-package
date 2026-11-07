<?php

/**
 * Chargeur minimal de fichier .env (aucune dependance).
 *
 * Lit ROOT/.env (KEY=valeur, une par ligne, # pour les commentaires,
 * guillemets simples ou doubles optionnels) et expose les valeurs via
 * dv_env(). Les variables deja definies dans l'environnement du processus
 * (serveur web, CI, docker) sont prioritaires sur le fichier.
 */

if (!function_exists('dv_env_load')) {

    function dv_env_load(string $file): array
    {
        $values = [];
        if (!is_file($file) || !is_readable($file))
            return $values;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false)
                continue;
            if (strpos($line, 'export ') === 0)
                $line = substr($line, 7);

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key === '')
                continue;

            $len = strlen($value);
            if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            } elseif (($hash = strpos($value, ' #')) !== false) {
                $value = rtrim(substr($value, 0, $hash));
            }
            $values[$key] = $value;
        }
        return $values;
    }

    /**
     * Valeur d'une variable d'environnement, convertie : "true"/"false"
     * deviennent des booleens, "null" devient null, "" renvoie $default.
     */
    function dv_env(string $key, $default = null)
    {
        global $__dv_env;
        if (!isset($__dv_env))
            $__dv_env = dv_env_load(__DIR__ . '/../.env');

        $value = getenv($key);
        if ($value === false)
            $value = $__dv_env[$key] ?? null;
        if ($value === null || $value === '')
            return $default;

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }
        return $value;
    }
}
