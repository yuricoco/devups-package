<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Class for Label
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGLabel
{
    const POSITION_TOP = 0;
    const POSITION_RIGHT = 1;
    const POSITION_BOTTOM = 2;
    const POSITION_LEFT = 3;

    const ALIGN_LEFT = 0;
    const ALIGN_TOP = 0;
    const ALIGN_CENTER = 1;
    const ALIGN_RIGHT = 2;
    const ALIGN_BOTTOM = 2;

    private BCGFont $font;
    private string $text = '';
    private int $position = 0;
    private int $alignment = 0;
    private int $offset = 0;
    private int $spacing = 0;
    private int $rotationAngle = 0;
    private BCGColor $backgroundColor;
    private BCGColor $foregroundColor;

    /**
     * Constructor.
     *
     * @param string $text The text.
     * @param BCGFont $font The font.
     * @param int $position The position.
     * @param int $alignment The alignment.
     */
    public function __construct(string $text = '', ?BCGFont $font = null, int $position = self::POSITION_BOTTOM, int $alignment = self::ALIGN_CENTER)
    {
        $this->font = $font === null ? new BCGFontPhp(5) : $font;
        $this->setText($text);
        $this->setPosition($position);
        $this->setAlignment($alignment);
        $this->setSpacing(4);
        $this->setOffset(0);
        $this->setRotationAngle(0);
        $this->setBackgroundColor(new BCGColor(0xffffff));
        $this->setForegroundColor(new BCGColor(0x000000));
        $this->setFont($this->font);
    }

    /**
     * Gets the text.
     *
     * @return string The text.
     */
    public function getText(): string
    {
        return $this->font->getText();
    }

    /**
     * Sets the text.
     *
     * @param string $text The text.
     * @return void
     */
    public function setText(string $text): void
    {
        $this->text = $text;
        $this->font->setText($this->text);
    }

    /**
     * Gets the font.
     *
     * @return BCGFont The font.
     */
    public function getFont(): BCGFont
    {
        return $this->font;
    }

    /**
     * Sets the font.
     *
     * @param BCGFont $font The font.
     * @return void
     */
    public function setFont(BCGFont $font): void
    {
        if ($font === null) {
            throw new BCGArgumentException('Font cannot be null.', 'font');
        }

        $this->font = clone $font;
        $this->font->setText($this->text);
        $this->font->setRotationAngle($this->rotationAngle);
        $this->font->setBackgroundColor($this->backgroundColor);
        $this->font->setForegroundColor($this->foregroundColor);
    }

    /**
     * Gets the text position for drawing.
     *
     * @return int The position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Sets the text position for drawing.
     *
     * @param int $position The position.
     * @return void
     */
    public function setPosition(int $position): void
    {
        $position = intval($position);
        if ($position !== self::POSITION_TOP && $position !== self::POSITION_RIGHT && $position !== self::POSITION_BOTTOM && $position !== self::POSITION_LEFT) {
            throw new BCGArgumentException('The text position must be one of a valid constant.', 'position');
        }

        $this->position = $position;
    }

    /**
     * Gets the text alignment for drawing.
     *
     * @return int The alignment.
     */
    public function getAlignment(): int
    {
        return $this->alignment;
    }

    /**
     * Sets the text alignment for drawing.
     *
     * @param int $alignment The alignment.
     * @return void
     */
    public function setAlignment(int $alignment): void
    {
        $alignment = intval($alignment);
        if ($alignment !== self::ALIGN_LEFT && $alignment !== self::ALIGN_TOP && $alignment !== self::ALIGN_CENTER && $alignment !== self::ALIGN_RIGHT && $alignment !== self::ALIGN_BOTTOM) {
            throw new BCGArgumentException('The text alignment must be one of a valid constant.', 'alignment');
        }

        $this->alignment = $alignment;
    }

    /**
     * Gets the offset.
     *
     * @return int The offset.
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Sets the offset.
     *
     * @param int $offset The offset.
     * @return void
     */
    public function setOffset(int $offset): void
    {
        $this->offset = intval($offset);
    }

    /**
     * Gets the spacing.
     *
     * @return int The spacing.
     */
    public function getSpacing(): int
    {
        return $this->spacing;
    }

    /**
     * Sets the spacing.
     *
     * @param int $spacing The spacing.
     * @return void
     */
    public function setSpacing(int $spacing): void
    {
        $this->spacing = max(0, intval($spacing));
    }

    /**
     * Gets the rotation angle in degree.
     *
     * @return int The rotation angle.
     */
    public function getRotationAngle(): int
    {
        return $this->font->getRotationAngle();
    }

    /**
     * Sets the rotation angle in degree.
     *
     * @param int $rotationAngle The rotation angle.
     * @return void
     */
    public function setRotationAngle(int $rotationAngle): void
    {
        $this->rotationAngle = intval($rotationAngle);
        $this->font->setRotationAngle($this->rotationAngle);
    }

    /**
     * Gets the background color in case of rotation.
     *
     * @return BCGColor The background color.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets the background color in case of rotation.
     *
     * @param BCGColor $backgroundColor The background color.
     * @return void
     */
    public function setBackgroundColor(BCGColor $backgroundColor): void
    {
        $this->backgroundColor = $backgroundColor;
        $this->font->setBackgroundColor($this->backgroundColor);
    }

    /**
     * Gets the foreground color.
     *
     * @return BCGColor The foreground color.
     */
    public function getForegroundColor()
    {
        return $this->font->getForegroundColor();
    }

    /**
     * Sets the foreground color.
     *
     * @param BCGColor $foregroundColor The foreground color.
     * @return void
     */
    public function setForegroundColor(BCGColor $foregroundColor): void
    {
        $this->foregroundColor = $foregroundColor;
        $this->font->setForegroundColor($this->foregroundColor);
    }

    /**
     * Gets the dimension taken by the label, including the spacing and offset.
     * [0]: width
     * [1]: height
     *
     * @return int[] The dimension.
     */
    public function getDimension(): array
    {
        $w = 0;
        $h = 0;

        $dimension = $this->font->getDimension();
        $w = $dimension[0];
        $h = $dimension[1];

        if ($this->position === self::POSITION_TOP || $this->position === self::POSITION_BOTTOM) {
            $h += $this->spacing;
            $w += max(0, $this->offset);
        } else {
            $w += $this->spacing;
            $h += max(0, $this->offset);
        }

        return array($w, $h);
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
    public function draw($image, int $x1, int $y1, int $x2, int $y2): void
    {
        $x = 0;
        $y = 0;

        $fontDimension = $this->font->getDimension();

        if ($this->position === self::POSITION_TOP || $this->position === self::POSITION_BOTTOM) {
            if ($this->position === self::POSITION_TOP) {
                $y = $y1 - $this->spacing - $fontDimension[1];
            } elseif ($this->position === self::POSITION_BOTTOM) {
                $y = $y2 + $this->spacing;
            }

            if ($this->alignment === self::ALIGN_CENTER) {
                $x = (int)(($x2 - $x1) / 2 + $x1 - $fontDimension[0] / 2 + $this->offset);
            } elseif ($this->alignment === self::ALIGN_LEFT) {
                $x = $x1 + $this->offset;
            } else {
                $x = $x2 + $this->offset - $fontDimension[0];
            }
        } else {
            if ($this->position === self::POSITION_LEFT) {
                $x = $x1 - $this->spacing - $fontDimension[0];
            } elseif ($this->position === self::POSITION_RIGHT) {
                $x = $x2 + $this->spacing;
            }

            if ($this->alignment === self::ALIGN_CENTER) {
                $y = (int)(($y2 - $y1) / 2 + $y1 - $fontDimension[1] / 2 + $this->offset);
            } elseif ($this->alignment === self::ALIGN_TOP) {
                $y = $y1 + $this->offset;
            } else {
                $y = $y2 + $this->offset - $fontDimension[1];
            }
        }

        $this->font->setText($this->text);
        $this->font->draw($image, $x, $y);
    }
}
