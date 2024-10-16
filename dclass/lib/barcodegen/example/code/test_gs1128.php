<?php
require __DIR__ . '/../vendor/autoload.php';

use BarcodeBakery\Common\BCGColor;
use BarcodeBakery\Common\BCGDrawing;
use BarcodeBakery\Common\BCGFontFile;
use BarcodeBakery\Barcode\BCGgs1128;
use BarcodeBakery\Common\GS1\GS1AI;

if (!class_exists('BarcodeBakery\Common\GS1\GS1AI')) {
    throw new Exception('The package barcode-bakery/gs1ai should be installed to use this class.');
}

// Loading Font
$font = new BCGFontFile(__DIR__ . '/../font/Arial.ttf', 18);

// Don't forget to sanitize user inputs
$text = isset($_GET['text']) ? $_GET['text'] : '011234567891234';

// The arguments are R, G, B for color.
$colorBlack = new BCGColor(0, 0, 0);
$colorWhite = new BCGColor(255, 255, 255);

$drawException = null;
$barcode = null;
try {
    $code = new BCGgs1128();

    // Uncomment when using the commercial version
    ////$code->useCommercialVersion();

    $code->setScale(2); // Resolution
    $code->setThickness(30); // Thickness
    $code->setForegroundColor($colorBlack); // Color of bars
    $code->setBackgroundColor($colorWhite); // Color of spaces
    $code->setFont($font); // Font (or 0)
    $code->setStrictMode(true);
    $code->setApplicationIdentifiers(GS1AI::getDefaultAIData());
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
