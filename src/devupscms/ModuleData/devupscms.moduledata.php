<?php

const status_entities = [
    'task','project',
];

    require 'Entity/Status.php';
    require 'Entity/Status_lang.php';
    require 'Form/StatusForm.php';
    require 'Datatable/StatusTable.php';
    require 'Controller/StatusController.php';



    require 'Entity/Interval.php';
    require 'Entity/Interval_group.php';
    require 'Form/IntervalForm.php';
    require 'Datatable/IntervalTable.php';
    require 'Controller/IntervalController.php';
