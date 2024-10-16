<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Draw Exception
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGDrawException extends \Exception
{
    /**
     * Constructor with specific message.
     *
     * @param string $message The message.
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 30000);
    }
}
