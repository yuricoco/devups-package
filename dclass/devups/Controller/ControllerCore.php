<?php


namespace dclass\devups\Controller;


class ControllerCore extends Controller
{
    use CrudTrait;

//    public function __construct()
//    {
//        parent::__construct();
//    }

    public static function init($entityname)
    {

        self::$entityname = $entityname;
        self::$classname = ucfirst(self::$entityname);
        // ETAPE 5.2 (v8.0) - LOT 9 : resolution namespace-aware, voir TODO.md.
        self::$tablename = \EntityNaming::tableFor(self::$classname);
        self::$formname = \EntityNaming::formFor(self::$classname);
        self::$ctrlname = self::$classname . "Ctrl";

        return new ControllerCore();

    }

}