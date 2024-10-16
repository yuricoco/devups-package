<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Holds font for PHP.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGFontPhp implements BCGFont
{
    private int $font;
    private string $text;
    private int $rotationAngle;
    private BCGColor $backgroundColor;
    private BCGColor $foregroundColor;

    /**
     * Constructor.
     *
     * @param int $font The font.
     */
    public function __construct($font)
    {
        $this->font = max(0, intval($font));
        $this->backgroundColor = new BCGColor(0xffffff);
        $this->foregroundColor = new BCGColor(0x000000);
        $this->setRotationAngle(0);
    }

    /**
     * Gets the text associated to the font.
     *
     * @return string The text.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Sets the text associated to the font.
     *
     * @param string text The text.
     * @return void
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * Gets the rotation in degree.
     *
     * @return int The rotation angle.
     */
    public function getRotationAngle(): int
    {
        return (360 - $this->rotationAngle) % 360;
    }

    /**
     * Sets the rotation in degree.
     *
     * @param int The rotation angle.
     * @return void
     */
    public function setRotationAngle(int $rotationAngle): void
    {
        $this->rotationAngle = (int)$rotationAngle;
        if ($this->rotationAngle !== 90 && $this->rotationAngle !== 180 && $this->rotationAngle !== 270) {
            $this->rotationAngle = 0;
        }

        $this->rotationAngle = (360 - $this->rotationAngle) % 360;
    }

    /**
     * Gets the background color.
     *
     * @return BCGColor The background color.
     */
    public function getBackgroundColor(): BCGColor
    {
        return $this->backgroundColor;
    }

    /**
     * Sets the background color.
     *
     * @param BCGColor $backgroundColor The background color.
     * @return void
     */
    public function setBackgroundColor(BCGColor $backgroundColor): void
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * Gets the foreground color.
     *
     * @return BCGColor The foreground color.
     */
    public function getForegroundColor(): BCGColor
    {
        return $this->foregroundColor;
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
    }

    /**
     * Returns the width and height that the text takes to be written.
     *
     * @return int[]
     */
    public function getDimension(): array
    {
        $width = imagefontwidth($this->font) * strlen($this->text);
        $height = imagefontheight($this->font);

        $rotationAngle = $this->getRotationAngle();
        if ($rotationAngle === 90 || $rotationAngle === 270) {
            return array($height, $width);
        } else {
            return array($width, $height);
        }
    }

    /**
     * Draws the text on the image at a specific position.
     * $x and $y represent the left bottom corner.
     *
     * @param resource $image The surface.
     * @param int $x X.
     * @param int $y Y.
     * @return void
     */
    public function draw($image, int $x, int $y): void
    {
        if ($this->getRotationAngle() !== 0) {
            if (!function_exists('imagerotate')) {
                throw new BCGDrawException('The method imagerotate doesn\'t exist on your server. Do not use any rotation.');
            }

            $w = imagefontwidth($this->font) * strlen($this->text);
            $h = imagefontheight($this->font);
            $gd = imagecreatetruecolor($w, $h);
            imagefilledrectangle($gd, 0, 0, $w - 1, $h - 1, $this->backgroundColor->allocate($gd));
            imagestring($gd, $this->font, 0, 0, $this->text, $this->foregroundColor->allocate($gd));
            $gd = imagerotate($gd, $this->rotationAngle, 0);
            imagecopy($image, $gd, $x, $y, 0, 0, imagesx($gd), imagesy($gd));
        } else {
            imagestring($image, $this->font, $x, $y, $this->text, $this->foregroundColor->allocate($image));
        }
    }
}
