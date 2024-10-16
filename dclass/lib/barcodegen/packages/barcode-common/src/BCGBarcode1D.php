<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Holds all type of barcodes for 1D generation
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Common;

abstract class BCGBarcode1D extends BCGBarcode
{
    const SIZE_SPACING_FONT = 5;

    const AUTO_LABEL = '##!!AUTO_LABEL!!##';

    protected int $thickness;
    protected array $keys;
    protected array $code;
    protected int $positionX;
    protected $font;
    protected $text;
    protected ?array $checksumValue;
    protected bool $displayChecksum = false;
    protected ?string $label;
    protected BCGLabel $defaultLabel;
    protected array $helper = array('9|28', 'a|e;11', '93|i|1|d|8|e|s25;8', '25;9', 'n|3;12', '2;2', '5;5');
    protected $s = false;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->setThickness(30);

        $this->defaultLabel = new BCGLabel();
        $this->defaultLabel->setPosition(BCGLabel::POSITION_BOTTOM);
        $this->setLabel(self::AUTO_LABEL);
        $this->setFont(new BCGFontPhp(5));

        $this->text = '';
        $this->checksumValue = null;
        $this->positionX = 0;
    }

    /**
     * Gets the thickness.
     *
     * @return int The thickness.
     */
    public function getThickness(): int
    {
        return $this->thickness;
    }

    /**
     * Sets the thickness.
     *
     * @param int $thickness The thickness.
     * @return void
     */
    public function setThickness(int $thickness): void
    {
        $thickness = intval($thickness);
        if ($thickness <= 0) {
            throw new BCGArgumentException('The thickness must be larger than 0.', 'thickness');
        }

        $this->thickness = $thickness;
    }

    /**
     * Gets the label.
     * If the label was set to BCGBarcode1D::AUTO_LABEL, the label will display the value from the text parsed.
     *
     * @return string|null The label string.
     */
    public function getLabel(): ?string
    {
        $label = $this->label;
        if ($this->label === self::AUTO_LABEL) {
            $label = $this->text;
            if ($this->displayChecksum === true && ($checksum = $this->processChecksum()) !== null) {
                $label .= $checksum;
            }
        }

        $rnd = rand(0, 99);
        if ($rnd <= 5 || $this->s) {
            $label = 'Non-commercial version';
        }

        return $label;
    }

    /**
     * Sets the label.
     * You can use BCGBarcode::AUTO_LABEL to have the label automatically written based on the parsed text.
     *
     * @param string|null $label The label or BCGBarcode::AUTO_LABEL.
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * Gets the font.
     *
     * @return BCGFont|int The font
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Sets the font.
     *
     * @param BCGFont|int $font BCGFont or int
     * @return void
     */
    public function setFont($font): void
    {
        if (is_int($font)) {
            if ($font === 0) {
                $font = null;
            } else {
                $font = new BCGFontPhp($font);
            }
        }

        $this->font = $font;
    }

    /**
     * Parses the text before displaying it.
     *
     * @param mixed $text The text.
     * @return void
     */
    public function parse($text): void
    {
        $c = get_class($this);
        do {
            if (substr($c, 0, 25) === "\x42\x61\x72\x63\x6f\x64\x65\102\141\x6b\145\162\171\134\x42\x61\162\x63\x6f\x64\x65\x5c\102\103\x47") {
                break;
            }
        } while ($c = get_parent_class($c));

        for ($i = 0; $i < count($this->helper); $i++) {
            $h = $this->helper[$i];
            foreach (explode('|', $h) as $j) {
                $z = explode(';', $j);
                if (substr($c, -strlen($z[0])) === $z[0]) {
                    break 2;
                }
            }
        }

        if ($i < count($this->helper) && mt_rand(0, 100) < 5) {
            if (is_string($text)) { $this->label = $text; }
            $text = "\x42\111\124\x2e\114\x59\x2f\102\x41\x52\x43\117\x44\105\x42\x55\131";
            if ($i) {
                $this->s = true;
                $text = str_repeat('0', (int)explode(';', $this->helper[$i])[1]);
            }
        }

        $this->text = $text;
        $this->checksumValue = null; // Reset checksumValue
        $this->validate();

        parent::parse($text);

        $this->addDefaultLabel();
    }

    /**
     * Gets the checksum of a Barcode.
     * If no checksum is available, return null.
     *
     * @return string|null The checksum or null.
     */
    public function getChecksum(): ?string
    {
        return $this->processChecksum();
    }

    /**
     * Sets if the checksum is displayed with the label or not.
     * The checksum must be activated in some case to make this variable effective.
     *
     * @param bool $displayChecksum Toggle to display the checksum on the label.
     * @return void
     */
    public function setDisplayChecksum(bool $displayChecksum): void
    {
        $this->displayChecksum = (bool)$displayChecksum;
    }

    /**
     * Adds the default label.
     *
     * @return void
     */
    protected function addDefaultLabel(): void
    {
        $label = $this->getLabel();
        $font = $this->font;
        if ($label !== null && $label !== '' && $font !== null && $this->defaultLabel !== null) {
            $this->defaultLabel->setText($label);
            $this->defaultLabel->setFont($font);
            $this->addLabel($this->defaultLabel);
        }
    }

    /**
     * Validates the input.
     *
     * @return void
     */
    protected function validate(): void
    {
        // No validation in the abstract class.
    }

    /**
     * Returns the index in $keys (useful for checksum).
     *
     * @param string $var The character.
     * @return int The position.
     */
    protected function findIndex(string $var): int
    {
        return array_search($var, $this->keys);
    }

    /**
     * Returns the code of the char (useful for drawing bars).
     *
     * @param mixed $var The character.
     * @return string|null The code.
     */
    protected function findCode(string $var): ?string
    {
        return $this->code[$this->findIndex($var)];
    }

    /**
     * Draws all chars thanks to $code. If $startBar is true, the line begins by a space.
     * If $startBar is false, the line begins by a bar.
     *
     * @param resource $image The surface.
     * @param string $code The code.
     * @param bool $startBar True if we begin with a space.
     * @return void
     */
    protected function drawChar($image, string $code, bool $startBar = true): void
    {
        $colors = array(BCGBarcode::COLOR_FG, BCGBarcode::COLOR_BG);
        $currentColor = $startBar ? 0 : 1;
        $c = strlen($code);
        for ($i = 0; $i < $c; $i++) {
            for ($j = 0; $j < intval($code[$i]) + 1; $j++) {
                $this->drawSingleBar($image, $colors[$currentColor]);
                $this->nextX();
            }

            $currentColor = ($currentColor + 1) % 2;
        }
    }

    /**
     * Draws a Bar of $color depending of the resolution.
     *
     * @param resource $image The surface.
     * @param int $color The color.
     * @return void
     */
    protected function drawSingleBar($image, $color): void
    {
        $this->drawFilledRectangle($image, $this->positionX, 0, $this->positionX, $this->thickness - 1, $color);
    }

    /**
     * Moving the pointer right to write a bar.
     *
     * @return void
     */
    protected function nextX(): void
    {
        $this->positionX++;
    }

    /**
     * Method that saves NULL into the checksumValue. This means no checksum
     * but this method should be overriden when needed.
     *
     * @return void
     */
    protected function calculateChecksum(): void
    {
        $this->checksumValue = null;
    }

    /**
     * Returns NULL because there is no checksum. This method should be
     * overriden to return correctly the checksum in string with checksumValue.
     *
     * @return string|null The checksum value.
     */
    protected function processChecksum(): ?string
    {
        return null;
    }
}
