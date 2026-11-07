<?php

namespace dclass\devups\Tchutte;
class Column
{

    /**
     * @var mixed
     */
    public $column;

    public function __construct($column)
    {
        $this->column = $column;
    }

    public static function init($column){
        $c = new Column($column);
        return $c;
    }

}