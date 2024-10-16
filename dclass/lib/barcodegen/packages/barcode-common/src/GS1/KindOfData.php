<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * GS1 Kind of data.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common\GS1;

/**
 * GS1 Kind of data.
 */
class KindOfData
{
    /**
     * The content is only numeric.
     */
    const NUMERIC = 1;

    /**
     * The content contains number and letters.
     */
    const ALPHA_NUMERIC = 2;

    /**
     * The content is of a date format yymmdd.
     */
    const DATE = 3;

    /**
     * The content is of a date and time format yymmddhhii or yymmddhhiiss.
     */
    const DATETIME = 4;
}
