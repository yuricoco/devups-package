<?php
require __DIR__ . '/../vendor/autoload.php';

use BarcodeBakery\Common\BCGColor;
use BarcodeBakery\Common\BCGDrawing;
use BarcodeBakery\Common\BCGFontFile;
use BarcodeBakery\Barcode\BCGcodabar;

// Loading Font
$font = new BCGFontFile(__DIR__ . '/../font/Arial.ttf', 18);

// Don't forget to sanitize user inputs
$text = isset($_GET['text']) ? $_GET['text'] : 'A12345B';

// The arguments are R, G, B for color.
$colorBlack = new BCGColor(0, 0, 0);
$colorWhite = new BCGColor(255, 255, 255);

$drawException = null;
$barcode = null;
try {
    $code = new BCGcodabar();

    // Uncomment when using the commercial version
    ////$code->useCommercialVersion();

    $code->setScale(2); // Resolution
    $code->setThickness(30); // Thickness
    $code->setForegroundColor($colorBlack); // Color of bars
    $code->setBackgroundColor($colorWhite); // Color of spaces
    $code->setFont($font); // Font (or 0)
    $code->parse($text); // Text
    $barcode = $code;
} catch (Exception $exception) {
    $drawException = $exception;
}

$drawing = new BCGDrawing($barcode, $colorWhite);
if ($drawException) {
    $drawing->drawException($drawException);
}

// Header that says it is an image (remove it if you save the barcode to a file)
header('Content-Type: image/png');
header('Content-Disposition: inline; filename="barcode.png"');

// Draw (or save) the image into PNG format.
$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
