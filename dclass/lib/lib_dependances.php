<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//require __DIR__ . '/HZip.php';
//require __DIR__ . '/My_ziparchive.php';
require __DIR__ . '/Util.php';
// ETAPE 3.1/3.2 (v8.0) : dclass/lib/addendum/ (lib vendoree, docblocks)
// retiree - les annotations de routage sont des attributs PHP 8 natifs,
// lus via ReflectionAnnotated.php (ReflectionAnnotatedClass/Method).
require __DIR__ . '/ReflectionAnnotated.php';
require __DIR__ . '/annotation/ApiLazyloading.php';
require __DIR__ . '/annotation/DDoc.php';
require __DIR__ . '/annotation/MethodView.php';
require __DIR__ . '/annotation/Auth.php';
require __DIR__ . '/annotation/Api.php';
require __DIR__ . '/annotation/GET.php';
require __DIR__ . '/annotation/POST.php';
require __DIR__ . '/annotation/PUT.php';
require __DIR__ . '/annotation/DELETE.php';
require __DIR__ . '/DMail.php';
require __DIR__ . '/DPdf.php';

//require __DIR__ . '/php-qrcode/qrcode.php';

