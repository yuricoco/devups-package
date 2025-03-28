<?php

/*define('USERAPP', __project_id . '_customer');
define('USER', __project_id . '_customer');
define('USERID', __project_id . '_customer_id');

define('USERCOOKIE', __project_id . '_usercookie');
define('USERMAIL', __project_id . '_usermail');
define('USERPHONE', __project_id . '_userphone');
define('USERPASS', __project_id . '_userpass');*/

    require 'Entity/User.php';
    require 'Form/UserForm.php';
    require 'Datatable/UserTable.php';
    require 'Controller/UserController.php';
    //require 'Controller/UserFrontController.php';


    /*require 'Entity/Address.php';
    require 'Form/AddressForm.php';
    require 'Datatable/AddressTable.php';
    require 'Controller/AddressController.php';*/

require 'Entity/Uuid_history.php';
require 'Entity/Oauth_client.php';
/*require 'Form/Uuid_historyForm.php';
require 'Datatable/Uuid_historyTable.php';
require 'Controller/Uuid_historyController.php';*/

    require 'Entity/Blacklist.php';
    require 'Form/BlacklistForm.php';
    require 'Datatable/BlacklistTable.php';
    require 'Controller/BlacklistController.php';
