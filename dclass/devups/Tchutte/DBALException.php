<?php

namespace dclass\devups\Tchutte;

/**
 * Erreur SQL levee par DBAL. Porte la requete et ses parametres pour le
 * diagnostic ; hors production, ils sont aussi repris dans le message.
 */
class DBALException extends \RuntimeException
{

    public $sql;
    public $param;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?string $sql = null, array $param = [])
    {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->param = $param;
    }

    public static function fromThrowable(\Throwable $e, ?string $sql = null, array $param = []): self
    {
        $message = $e->getMessage();
        if (defined('__prod') && !__prod && $sql)
            $message .= "\nSQL: " . $sql . ($param ? "\nParams: " . json_encode($param) : '');
        return new self($message, (int)$e->getCode(), $e, $sql, $param);
    }

}
