<?php

use Cake\I18n\Time;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load(WWW_ROOT . "modello_timeslots.xlsx");

$date = new DateTime();
$r = $date->format('Y-m-d');
$filename = "$r-orari-scuole.xls";
header("Content-Type: application/force-download");
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");;
header("Content-Disposition: attachment;filename=$filename");
header("Content-Transfer-Encoding: binary ");

//https://api.5t.drupalvm.test/timeslots/export.xls?q=avigliana
$sheet = $spreadsheet->getActiveSheet();
$giorni = ["", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"];
$row = 1;
$col = 1;
$columns = [
  'province', 'city',
  'nome_scuola', 'company_code', 'nome_sede',
  'address',
  'type',
  'slot', 'qty', 'is_out', 'day',
  'valid_from', 'approved', 'note',
];
set_time_limit(120);
foreach ($timeslots as $ts) {
    $row++;
    $col = 1;
    foreach ($columns as $c) {
        $value = $ts[$c];
        //Format special values
        if ($c == 'is_out') {
            if ($value == 0) {
                $value = 'E';
            } else {
                $value = 'U';
            }
        } elseif ($c == 'valid_from' && !is_null($value)) {
            $time = new Time($value);
            $t = $time->toUnixString();
            $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($t);
            $sheet->getCellByColumnAndRow($col, $row)->getStyle()
        ->getNumberFormat()
        ->setFormatCode(
            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
        );
        } elseif ($c == 'slot' && !is_null($value)) {
            $time = new Time($value);
            $t = $time->toUnixString();
            $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($t);
            $sheet->getCellByColumnAndRow($col, $row)->getStyle()
        ->getNumberFormat()
        ->setFormatCode(
            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_TIME1
        );
        } elseif ($c == 'day') {
            $value = $giorni[$value];
        }

        $sheet->setCellValueByColumnAndRow($col, $row, $value);
        $col++;
    }
}

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
$writer->save('php://output');
// Return response object to prevent controller from trying to render
// a view.
return;
