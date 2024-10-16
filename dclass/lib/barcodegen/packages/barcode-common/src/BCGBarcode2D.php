<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Base class for Barcode2D
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

abstract class BCGBarcode2D extends BCGBarcode
{
    protected int $scaleX;
    protected int $scaleY;            // ScaleX and Y multiplied by the scale

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->setScaleX(1);
        $this->setScaleY(1);
    }

    /**
     * Returns the maximal size of a barcode.
     *
     * @param int $width The width.
     * @param int $height The height.
     * @return int[] An array, [0] being the width, [1] being the height.
     */
    public function getDimension(int $width, int $height): array
    {
        return parent::getDimension($width * $this->scaleX, $height * $this->scaleY);
    }

    /**
     * Sets the scale of the barcode in pixel for X.
     * If the scale is lower than 1, an exception is raised.
     *
     * @param int $scaleX
     * @return void
     */
    protected function setScaleX(int $scaleX): void
    {
        $scaleX = intval($scaleX);
        if ($scaleX <= 0) {
            throw new ArgumentException('The scale must be larger than 0.', 'scaleX');
        }

        $this->scaleX = $scaleX;
    }

    /**
     * Sets the scale of the barcode in pixel for Y.
     * If the scale is lower than 1, an exception is raised.
     *
     * @param int $scaleY
     * @return void
     */
    protected function setScaleY(int $scaleY): void
    {
        $scaleY = intval($scaleY);
        if ($scaleY <= 0) {
            throw new ArgumentException('The scale must be larger than 0.', 'scaleY');
        }

        $this->scaleY = $scaleY;
    }

    /**
     * Draws the text.
     * The coordinate passed are the positions of the barcode.
     * $x1 and $y1 represent the top left corner.
     * $x2 and $y2 represent the bottom right corner.
     *
     * @param resource $image The surface.
     * @param int $x1 X1.
     * @param int $y1 Y1.
     * @param int $x2 X2.
     * @param int $y2 Y2.
     * @return void
     */
    protected function drawText($image, int $x1, int $y1, int $x2, int $y2): void
    {
        foreach ($this->labels as $label) {
            $label->draw(
                $image,
                ($x1 + $this->offsetX) * $this->scale * $this->scaleX + $this->pushLabel[0],
                ($y1 + $this->offsetY) * $this->scale * $this->scaleY + $this->pushLabel[1],
                ($x2 + $this->offsetX) * $this->scale * $this->scaleX + $this->pushLabel[0],
                ($y2 + $this->offsetY) * $this->scale * $this->scaleY + $this->pushLabel[1]
            );
        }
    }

    /**
     * Draws 1 pixel on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x X.
     * @param int $y Y.
     * @param int $color The color.
     * @return void
     */
    protected function drawPixel($image, int $x, int $y, int $color = self::COLOR_FG): void
    {
        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        $xR = ($x + $this->offsetX) * $scaleX + $this->pushLabel[0];
        $yR = ($y + $this->offsetY) * $scaleY + $this->pushLabel[1];

        // We always draw a rectangle
        imagefilledrectangle(
            $image,
            $xR,
            $yR,
            $xR + $scaleX - 1,
            $yR + $scaleY - 1,
            $this->getColor($image, $color)
        );
    }

    /**
     * Draws an empty rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x1 X1.
     * @param int $y1 Y1.
     * @param int $x2 X2.
     * @param int $y2 Y2.
     * @param int $color The color.
     * @return void
     */
    protected function drawRectangle($image, int $x1, int $y1, int $x2, int $y2, int $color = BCGBarcode::COLOR_FG): void
    {
        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        if ($this->scale === 1) {
            imagefilledrectangle(
                $image,
                ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0],
                ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1],
                ($x2 + $this->offsetX) * $scaleX + $this->pushLabel[0],
                ($y2 + $this->offsetY) * $scaleY + $this->pushLabel[1],
                $this->getColor($image, $color)
            );
        } else {
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($image, $color));
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x1 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($image, $color));
            imagefilledrectangle($image, ($x2 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($image, $color));
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $this->pushLabel[1], ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0], ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1], $this->getColor($image, $color));
        }
    }

    /**
     * Draws a filled rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x1 X1.
     * @param int $y1 Y1.
     * @param int $x2 X2.
     * @param int $y2 Y2.
     * @param int $color The color.
     * @return void
     */
    protected function drawFilledRectangle($image, int $x1, int $y1, int $x2, int $y2, int $color = BCGBarcode::COLOR_FG): void
    {
        if ($x1 > $x2) { // Swap
            $x1 ^= $x2 ^= $x1 ^= $x2;
        }

        if ($y1 > $y2) { // Swap
            $y1 ^= $y2 ^= $y1 ^= $y2;
        }

        $scaleX = $this->scale * $this->scaleX;
        $scaleY = $this->scale * $this->scaleY;

        imagefilledrectangle(
            $image,
            ($x1 + $this->offsetX) * $scaleX + $this->pushLabel[0],
            ($y1 + $this->offsetY) * $scaleY + $this->pushLabel[1],
            ($x2 + $this->offsetX) * $scaleX + $scaleX - 1 + $this->pushLabel[0],
            ($y2 + $this->offsetY) * $scaleY + $scaleY - 1 + $this->pushLabel[1],
            $this->getColor($image, $color)
        );
    }
}
