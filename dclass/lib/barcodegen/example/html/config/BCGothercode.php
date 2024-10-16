<?php
$classFile = 'BCGothercode.php';
$className = 'BCGothercode';
$baseClassFile = 'BCGBarcode1D.php';
$codeVersion = '7.0.4';

function customSetup($barcode, $get)
{
    if (isset($get['label'])) {
        $barcode->setLabel($get['label']);
    }
}
