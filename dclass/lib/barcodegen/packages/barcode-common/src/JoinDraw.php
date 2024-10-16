<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Enable to join 2 BCGDrawing or 2 image object to make only one image.
 * There are some options for alignment.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

class JoinDraw
{
    const ALIGN_RIGHT       = 0;
    const ALIGN_BOTTOM      = 0;
    const ALIGN_LEFT        = 1;
    const ALIGN_TOP         = 1;
    const ALIGN_CENTER      = 2;

    const POSITION_RIGHT    = 0;
    const POSITION_BOTTOM   = 1;
    const POSITION_LEFT     = 2;
    const POSITION_TOP      = 3;

    private $image1;
    private $image2;
    private $alignement;
    private $position;
    private $space;
    private $image;

    /**
     * Construct the JoinDrawing Object.
     *  - $image1 and $image2 have to be BCGDrawing object or image object.
     *  - $space is the space between the two graphics in pixel.
     *  - $position is the position of the $image2 depending the $image1.
     *  - $alignment is the alignment of the $image2 if this one is smaller than $image1;
     *    if $image2 is bigger than $image1, the $image1 will be positionned on the opposite side specified.
     *
     * @param mixed $image1 The image 1.
     * @param mixed $image2 The image 2.
     * @param BCGColor $background The bacground color.
     * @param int $space The spacing.
     * @param int $position The position.
     * @param int $alignment The alignment.
     */
    public function __construct($image1, $image2, BCGColor $background, int $space = 10, int $position = self::POSITION_RIGHT, int $alignment = self::ALIGN_TOP)
    {
        if ($image1 instanceof BCGDrawing) {
            $this->image1 = $image1->getImage();
        } else {
            $this->image1 = $image1;
        }
        if ($image2 instanceof BCGDrawing) {
            $this->image2 = $image2->getImage();
        } else {
            $this->image2 = $image2;
        }

        $this->background = $background;
        $this->space = (int)$space;
        $this->position = (int)$position;
        $this->alignment = (int)$alignment;

        $this->createIm();
    }

    /**
     * Destroys the image.
     */
    public function __destruct(): void
    {
        imagedestroy($this->image);
    }

    /**
     * Finds the position where the barcode should be aligned.
     *
     * @param int $size1 The size 1.
     * @param int $size2 The size 2.
     * @param int $ailgnment The alignment.
     * @return int The position.
     */
    private function findPosition(int $size1, int $size2, int $alignment): int
    {
        $rsize1 = max($size1, $size2);
        $rsize2 = min($size1, $size2);

        if ($alignment === self::ALIGN_LEFT) { // Or TOP
            return 0;
        } elseif ($alignment === self::ALIGN_CENTER) {
            return $rsize1 / 2 - $rsize2 / 2;
        } else { // RIGHT or TOP
            return $rsize1 - $rsize2;
        }
    }

    /**
     * Change the alignments.
     *
     * @param int $alignment The alignment.
     * @return int The alignment.
     */
    private function changeAlignment(int $alignment): int
    {
        if ($alignment === 0) {
            return 1;
        } elseif ($alignment === 1) {
            return 0;
        } else {
            return 2;
        }
    }

    /**
     * Creates the image.
     *
     * @return void
     */
    private function createIm(): void
    {
        $w1 = imagesx($this->image1);
        $w2 = imagesx($this->image2);
        $h1 = imagesy($this->image1);
        $h2 = imagesy($this->image2);

        if ($this->position === self::POSITION_LEFT || $this->position === self::POSITION_RIGHT) {
            $w = $w1 + $w2 + $this->space;
            $h = max($h1, $h2);
        } else {
            $w = max($w1, $w2);
            $h = $h1 + $h2 + $this->space;
        }

        $this->image = imagecreatetruecolor($w, $h);
        imagefill($this->image, 0, 0, $this->background->allocate($this->image));

        // We start defining position of images
        if ($this->position === self::POSITION_TOP) {
            if ($w1 > $w2) {
                $posX1 = 0;
                $posX2 = $this->findPosition($w1, $w2, $this->alignment);
            } else {
                $a = $this->changeAlignment($this->alignment);
                $posX1 = $this->findPosition($w1, $w2, $a);
                $posX2 = 0;
            }

            $posY2 = 0;
            $posY1 = $h2 + $this->space;
        } elseif ($this->position === self::POSITION_LEFT) {
            if ($w1 > $w2) {
                $posY1 = 0;
                $posY2 = $this->findPosition($h1, $h2, $this->alignment);
            } else {
                $a = $this->changeAlignment($this->alignment);
                $posY2 = 0;
                $posY1 = $this->findPosition($h1, $h2, $a);
            }

            $posX2 = 0;
            $posX1 = $w2 + $this->space;
        } elseif ($this->position === self::POSITION_BOTTOM) {
            if ($w1 > $w2) {
                $posX2 = $this->findPosition($w1, $w2, $this->alignment);
                $posX1 = 0;
            } else {
                $a = $this->changeAlignment($this->alignment);
                $posX2 = 0;
                $posX1 = $this->findPosition($w1, $w2, $a);
            }

            $posY1 = 0;
            $posY2 = $h1 + $this->space;
        } else { // defaults to RIGHT
            if ($w1 > $w2) {
                $posY2 = $this->findPosition($h1, $h2, $this->alignment);
                $posY1 = 0;
            } else {
                $a = $this->changeAlignment($this->alignment);
                $posY2 = 0;
                $posY1 = $this->findPosition($h1, $h2, $a);
            }

            $posX1 = 0;
            $posX2 = $w1 + $this->space;
        }

        imagecopy($this->image, $this->image1, $posX1, $posY1, 0, 0, $w1, $h1);
        imagecopy($this->image, $this->image2, $posX2, $posY2, 0, 0, $w2, $h2);
    }

    /**
     * Returns the new $image created.
     *
     * @return resource The surface.
     */
    public function getImage()
    {
        return $this->image;
    }
}
