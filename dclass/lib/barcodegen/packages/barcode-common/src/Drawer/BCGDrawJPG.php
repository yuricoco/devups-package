<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Image Class to draw JPG images with possibility to set DPI
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common\Drawer;

if (!function_exists('file_put_contents')) {
    function file_put_contents($fileName, $data)
    {
        $f = @fopen($fileName, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $data);
            fclose($f);
            return $bytes;
        }
    }
}

class BCGDrawJPG extends BCGDraw
{
    private int $dpi;
    private int $quality;

    /**
     * Constructor.
     *
     * @param resource $image The surface.
     */
    public function __construct($image)
    {
        parent::__construct($image);
    }

    /**
     * Sets the DPI.
     *
     * @param int $dpi The DPI.
     * @return void
     */
    public function setDPI(int $dpi): void
    {
        if (is_int($dpi)) {
            $this->dpi = max(1, $dpi);
        } else {
            $this->dpi = null;
        }
    }

    /**
     * Sets the quality of the JPG.
     *
     * @param int $quality The quality.
     * @return void
     */
    public function setQuality(int $quality): void
    {
        $this->quality = $quality;
    }

    /**
     * Draws the JPG on the screen or in a file.
     *
     * @return void
     */
    public function draw(): void
    {
        ob_start();
        imagejpeg($this->image, null, $this->quality);
        $bin = ob_get_contents();
        ob_end_clean();

        $this->setInternalProperties($bin);

        if (empty($this->fileName)) {
            echo $bin;
        } else {
            file_put_contents($this->fileName, $bin);
        }
    }

    private function setInternalProperties(&$bin): void
    {
        $this->internalSetDPI($bin);
        $this->internalSetC($bin);
    }

    private function internalSetDPI(&$bin): void
    {
        if ($this->dpi !== null) {
            $bin = substr_replace($bin, pack("Cnn", 0x01, $this->dpi, $this->dpi), 13, 5);
        }
    }

    private function internalSetC(&$bin): void
    {
        if (strcmp(substr($bin, 0, 4), pack('H*', 'FFD8FFE0')) === 0) {
            $offset = 4 + (ord($bin[4]) << 8 | ord($bin[5]));
            $firstPart = substr($bin, 0, $offset);
            $secondPart = substr($bin, $offset);
            $cr = pack('H*', 'FFFE004447656E657261746564207769746820426172636F64652042616B65727920666F722050485020687474703A2F2F7777772E626172636F646562616B6572792E636F6D');
            $bin = $firstPart;
            $bin .= $cr;
            $bin .= $secondPart;
        }
    }
}
