<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Parse Exception
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGParseException extends \Exception
{
    protected string $barcode;

    /**
     * Constructor with specific message for a barcode.
     *
     * @param string $barcode The barcode name.
     * @param string $message The message.
     */
    public function __construct(string $barcode, string $message)
    {
        $this->barcode = $barcode;
        parent::__construct($message, 10000);
    }
}
