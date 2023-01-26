<?php

use Cake\Utility\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$date = new DateTime();
$r = $date->format('Y-m-d');
$filename = "$r-origini.xls";
header("Content-Type: application/force-download");
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");;
header("Content-Disposition: attachment;filename=$filename");
header("Content-Transfer-Encoding: binary ");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$row = '1';
$col = 'A';
$columns = ['province', 'city', 'company.name', 'name', 'address', 'office_code_external', 'company.company_code',];

foreach ($companies as $p) {
    //We convert every row to a flat array
    $p = Hash::flatten($p);
    if ($row == 1) {
        //https://api.cakephp.org/4.0/trait-Cake.Datasource.EntityTrait.html#getVisible
        //Restituisce l'elenco dei campi visibili della query

        foreach ($columns as $c) {
            if ($c == "company.company_code") {
                $sheet->setCellValue("$col$row", "Formazione Professionale");
            } else {
                $sheet->setCellValue("$col$row", $c);
            }
            $col++;
        }
    }

    $row++;
    $col = 'A';
    foreach ($columns as $c) {
        $value = $p[$c];
        if ($c == 'office_code_external') {
            if ($value == null) {
                $sheet->setCellValue("$col$row", "PRIVATO");
            } else {
                $sheet->setCellValue("$col$row", "PUBBLICO");
            }
        } elseif ($c == "company.company_code") {
            if ($value == null) {
                $sheet->setCellValue("$col$row", "SI");
            } else {
                $sheet->setCellValue("$col$row", "NO");
            }
        } else {
            $sheet->setCellValue("$col$row", $value);
        }
        $col++;
    }
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Return response object to prevent controller from trying to render
// a view.
return;
