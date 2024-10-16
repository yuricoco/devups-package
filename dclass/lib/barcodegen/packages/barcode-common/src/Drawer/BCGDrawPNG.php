<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Image Class to draw PNG images with possibility to set DPI
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

class BCGDrawPNG extends BCGDraw
{
    private ?int $dpi;

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
     * @param int|null $dpi The dpi.
     * @return void
     */
    public function setDPI(?int $dpi): void
    {
        if (is_numeric($dpi)) {
            $this->dpi = max(1, $dpi);
        } else {
            $this->dpi = null;
        }
    }

    /**
     * Draws the PNG on the screen or in a file.
     *
     * @return void
     */
    public function draw(): void
    {
        ob_start();
        imagepng($this->image);
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
        // Scan all the ChunkType
        if (strcmp(substr($bin, 0, 8), pack('H*', '89504E470D0A1A0A')) === 0) {
            $chunks = $this->detectChunks($bin);

            $this->internalSetDPI($bin, $chunks);
            $this->internalSetC($bin, $chunks);
        }
    }

    private function detectChunks($bin)
    {
        $data = substr($bin, 8);
        $chunks = array();
        $c = strlen($data);

        $offset = 0;
        while ($offset < $c) {
            $packed = unpack('Nsize/a4chunk', $data);
            $size = $packed['size'];
            $chunk = $packed['chunk'];

            $chunks[] = array('offset' => $offset + 8, 'size' => $size, 'chunk' => $chunk);
            $jump = $size + 12;
            $offset += $jump;
            $data = substr($data, $jump);
        }

        return $chunks;
    }

    private function internalSetDPI(&$bin, &$chunks): void
    {
        if ($this->dpi !== null) {
            $meters = (int)($this->dpi * 39.37007874);

            $found = -1;
            $c = count($chunks);
            for ($i = 0; $i < $c; $i++) {
                // We already have a pHYs
                if ($chunks[$i]['chunk'] === 'pHYs') {
                    $found = $i;
                    break;
                }
            }

            $data = 'pHYs' . pack('NNC', $meters, $meters, 0x01);
            $crc = self::crc($data, 13);
            $cr = pack('Na13N', 9, $data, $crc);

            // We didn't have a pHYs
            if ($found === -1) {
                // Don't do anything if we have a bad PNG
                if ($c >= 2 && $chunks[0]['chunk'] === 'IHDR') {
                    array_splice($chunks, 1, 0, array(array('offset' => 33, 'size' => 9, 'chunk' => 'pHYs')));

                    // Push the data
                    for ($i = 2; $i < $c; $i++) {
                        $chunks[$i]['offset'] += 21;
                    }

                    $firstPart = substr($bin, 0, 33);
                    $secondPart = substr($bin, 33);
                    $bin = $firstPart;
                    $bin .= $cr;
                    $bin .= $secondPart;
                }
            } else {
                $bin = substr_replace($bin, $cr, $chunks[$i]['offset'], 21);
            }
        }
    }

    private function internalSetC(&$bin, &$chunks): void
    {
        if (count($chunks) >= 2 && $chunks[0]['chunk'] === 'IHDR') {
            $firstPart = substr($bin, 0, 33);
            $secondPart = substr($bin, 33);
            $cr = pack('H*', '0000004C74455874436F707972696768740047656E657261746564207769746820426172636F64652042616B65727920666F722050485020687474703A2F2F7777772E626172636F646562616B6572792E636F6DC57F50A1');
            $bin = $firstPart;
            $bin .= $cr;
            $bin .= $secondPart;
        }

        // Chunks is dirty!! But we are done.
    }

    private static array $crcTable = array();
    private static bool $crcTableComputed = false;

    private static function make_crcTable(): void
    {
        for ($n = 0; $n < 256; $n++) {
            $c = $n;
            for ($k = 0; $k < 8; $k++) {
                if (($c & 1) === 1) {
                    $c = 0xedb88320 ^ (self::SHR($c, 1));
                } else {
                    $c = self::SHR($c, 1);
                }
            }

            self::$crcTable[$n] = $c;
        }

        self::$crcTableComputed = true;
    }

    private static function SHR($x, $n): int
    {
        $mask = 0x40000000;

        if ($x < 0) {
            $x &= 0x7FFFFFFF;
            $mask = $mask >> ($n - 1);
            return ($x >> $n) | $mask;
        }

        return (int)$x >> (int)$n;
    }

    private static function update_crc($crc, $buf, $len): int
    {
        $c = $crc;

        if (!self::$crcTableComputed) {
            self::make_crcTable();
        }

        for ($n = 0; $n < $len; $n++) {
            $c = self::$crcTable[($c ^ ord($buf[$n])) & 0xff] ^ (self::SHR($c, 8));
        }

        return $c;
    }

    private static function crc($data, $len): int
    {
        return self::update_crc(-1, $data, $len) ^ -1;
    }
}
