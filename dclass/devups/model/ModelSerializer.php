<?php

namespace dclass\devups\model;
class ModelSerializer extends \stdClass
{

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
//        $data = get_object_vars($this);
        $data = [];
    //    foreach ($this as $key => $value)
    //         if ($value)
    //            $data[$key] = $value;

        foreach ($this->with as $key) {

            if (is_object($this->{$key})) {
//                var_dump($key, $this->with);
                $data[$key] = $this->{$key}->jsonSerialize();
            }
//            else if ($value)
//                $data[$key] = $value;

        }

        return $data+$this->dvserialised;
    }
    /*public function jsonSerialize()
    {
        return [
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }*/
        
    /**
     * create a new entry
     * @return integer
     */
    public function __insert()
    {
        if (isset($this->created_at))
            $this->created_at = date(\DClass\lib\Util::dateformat);

        $dbal = new \DBAL();
        \DBAL::$id_lang_static = $this->dvid_lang;
        $id = $dbal->createDbal($this);
        return $id;
    }

    /**
     * update a part or an entire entity
     * @example http://easyprod.spacekola.com description
     * @param Mixed $arrayvalues
     * @param Mixed $seton
     * @param Mixed $case
     * @return boolean | \QueryBuilder
     */
    public function __update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        $this->updated_at = date(\DClass\lib\Util::dateformat);
        $dbal = new \DBAL();
        \DBAL::$id_lang_static = $this->dvid_lang;
//        dv_dump($arrayvalues, $this->dvpersistence);
        if ($arrayvalues === null) {// && !$this->dvpersistence

            return $dbal->updateDbal($this);
        } else {
            $qb = new \QueryBuilder($this, false);
//            if ($arrayvalues)
            return $qb->whereId($this->id)->update($arrayvalues, $seton, $case);

//            return $qb->whereId($this->id)->update($this->dvpersistence, $seton, $case);
        }
    }
    
    public function __delete($exec = true)
    {
        if ($exec) {
            $dbal = new \DBAL();
            return $dbal->deleteDbal($this);
        } else {
            $qb = new \QueryBuilder($this);
            $qb->delete();
            return $qb;
        }
    }

    /**
     * static method gives the path of the module where the entity is/
     * @return string the path of the module where the class is.
     */
    public static function classpath($src = "", $route = __env)
    {
        $reflector = new \ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dirname = explode("src", dirname($fn));
        $dirname = str_replace("Entity", "", $dirname[1]);
        return str_replace("\\", "/", $route . "src" . $dirname . $src);
    }

}
