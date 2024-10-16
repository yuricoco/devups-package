<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Holds the drawing $image
 * You can use getImage() to add other kind of form not held into these classes.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

use BarcodeBakery\Common\Drawer\BCGDrawPNG;
use BarcodeBakery\Common\Drawer\BCGDrawJPG;

class BCGDrawing
{
    const IMG_FORMAT_PNG = 1;
    const IMG_FORMAT_JPEG = 2;
    const IMG_FORMAT_GIF = 3;
    const IMG_FORMAT_WBMP = 4;

    private int $w;
    private int $h;
    private BCGColor $color;
    private $image;
    private ?BCGBarcode $barcode = null;
    private ?int $dpi;
    private int $rotateDegree;

    private ?\Exception $exceptionToDraw = null;

    /**
     * Creates a drawing surface by indicating its background color.
     *
     * @param BCGBarcode|null $barcode The barcode.
     * @param BCGColor|null $color Background color.
     */
    public function __construct(?BCGBarcode $barcode, BCGColor $color = null)
    {
        $this->image = null;
        $this->setBarcode($barcode);
        $this->color = $color;
        if ($this->color === null) {
            $this->color = new BCGColor('white');
        }

        $this->dpi = null;
        $this->rotateDegree = 0;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->destroy();
    }

    /**
     * Gets the image resource.
     *
     * @return resource The surface.
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Sets the image resource.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

    /**
     * Gets barcode for drawing.
     *
     * @return BCGBarcode|null The barcode.
     */
    public function getBarcode(): ?BCGBarcode
    {
        return $this->barcode;
    }

    /**
     * Sets barcode for drawing.
     *
     * @param BCGBarcode|null $barcode The barcode.
     * @return void
     */
    public function setBarcode(?BCGBarcode $barcode): void
    {
        $r = mt_rand(0, 100);
        if ($barcode !== null && $r <= 5) {
            $addOnTop = true;
            // If any label on top, add to the bottom
            foreach ($barcode->getLabels() as $label) {
                if ($label->getPosition() === BCGLabel::POSITION_TOP) {
                    $addOnTop = false;
                    break;
                }
            }

            $l = pack('H*', '4e6f6e2d636f6d6d65726369616c2076657273696f6e');
            $system = new BCGLabel($l, new BCGFontPhp(1), $addOnTop ? BCGLabel::POSITION_TOP : BCGLabel::POSITION_BOTTOM);
            $barcode->addLabel($system);
        }

        $this->barcode = $barcode;
    }

    /**
     * Gets the DPI for supported filetype.
     *
     * @return int The DPI.
     */
    public function getDPI(): int
    {
        return $this->dpi;
    }

    /**
     * Sets the DPI for supported filetype.
     *
     * @param int $dpi The DPI.
     * @return void
     */
    public function setDPI(int $dpi): void
    {
        $this->dpi = $dpi;
    }

    /**
     * Gets the rotation angle in degree clockwise. The rotation is clockwise.
     *
     * @return int Rotation angle in degree.
     */
    public function getRotationAngle(): int
    {
        return $this->rotateDegree;
    }

    /**
     * Sets the rotation angle in degree clockwise. The rotation is clockwise.
     *
     * @param int $degree Rotation angle in degree.
     * @return void
     */
    public function setRotationAngle(int $degree): void
    {
        $this->rotateDegree = (int)$degree;
    }

    /**
     * Draws the barcode on the surface.
     *
     * @return void
     */
    private function draw(): void
    {
        if ($this->exceptionToDraw !== null || $this->barcode === null) {
            $this->w = 1;
            $this->h = 1;
            $this->init();

            // Is the image big enough?
            $w = imagesx($this->image);
            $h = imagesy($this->image);

            $text = $this->exceptionToDraw ? $this->exceptionToDraw->getMessage() : 'No barcode available';

            $width = imagefontwidth(2) * strlen($text);
            $height = imagefontheight(2);
            if ($width > $w || $height > $h) {
                $width = max($w, $width);
                $height = max($h, $height);

                // We change the size of the image
                $newimg = imagecreatetruecolor($width, $height);
                imagefill($newimg, 0, 0, imagecolorat($this->image, 0, 0));
                imagecopy($newimg, $this->image, 0, 0, 0, 0, $w, $h);
                $this->image = $newimg;
            }

            $black = new BCGColor('black');
            imagestring($this->image, 2, 0, 0, $text, $black->allocate($this->image));
        } else {
            $size = $this->barcode->getDimension(0, 0);
            $this->w = max(1, $size[0]);
            $this->h = max(1, $size[1]);
            $this->init();
            $this->barcode->draw($this->image);
        }
    }

    /**
     * Saves $image into the file (many format available).
     *
     * @param int $imageStyle The image style.
     * @param string $fileName The file name.
     * @param int $quality The quality.
     * @return void
     */
    public function finish(int $imageStyle = self::IMG_FORMAT_PNG, ?string $fileName = null, int $quality = 100): void
    {
        $this->draw();
        $drawer = null;

        $image = $this->image;
        if ($this->rotateDegree > 0.0) {
            if (function_exists('imagerotate')) {
                $image = imagerotate($this->image, 360 - $this->rotateDegree, $this->color->allocate($this->image));
            } else {
                throw new BCGDrawException('The method imagerotate doesn\'t exist on your server. Do not use any rotation.');
            }
        }

        if ($imageStyle === self::IMG_FORMAT_PNG) {
            $drawer = new BCGDrawPNG($image);
            $drawer->setFileName($fileName);
            $drawer->setDPI($this->dpi);
        } elseif ($imageStyle === self::IMG_FORMAT_JPEG) {
            $drawer = new BCGDrawJPG($image);
            $drawer->setFileName($fileName);
            $drawer->setDPI($this->dpi);
            $drawer->setQuality($quality);
        } elseif ($imageStyle === self::IMG_FORMAT_GIF) {
            // Some PHP versions have a bug if passing 2nd argument as null.
            if ($this->fileName === null || $fileName === '') {
                imagegif($image);
            } else {
                imagegif($image, $fileName);
            }
        } elseif ($imageStyle === self::IMG_FORMAT_WBMP) {
            imagewbmp($image, $fileName);
        }

        if ($drawer !== null) {
            $drawer->draw();
        }
    }

    /**
     * Writes the Error on the picture.
     *
     * @param \Exception $exception
     * @return void
     */
    public function drawException(\Exception $exception): void
    {
        $this->exceptionToDraw = $exception;
    }

    /**
     * Free the memory of PHP (called also by destructor).
     *
     * @return void
     */
    public function destroy(): void
    {
        @imagedestroy($this->image);
    }

    /**
     * Init Image and color background.
     *
     * @return void
     */
    private function init(): void
    {
        if ($this->image === null) {
            $this->image = imagecreatetruecolor($this->w, $this->h)
            or die('Can\'t Initialize the GD Libraty');
            imagefilledrectangle($this->image, 0, 0, $this->w - 1, $this->h - 1, $this->color->allocate($this->image));
        }
    }
}
