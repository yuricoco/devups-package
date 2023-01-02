<?php

namespace dclass\devups\Tchutte;

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class DBALException extends \Exception
{

    public $sql;
    public $param;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

    }

}
