<?php
$classFile = 'BCGgs1128.php';
$className = 'BCGgs1128';
$baseClassFile = 'BCGBarcode1D.php';
$codeVersion = '7.0.4';

function customSetup($barcode, $get)
{
    if (isset($get['start'])) {
        $barcode->setStart($get['start'] === 'NULL' ? null : $get['start']);
    }

    $barcode->setApplicationIdentifiers(BarcodeBakery\Common\GS1\GS1AI::getDefaultAIData());
}
