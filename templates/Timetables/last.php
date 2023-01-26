<h1>Show the last timetable</h1>

<?php // pr($timetable);
?>
<?= $timetable->valid_from ?><br>
<?= $timetable->type ?>

<h2>School</h2>
School name: <?= $timetable->office->company->name ?><br>
Branch name: <?= $timetable->office->name ?><br>