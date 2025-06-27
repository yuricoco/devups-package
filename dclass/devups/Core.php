<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Core
 *
 * @author azankang
 */
class Core extends stdClass
{

    public function __construct($entity)
    {
        $this->entity = $entity;
        $this->classname = get_class($entity);
        $this->name = strtolower(get_class($entity));
        $this->addjs = [];
        return $this;
    }

    public function addDformjs($action = true)
    {
        if ($action) $this->addjs[] = CLASSJS . "dform";
    }

    public function addjs($js, $path = "")
    {
        $this->addjs[] = $path . $js;//.".js";
    }

    public function addcss($css, $path = "")
    {
        $this->addcss[] = $path . $css;//.".css";
    }

    public static function __extract($entity, $asarray = false)
    {

        global $enittycollection;

        $entityname = strtolower(get_class($entity));
        $path = $enittycollection[$entityname] . '/Core/' . $entityname . 'Core.json';
//        $path = $__DIR__ . '/../Core/' . ucfirst(get_class($entity)) . 'Core.json';

        $json_file_content = file_get_contents($path);

        if ($asarray)
            $entitycore = json_decode($json_file_content, true);
        else
            $entitycore = json_decode($json_file_content);

        $entitycore->instance = $entity;
        $entitycore->name = strtolower($entitycore->name);

        return $entitycore;
    }

    public static function findprojectcore($dir, $file, $next = null)
    {
//            var_dump($dir."/".strtolower($file) . ".json");
        if (!file_exists($dir . "/" . strtolower($file) . "Core.json"))
            return [];

        $files = array_diff(scandir($dir), array('.', '..'));
        $modulecores = [];

        $projectcore = json_decode(file_get_contents($dir . "/" . strtolower($file) . "Core.json"));

        if (is_callable($next))
            if (!$next($projectcore->name, 'component'))
                return null;

        $load = true;
        foreach ($files as $file) {

            if (is_dir($dir . "/" . $file)) {

//                if (!file_exists($dir . "/" . strtolower($file) . "Core.json")){
                if ($module = Core::findmodulecore($dir . "/" . $file, $file, $next))
                    $modulecores[] = $module;
//                }

            }
        }
//        dv_dump($modulecores);
        $projectcore->listmodule = $modulecores;

        return $projectcore;
    }

    public static function findmodulecore($dir, $file, $next = null)
    {
        if (!file_exists($dir . "/" . strtolower($file) . "Core.json"))
            return [];

        $modulecore = json_decode(file_get_contents($dir . "/" . strtolower($file) . "Core.json"));

        if (is_callable($next))
            if (!$next($modulecore->name, 'module'))
                return null;

        $entitycores = Core::findentitycore($dir . "/Core", $next);

        $modulecore->listentity = $entitycores;

        return $modulecore;
    }

    public static function findentitycore($dir, $next = null)
    {
        if (!file_exists($dir))
            return [];

        $entitycores = [];
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_callable($next)) {
                if ($entity = $next(str_replace("Core.json", "", $file), 'entity'))
                    $entitycores[] = $entity;
//                else
//                    continue;
            }elseif (!is_dir($dir . "/" . $file)) {
                $entitycores[] = json_decode(file_get_contents($dir . "/" . $file));
            }
        }
        return $entitycores;

    }

    public static function buildOriginCore($next = null)
    {

        $dir = __DIR__ . '/../../src';
        $navigation = [];
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));

            foreach ($files as $file) {
                if ($file != "requires.php")
                    if ($nav = Core::findprojectcore($dir . "/" . $file, $file, $next))
                        $navigation[] = $nav;

            }

            return $navigation;
        } else {
            return [];
        }
    }

    public static function getComponentCore($component)
    {

        $dir = __DIR__ . '/../../src';

        if (file_exists($dir . "/" . $component)) {
            if (file_exists($dir))
                return json_decode(file_get_contents($dir . "/" . $component . "/" . $component . "Core.json"));
            else
                return null;

        } else {
            return null;
        }
    }

    public static function updateDvupsTable()
    {
        $updated = false;
        $global_navigation = Core::buildOriginCore();

        $dvups_configurations = [];
        $comps = [];
        $mods = [];
        $ents = [];
//        $lang_isos = Dvups_lang::getLangIso();
        foreach ($global_navigation as $key => $project) {
            if (is_object($project)) {
                $comps[] = $project->name;
                $projectname = ($project->name);

                foreach ($project->listmodule as $key => $module) {
                    //foreach ([] as $key => $module) {

                    if (!is_object($module)) {
                        continue;
                    }
                    $modulename = ucfirst($module->name);
                    $mods[] = $modulename;
                    $entities = [];
                    foreach ($module->listentity as $key => $entity) {

                        $entityname = ucfirst($entity->name);
                        $ents[] = $entity->name;

                        $dvups_configurations["$projectname\\$modulename\\Entity\\" . $entityname] = [
                            "path" => "$projectname/$modulename/",
                            "module" => $modulename,
                            "name" => $entityname,
                            "namespace" => "$projectname\\$modulename\\Entity\\",
                            "namespace_name" => "$projectname\\$modulename\\Entity\\$entityname",
                            "component" => $projectname,
                        ];
                        $dvups_configurations["" . $entityname] = [
                            "path" => "$projectname/$modulename/",
                            "module" => $modulename,
                            "name" => $entityname,
                            "namespace" => "$projectname\\$modulename\\Entity\\",
                            "namespace_name" => "$projectname\\$modulename\\Entity\\$entityname",
                            "component" => $projectname,
                        ];

                        $entities[] = [
                            "path" => "$projectname/$modulename/",
                            "module" => $modulename,
                            "name" => $entityname,
                            "namespace" => "$projectname\\$modulename\\Entity\\",
                            "component" => $projectname,
                        ];

                    }

                    $module_configurations[$modulename] = [
                        'name' => $modulename,
                        'entities' => $entities,
                    ];

                }
            }
        }

        \DClass\lib\Util::arrayToPhpFile($module_configurations, "module_configurations", 'config/');
        \DClass\lib\Util::arrayToPhpFile($dvups_configurations, "dvups_configurations", 'config/');

        $role = Dvups_role::find(1);
        $role->setComponents($comps);
        $role->setModules($mods);
        $role->setEntities($ents);
        $role->updateConfigs();
        $role->__update();

        return $updated;

    }

}
