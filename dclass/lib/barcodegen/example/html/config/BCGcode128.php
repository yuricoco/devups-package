<?php
$classFile = 'BCGcode128.php';
$className = 'BCGcode128';
$baseClassFile = 'BCGBarcode1D.php';
$codeVersion = '7.0.4';

function customSetup($barcode, $get)
{
    if (isset($get['start'])) {
        $barcode->setStart($get['start'] === 'NULL' ? null : $get['start']);
    }
}
