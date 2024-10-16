<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Base class for Barcode 1D and 2D
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

use BarcodeBakery\Common\BCGArgumentException;
use BarcodeBakery\Common\BCGDrawException;

abstract class BCGBarcode
{
    const COLOR_BG = 0;
    const COLOR_FG = 1;

    protected BCGColor $colorFg;
    protected BCGColor $colorBg;
    protected int $scale;                   // Scale of the graphic, default: 1
    protected int $offsetX;
    protected int $offsetY;       // Position where to start the drawing
    protected array $labels = array();        // Array of BCGLabel
    protected array $pushLabel = array(0, 0); // Push for the label, left and top

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->setOffsetX(0);
        $this->setOffsetY(0);
        $this->setForegroundColor(0x000000);
        $this->setBackgroundColor(0xffffff);
        $this->setScale(1);
    }

    /**
     * Call this method if you are using the commercial version of our software.
     *
     * @return void
     */
    public function useCommercialVersion(): void
    {
        throw new BCGArgumentException('You are using the non-commercial library. You must purchase a license at https://www.barcodebakery.com in order to use this in a commercial environment. If you have purchased the library and still obtain this message, follow the documentation on our website.', 'free');
    }

    /**
     * Parses the text before displaying it.
     *
     * @param mixed $text The text.
     */
    public function parse($text): void
    {
    }

    /**
     * Gets the foreground color of the barcode.
     *
     * @return BCGColor The foreground color.
     */
    public function getForegroundColor(): BCGColor
    {
        return $this->colorFg;
    }

    /**
     * Sets the foreground color of the barcode. It could be a BCGColor
     * value or simply a language code (white, black, yellow...) or hex value.
     *
     * @param BCGColor|int $code The foreground color.
     * @return void
     */
    public function setForegroundColor($code): void
    {
        if ($code instanceof BCGColor) {
            $this->colorFg = $code;
        } else {
            $this->colorFg = new BCGColor($code);
        }
    }

    /**
     * Gets the background color of the barcode.
     *
     * @return BCGColor The background color.
     */
    public function getBackgroundColor(): BCGColor
    {
        return $this->colorBg;
    }

    /**
     * Sets the background color of the barcode. It could be a BCGColor
     * value or simply a language code (white, black, yellow...) or hex value.
     *
     * @param BCGColor|int $code The background color.
     * @return void
     */
    public function setBackgroundColor($code): void
    {
        if ($code instanceof BCGColor) {
            $this->colorBg = $code;
        } else {
            $this->colorBg = new BCGColor($code);
        }

        foreach ($this->labels as $label) {
            $label->setBackgroundColor($this->colorBg);
        }
    }

    /**
     * Sets the foreground and background color.
     *
     * @param BCGColor|int $foregroundColor The foreground color.
     * @param BCGColor|int $backgroundColor The background color.
     */
    public function setColor($foregroundColor, $backgroundColor): void
    {
        $this->setForegroundColor($foregroundColor);
        $this->setBackgroundColor($backgroundColor);
    }

    /**
     * Gets the scale of the barcode.
     *
     * @return int The scale.
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Sets the scale of the barcode in pixel.
     * If the scale is lower than 1, an exception is raised.
     *
     * @param int $scale The scale.
     * @return void
     */
    public function setScale(int $scale): void
    {
        $scale = intval($scale);
        if ($scale <= 0) {
            throw new BCGArgumentException('The scale must be larger than 0.', 'scale');
        }

        $this->scale = $scale;
    }

    /**
     * Abstract method that draws the barcode on the resource.
     *
     * @param resource $image The surface.
     * @return void
     */
    abstract public function draw($image): void;

    /**
     * Returns the maximal size of a barcode.
     * [0]->width
     * [1]->height
     *
     * @param int $width The width.
     * @param int $height The height.
     * @return int[] An array, [0] being the width, [1] being the height.
     */
    public function getDimension(int $width, int $height): array
    {
        $labels = $this->getBiggestLabels(false);
        $pixelsAround = array(0, 0, 0, 0); // TRBL
        if (isset($labels[BCGLabel::POSITION_TOP])) {
            $dimension = $labels[BCGLabel::POSITION_TOP]->getDimension();
            $pixelsAround[0] += $dimension[1];
        }

        if (isset($labels[BCGLabel::POSITION_RIGHT])) {
            $dimension = $labels[BCGLabel::POSITION_RIGHT]->getDimension();
            $pixelsAround[1] += $dimension[0];
        }

        if (isset($labels[BCGLabel::POSITION_BOTTOM])) {
            $dimension = $labels[BCGLabel::POSITION_BOTTOM]->getDimension();
            $pixelsAround[2] += $dimension[1];
        }

        if (isset($labels[BCGLabel::POSITION_LEFT])) {
            $dimension = $labels[BCGLabel::POSITION_LEFT]->getDimension();
            $pixelsAround[3] += $dimension[0];
        }

        $finalW = ($width + $this->offsetX) * $this->scale;
        $finalH = ($height + $this->offsetY) * $this->scale;

        // This section will check if a top/bottom label is too big for its width and left/right too big for its height
        $reversedLabels = $this->getBiggestLabels(true);
        foreach ($reversedLabels as $label) {
            $dimension = $label->getDimension();
            $alignment = $label->getAlignment();
            if ($label->getPosition() === BCGLabel::POSITION_LEFT || $label->getPosition() === BCGLabel::POSITION_RIGHT) {
                if ($alignment === BCGLabel::ALIGN_TOP) {
                    $pixelsAround[2] = max($pixelsAround[2], $dimension[1] - $finalH);
                } elseif ($alignment === BCGLabel::ALIGN_CENTER) {
                    $temp = (int)ceil(($dimension[1] - $finalH) / 2);
                    $pixelsAround[0] = max($pixelsAround[0], $temp);
                    $pixelsAround[2] = max($pixelsAround[2], $temp);
                } elseif ($alignment === BCGLabel::ALIGN_BOTTOM) {
                    $pixelsAround[0] = max($pixelsAround[0], $dimension[1] - $finalH);
                }
            } else {
                if ($alignment === BCGLabel::ALIGN_LEFT) {
                    $pixelsAround[1] = max($pixelsAround[1], $dimension[0] - $finalW);
                } elseif ($alignment === BCGLabel::ALIGN_CENTER) {
                    $temp = (int)ceil(($dimension[0] - $finalW) / 2);
                    $pixelsAround[1] = max($pixelsAround[1], $temp);
                    $pixelsAround[3] = max($pixelsAround[3], $temp);
                } elseif ($alignment === BCGLabel::ALIGN_RIGHT) {
                    $pixelsAround[3] = max($pixelsAround[3], $dimension[0] - $finalW);
                }
            }
        }

        $this->pushLabel[0] = $pixelsAround[3];
        $this->pushLabel[1] = $pixelsAround[0];

        $finalW = ($width + $this->offsetX) * $this->scale + $pixelsAround[1] + $pixelsAround[3];
        $finalH = ($height + $this->offsetY) * $this->scale + $pixelsAround[0] + $pixelsAround[2];

        return array((int)$finalW, (int)$finalH);
    }

    /**
     * Gets the X offset.
     *
     * @return int The X offset.
     */
    public function getOffsetX(): int
    {
        return $this->offsetX;
    }

    /**
     * Sets the X offset.
     *
     * @param int $offsetX The X offset.
     * @return void
     */
    public function setOffsetX(int $offsetX): void
    {
        $offsetX = intval($offsetX);
        if ($offsetX < 0) {
            throw new BCGArgumentException('The offset X must be 0 or larger.', 'offsetX');
        }

        $this->offsetX = $offsetX;
    }

    /**
     * Gets the Y offset.
     *
     * @return int The Y offset.
     */
    public function getOffsetY(): int
    {
        return $this->offsetY;
    }

    /**
     * Sets the Y offset.
     *
     * @param int $offsetY The Y offset.
     * @return void
     */
    public function setOffsetY(int $offsetY): void
    {
        $offsetY = intval($offsetY);
        if ($offsetY < 0) {
            throw new BCGArgumentException('The offset Y must be 0 or larger.', 'offsetY');
        }

        $this->offsetY = $offsetY;
    }

    /**
     * Adds the label to the drawing.
     *
     * @param BCGLabel $label The label.
     * @return void
     */
    public function addLabel(BCGLabel $label): void
    {
        $label->setBackgroundColor($this->colorBg);
        $this->labels[] = $label;
    }

    /**
     * Removes the label from the drawing.
     *
     * @param BCGLabel $label The label.
     * @return void
     */
    public function removeLabel(BCGLabel $label): void
    {
        $remove = -1;
        $c = count($this->labels);
        for ($i = 0; $i < $c; $i++) {
            if ($this->labels[$i] === $label) {
                $remove = $i;
                break;
            }
        }

        if ($remove > -1) {
            array_splice($this->labels, $remove, 1);
        }
    }

    /**
     * Gets the labels.
     *
     * @return BCGLabel[] The labels.
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Clears the labels.
     *
     * @return void
     */
    public function clearLabels(): void
    {
        $this->labels = array();
    }

    /**
     * Draws the text.
     * The coordinate passed are the positions of the barcode.
     * $x1 and $y1 represent the top left corner.
     * $x2 and $y2 represent the bottom right corner.
     *
     * @param resource $image The surface.
     * @param int $x1 The top left corner X coordinate.
     * @param int $y1 The top left corner Y coordinate.
     * @param int $x2 The bottom right corner X coordinate.
     * @param int $y2 The bottom right corner Y coordinate.
     * @return void
     */
    protected function drawText($image, int $x1, int $y1, int $x2, int $y2): void
    {
        foreach ($this->labels as $label) {
            $label->draw(
                $image,
                ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0],
                ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1],
                ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0],
                ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1]
            );
        }
    }

    /**
     * Draws 1 pixel on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x The X coordinate.
     * @param int $y The Y coordinate.
     * @param int $color The color.
     * @return void
     */
    protected function drawPixel($image, int $x, int $y, int $color = self::COLOR_FG): void
    {
        $xR = ($x + $this->offsetX) * $this->scale + $this->pushLabel[0];
        $yR = ($y + $this->offsetY) * $this->scale + $this->pushLabel[1];

        // We always draw a rectangle
        imagefilledrectangle(
            $image,
            $xR,
            $yR,
            $xR + $this->scale - 1,
            $yR + $this->scale - 1,
            $this->getColor($image, $color)
        );
    }

    /**
     * Draws an empty rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x1 The top left corner X coordinate.
     * @param int $y1 The top left corner Y coordinate.
     * @param int $x2 The bottom right corner X coordinate.
     * @param int $y2 The bottom right corner Y coordinate.
     * @param int $color The color.
     * @return void
     */
    protected function drawRectangle($image, int $x1, int $y1, int $x2, int $y2, int $color = self::COLOR_FG): void
    {
        if ($this->scale === 1) {
            imagefilledrectangle(
                $image,
                ($x1 + $this->offsetX) + $this->pushLabel[0],
                ($y1 + $this->offsetY) + $this->pushLabel[1],
                ($x2 + $this->offsetX) + $this->pushLabel[0],
                ($y2 + $this->offsetY) + $this->pushLabel[1],
                $this->getColor($image, $color)
            );
        } else {
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0], ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1], ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0] + $this->scale - 1, ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1] + $this->scale - 1, $this->getColor($image, $color));
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0], ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1], ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0] + $this->scale - 1, ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1] + $this->scale - 1, $this->getColor($image, $color));
            imagefilledrectangle($image, ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0], ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1], ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0] + $this->scale - 1, ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1] + $this->scale - 1, $this->getColor($image, $color));
            imagefilledrectangle($image, ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0], ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1], ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0] + $this->scale - 1, ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1] + $this->scale - 1, $this->getColor($image, $color));
        }
    }

    /**
     * Draws a filled rectangle on the resource at a specific position with a determined color.
     *
     * @param resource $image The surface.
     * @param int $x1 The top left corner X coordinate.
     * @param int $y1 The top left corner Y coordinate.
     * @param int $x2 The bottom right corner X coordinate.
     * @param int $y2 The bottom right corner Y coordinate.
     * @param int $color The color.
     * @return void
     */
    protected function drawFilledRectangle($image, int $x1, int $y1, int $x2, int $y2, int $color = self::COLOR_FG): void
    {
        if ($x1 > $x2) { // Swap
            $x1 ^= $x2 ^= $x1 ^= $x2;
        }

        if ($y1 > $y2) { // Swap
            $y1 ^= $y2 ^= $y1 ^= $y2;
        }

        imagefilledrectangle(
            $image,
            ($x1 + $this->offsetX) * $this->scale + $this->pushLabel[0],
            ($y1 + $this->offsetY) * $this->scale + $this->pushLabel[1],
            ($x2 + $this->offsetX) * $this->scale + $this->pushLabel[0] + $this->scale - 1,
            ($y2 + $this->offsetY) * $this->scale + $this->pushLabel[1] + $this->scale - 1,
            $this->getColor($image, $color)
        );
    }

    /**
     * Allocates the color based on the integer.
     *
     * @param resource $image The surface.
     * @param int $color The color.
     * @return resource Implementation details of the color.
     */
    protected function getColor($image, int $color)
    {
        if ($color === self::COLOR_BG) {
            return $this->colorBg->allocate($image);
        } else {
            return $this->colorFg->allocate($image);
        }
    }

    /**
     * Returning the biggest label widths for LEFT/RIGHT and heights for TOP/BOTTOM.
     *
     * @param bool $reversed Indicates if the barcode has been rotated.
     * @return BCGLabel[] Position of the biggest barcode.
     */
    private function getBiggestLabels(bool $reversed = false): array
    {
        $searchLR = $reversed ? 1 : 0;
        $searchTB = $reversed ? 0 : 1;

        $labels = array();
        foreach ($this->labels as $label) {
            $position = $label->getPosition();
            if (isset($labels[$position])) {
                $savedDimension = $labels[$position]->getDimension();
                $dimension = $label->getDimension();
                if ($position === BCGLabel::POSITION_LEFT || $position === BCGLabel::POSITION_RIGHT) {
                    if ($dimension[$searchLR] > $savedDimension[$searchLR]) {
                        $labels[$position] = $label;
                    }
                } else {
                    if ($dimension[$searchTB] > $savedDimension[$searchTB]) {
                        $labels[$position] = $label;
                    }
                }
            } else {
                $labels[$position] = $label;
            }
        }

        return $labels;
    }
}
