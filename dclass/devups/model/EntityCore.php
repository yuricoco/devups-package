<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EntityCore
 *
 * @author azankang
 */
class EntityCore
{
    //put your code here
    public function __construct($entity)
    {
        return get_class($entity);
    }

    public static function __extract($__DIR__, $entity, $asarray = false)
    {

        $path = $__DIR__ . '../Core/' . strtolower(get_class($entity)) . '.json';

        $json_file_content = fopen(__DIR__ . $path, 'r+');
        if ($asarray)
            $entitycore = json_decode(fgets($json_file_content), true);
        else
            $entitycore = json_decode(fgets($json_file_content));
        fclose($json_file_content);

        return $entitycore;
    }

    public static function parseEntity($input)
    {
        $withs = '';

        // Séparation des relations
        if (($pos = strpos($input, ':')) !== false) {
            $withs = substr($input, $pos + 1);
            $input = substr($input, 0, $pos);
        }

        // Classe + entité
        $classname = $input;
        $entityDef = $input;

        if (($pos = strpos($input, '\\')) !== false) {
            $classname = substr($input, 0, $pos);
            $entityDef = substr($input, $pos + 1);
        }

        // Entité + attributs
        $entity = $entityDef;
        $attributes = [];
        $relations = [];

        if (($pos = strpos($entityDef, '[')) !== false) {
            $entity = substr($entityDef, 0, $pos);

            $end = strrpos($entityDef, ']');
            $fields = substr($entityDef, $pos + 1, $end - $pos - 1);

            foreach (explode(',', $fields) as $field) {
                $field = trim($field);

                if ($field !== '') {
                    $attributes[] = $field;
                }
            }
        }

        // Pas de préfixe => classname = entity
        if ($classname === $entityDef) {
            $classname = $entity;
        }

        // Relations simples
        if ($withs !== '') {

            foreach (explode(',', $withs) as $relationDef) {

                $relationDef = trim($relationDef);

                if ($relationDef === '') {
                    continue;
                }

                $pos = strpos($relationDef, '[');

                if ($pos === false) {
                    continue;
                }

                $relation = substr($relationDef, 0, $pos);

                $end = strrpos($relationDef, ']');

                $relationFields = substr(
                    $relationDef,
                    $pos + 1,
                    $end - $pos - 1
                );

                //$attributes[] = $relation . '_id';

                $relations[$relation] = array_map(
                    'trim',
                    explode(',', $relationFields)
                );
            }
        }

        return [
            'classentity' => $classname,
            'entity'    => $entity,
            'attributes' => $attributes,
            'relations' => $relations,
        ];
    }
}
