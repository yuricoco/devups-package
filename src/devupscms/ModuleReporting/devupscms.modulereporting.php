<?php

function langt($fr, $en, $local = "fr", $matcher = [])
{
    if ($local == "fr") {
        foreach ($matcher as $search => $value)
            $fr = str_replace(":" . $search, $value, $fr);

        return $fr;
    }
    foreach ($matcher as $search => $value)
        $en = str_replace(":" . $search, $value, $en);

    return $en;
}

require 'Entity/Emaillog.php';
require 'Form/EmaillogForm.php';
require 'Datatable/EmaillogTable.php';
require 'Controller/EmaillogController.php';

require 'Entity/Reportingmodel.php';
require 'Entity/Reportingmodel_lang.php';
require 'Form/ReportingmodelForm.php';
require 'Datatable/ReportingmodelTable.php';
require 'Controller/ReportingmodelController.php';


require 'Entity/Push_email.php';
require 'Form/Push_emailForm.php';
require 'Datatable/Push_emailTable.php';
require 'Controller/Push_emailController.php';
