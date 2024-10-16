<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Interface for a font.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

interface BCGFont
{
    public function getText(): string;
    public function setText(string $text): void;
    public function getRotationAngle(): int;
    public function setRotationAngle(int $rotationDegree): void;
    public function getBackgroundColor(): BCGColor;
    public function setBackgroundColor(BCGColor $backgroundColor): void;
    public function getForegroundColor(): BCGColor;
    public function setForegroundColor(BCGColor $foregroundColor): void;
    public function getDimension(): array;
    public function draw($image, int $x, int $y): void;
}
