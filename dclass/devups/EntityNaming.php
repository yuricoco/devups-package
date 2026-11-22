<?php

/**
 * ETAPE 5.2 (v8.0) - LOT 9 : point de verite UNIQUE pour deriver le nom
 * des classes "satellites" d'une entite (Controller/FrontController,
 * Datatable, Form) depuis le nom de l'entite - remplace la convention
 * par concatenation de chaine jusque-la dupliquee independamment dans
 * Controller.php, ControllerCore.php et FrontController.php (voir
 * TODO.md "LOT 9" pour le detail de chaque site corrige).
 *
 * Accepte en entree aussi bien un nom court historique ("Oauth_client",
 * jamais namespace) qu'une FQCN namespacee ("devupsfront\ModuleUser\
 * Entity\Oauth_client") - les deux doivent continuer a fonctionner
 * indefiniment (meme garantie que class_alias, voir UPGRADE-8.0.md LOT
 * 5 : "aucune depreciation n'est prevue"). La classe satellite namespacee
 * n'existe pas forcement encore (entite migree mais pas (encore) son
 * Controller/Table/Form) : dans ce cas on retombe simplement sur l'ancien
 * nom court global, jamais d'erreur ici - resolution volontairement
 * permissive, le "class not found" eventuel reste la responsabilite de
 * l'appelant (comme avant ce LOT).
 */
class EntityNaming
{
    public static function controllerFor($entity, bool $front = false): string
    {
        return self::resolve($entity, 'Controller', $front ? 'FrontController' : 'Controller');
    }

    public static function tableFor($entity): string
    {
        return self::resolve($entity, 'Datatable', 'Table');
    }

    public static function formFor($entity): string
    {
        return self::resolve($entity, 'Form', 'Form');
    }

    private static function resolve($entity, string $kind, string $suffix): string
    {
        $entityClass = ltrim((string) $entity, '\\');

        $pos = strrpos($entityClass, '\\');
        if ($pos === false) {
            // Nom court : cherche la FQCN connue de cette entite dans la
            // config generee (meme source que Core::buildOriginCore()
            // pour "namespace_name") avant de se rabattre sur l'ancien
            // comportement (nom court global, toujours valide).
            $known = self::namespaceNameFor($entityClass);
            if ($known !== null && strpos($known, '\\') !== false)
                return self::resolve($known, $kind, $suffix);

            return $entityClass . $suffix;
        }

        $namespace = substr($entityClass, 0, $pos);
        $short = substr($entityClass, $pos + 1);
        $nsParts = explode('\\', $namespace);
        if (array_pop($nsParts) !== 'Entity')
            // Convention non respectee (ne devrait pas arriver pour une
            // entite Devups) - repli sur le nom court global.
            return $short . $suffix;

        $nsParts[] = $kind;
        $candidate = implode('\\', $nsParts) . '\\' . $short . $suffix;

        return class_exists($candidate) ? $candidate : ($short . $suffix);
    }

    private static function namespaceNameFor(string $shortName): ?string
    {
        static $config = null;
        if ($config === null)
            $config = @include ROOT . 'config/dvups_configurations.php';
        if (!is_array($config))
            return null;

        return $config[$shortName]['namespace_name']
            ?? $config[ucfirst($shortName)]['namespace_name']
            ?? null;
    }
}
