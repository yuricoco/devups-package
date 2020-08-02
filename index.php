<?php
//

require 'header.php';

(new Request('hello'));


switch (Request::get('path')) {

    case 'hello':
        Genesis::render("hello");
        break;

    default:
        Genesis::render('404', ['page' => Request::get('path')]);
        break;
}
    
    