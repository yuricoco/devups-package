<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Holds font family and size.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class BCGFontInfo
{
    private $box;

    public function __construct($box)
    {
        $this->box = $box;
    }

    public function getBox(): array
    {
        return $this->box;
    }

    public function getAscender(): int
    {
        return abs($this->box[7]);
    }

    public function getDescender(): int
    {
        return abs($this->box[1] > 0 ? $this->box[1] : 0);
    }

    public function getWidth(): int
    {
        // We drew at 0, so even if the box starts at 1, we need more space
        // So we don't do -box[0].
        return max($this->box[2], $this->box[4]);
    }

    public function getHeight(): int
    {
        $minY = min(array($this->box[1], $this->box[3], $this->box[5], $this->box[7]));
        $maxY = max(array($this->box[1], $this->box[3], $this->box[5], $this->box[7]));
        return $maxY - $minY;
    }
}

class BCGFontFile implements BCGFont
{
    private string $path;
    private int $size;
    private string $text = '';
    private BCGColor $foregroundColor;
    private int $rotationAngle;
    private ?BCGFontInfo $fontInfo; // BCGFontInfo
    private float $descenderSize;

    /**
     * Constructor.
     *
     * @param string $fontPath path to the file
     * @param int $size size in point
     */
    public function __construct(string $fontPath, int $size)
    {
        if (!file_exists($fontPath)) {
            throw new BCGArgumentException('The font path is incorrect.', 'fontPath');
        }

        $this->path = $fontPath;
        $this->size = $size;
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
        $this->fontInfo = null;
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

        $this->fontInfo = null;
    }

    /**
     * Gets the background color.
     *
     * @return BCGColor The background color.
     */
    public function getBackgroundColor(): BCGColor
    {
    }

    /**
     * Sets the background color.
     *
     * @param BCGColor $backgroundColor The background color.
     * @return void
     */
    public function setBackgroundColor(BCGColor $backgroundColor): void
    {
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
        $fontInfo = $this->getFontInfo();
        $rotationAngle = $this->getRotationAngle();
        $width = $fontInfo->getWidth();
        $height = $fontInfo->getHeight();
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
        $drawingPosition = $this->getDrawingPosition($x, $y);
        imagettftext($image, $this->size, $this->rotationAngle, $drawingPosition[0], $drawingPosition[1], $this->foregroundColor->allocate($image), $this->path, $this->text);
    }

    private function getDrawingPosition(int $x, int $y): array
    {
        $fontInfo = $this->getFontInfo();
        $dimension = $this->getDimension();
        $rotationAngle = $this->getRotationAngle();

        if ($rotationAngle === 0) {
            $y += $fontInfo->getAscender();
        } elseif ($rotationAngle === 90) {
            $x += $fontInfo->getDescender();
        } elseif ($rotationAngle === 180) {
            $x += $dimension[0];
            $y += $fontInfo->getDescender();
        } elseif ($rotationAngle === 270) {
            $x += $fontInfo->getAscender();
            $y += $dimension[1];
        }

        return array($x, $y);
    }

    private function getFontInfo(): BCGFontInfo
    {
        if ($this->fontInfo === null) {
            $box = imagettfbbox($this->size, 0, $this->path, $this->text);
            $this->fontInfo = new BCGFontInfo($box);
        }

        return $this->fontInfo;
    }
}
