<?php

use Cake\I18n\Time;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load(WWW_ROOT . "modello_timeslots_one_line.xlsx");

$date = new DateTime();
$r = $date->format('Y-m-d');
$giorni = ["", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"];
$day = $this->request->getQuery('day');
$giorno = isset($giorni[$day]) ? $giorni[$day] : 'xxx';
$filename = "$r-orari-export-one-line-$giorno.xls";
header("Content-Type: application/force-download");
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");;
header("Content-Disposition: attachment;filename=$filename");
header("Content-Transfer-Encoding: binary ");

//$spreadsheet = new Spreadsheet();
//https://api.5t.drupalvm.test/timeslots/export.xls?q=avigliana
$sheet = $spreadsheet->getActiveSheet();

$row = 1;
$columns = [
  'name', 'code',
  'cap', 'city', 'province',
];
foreach ($orari as $address => $o) {
    $row++;
    //La prima colonna è la chiave
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col, $row, $address);
    $col++;

    //Le colonne successive sono quelle della tabella columns
    foreach ($columns as $c) {
        $value = $o[$c];
        $sheet->setCellValueByColumnAndRow($col, $row, $value);
        $col++;
    }

    //scrivo le due serie di orari
    $lastCol = $col;
    //Entrate
    $inout = [0, 1];
    foreach ($inout as $is_out) {
        if (isset($o[$is_out])) {
            foreach ($o[$is_out] as $key => $s) {
                $col = $lastCol + intval($key) + (intval($is_out) * (17 * 4 + 1)); //Siccome è un'uscita devo posizionarmi al fondo
                $sheet->setCellValueByColumnAndRow($col, $row, $s);
            }
        }
    }
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
// Return response object to prevent controller from trying to render
// a view.
return;
