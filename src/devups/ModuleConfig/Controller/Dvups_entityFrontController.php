<?php


use dclass\devups\Controller\Controller;
use dclass\devups\Datatable\Datatable;
use Illuminate\Support\Facades\DB;
use Shuchkin\SimpleXLSX;

class Dvups_entityFrontController extends Dvups_entityController
{

    public function exportCsv()
    {

        function filterData(&$str)
        {
            $str = preg_replace("/\t/", "\\t", $str);
            $str = preg_replace("/\r?\n/", "\\n", $str);
            if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
        }

        $fields = Request::post("fields");
        $classname = ucfirst(Request::get("classname"));
        $entity = new $classname;

        if (Request::post("allcolumns")) {
            $keys = explode(',', $fields);
            //$columns = "$fields";
        } else {
            $keys = Request::post("columns");
        }
        $columns =  implode(', ', $keys);

        $exportat = date("YmdHis");
        //$classname = get_class($this);
        $filename = $classname . "-" . $exportat . ".csv";

        $dataexport = implode("\t", $keys) . "\n";
        (new \dclass\devups\Datatable\Lazyloading())->lazyloading($entity, null, "", Request::post("idlang"), true)
            ->getRows($columns, function ($row, $classname) use (&$dataexport) {

                $dataexport .= implode("\t", array_values($row)) . "\n";

            });

        $fileName = $classname . "_" . date('Y-m-d_H-i') . ".csv";
//        $excelData = file_get_contents(__DIR__ . "/../import/datalang.csv");

        header('Content-Type: text/html; charset=windows-1252');
        //header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$fileName\"");

// Render excel data
        echo $dataexport;

        exit;
    }

    public function importDataFront()
    {
        $log = [];
        $split = Request::post('split');

        return $this->importData(Request::post('classname'), $split);

    }


}
