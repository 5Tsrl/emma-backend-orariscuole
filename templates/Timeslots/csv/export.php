<?php

use Cake\I18n\Time;

$date = new DateTime();
$r = $date->format('Y-m-d');
$filename = "$r-timeslots.csv";
header("Content-Type: application/force-download");
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header('Content-type: text/csv');
header("Content-Type: application/download");;
header("Content-Disposition: attachment;filename=$filename");
header("Content-Transfer-Encoding: binary ");

if (empty($timeslots)) {
    return;
}

$columns = array_keys($timeslots[0]);

// Open a file in write mode ('w')
$fp = fopen('php://output', 'w');
fputcsv($fp, $columns);

foreach ($timeslots as $r) {
    fputcsv($fp, $r);
}

fclose($fp);

// Return response object to prevent controller from trying to render
// a view.
return;
