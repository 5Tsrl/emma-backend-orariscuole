<h1>Esportazione 15 Minuti</h1>
<p>Formato di esportazione con intervalli di 15 minuti raggruppati per sede</p>
<ul>
  <?php for ($day = 1; $day <= 7; $day++) : ?>
    <li><?= $this->Html->link('Scarica il file xls giorno: ' . $day, ['action' => 'export-one-line.xls', '?' => ['day' => $day], 'ext' => 'xls']) ?></li>
  <?php endfor ?>
</ul>