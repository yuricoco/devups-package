<?php
require __DIR__ . '../../vendor/autoload.php';

use BarcodeBakery\Common\BCGColor;
use BarcodeBakery\Common\BCGDrawing;
use BarcodeBakery\Common\BCGFontFile;
use BarcodeBakery\Common\BCGLabel;

define('IN_CB', true);
include_once('include/function.php');

function showError()
{
    header('Content-Type: image/png');
    readfile('error.png');
    exit;
}

$requiredKeys = array('code', 'filetype', 'dpi', 'scale', 'rotation', 'font_family', 'font_size', 'text');

// Check if everything is present in the request
foreach ($requiredKeys as $key) {
    if (!isset($_GET[$key])) {
        showError();
    }
}

if (!preg_match('/^[A-Za-z0-9]+$/', $_GET['code'])) {
    showError();
}

$code = $_GET['code'];

// Check if the code is valid
if (!file_exists('config' . DIRECTORY_SEPARATOR . $code . '.php')) {
    showError();
}

include_once('config' . DIRECTORY_SEPARATOR . $code . '.php');

include_once('config' . DIRECTORY_SEPARATOR . $baseClassFile);

$filetypes = array('PNG' => BCGDrawing::IMG_FORMAT_PNG, 'JPEG' => BCGDrawing::IMG_FORMAT_JPEG, 'GIF' => BCGDrawing::IMG_FORMAT_GIF);
$finalClassName = 'BarcodeBakery\\Barcode\\' . $className;

$drawException = null;
$barcode = null;
try {
    $colorBlack = new BCGColor(0, 0, 0);
    $colorWhite = new BCGColor(255, 255, 255);

    $code_generated = new $finalClassName();

    if (function_exists('baseCustomSetup')) {
        baseCustomSetup($code_generated, $_GET);
    }

    if (function_exists('customSetup')) {
        customSetup($code_generated, $_GET);
    }

    $code_generated->setScale(max(1, $_GET['scale']));
    $code_generated->setBackgroundColor($colorWhite);
    $code_generated->setForegroundColor($colorBlack);

    if ($_GET['text'] !== '') {
        $text = convertText($_GET['text']);
        $code_generated->parse($text);
    }

    $barcode = $code_generated;
} catch (\Exception $exception) {
    $drawException = $exception;
}

$drawing = new BCGDrawing($barcode, $colorWhite);
if ($drawException) {
    $drawing->drawException($drawException);
} else {
    $drawing->setRotationAngle($_GET['rotation']);
    $drawing->setDPI($_GET['dpi'] === 'NULL' ? null : max(72, min(300, intval($_GET['dpi']))));
}

switch ($_GET['filetype']) {
    case 'PNG':
        header('Content-Type: image/png');
        break;
    case 'JPEG':
        header('Content-Type: image/jpeg');
        break;
    case 'GIF':
        header('Content-Type: image/gif');
        break;
}

$drawing->finish($filetypes[$_GET['filetype']]);
