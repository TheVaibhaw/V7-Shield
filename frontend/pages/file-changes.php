<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('file-changes');
page_header('File Changes', 'Chronological list of file-system changes detected between integrity checks.', export_buttons('changes-table'));
echo demo_notice();
$changes = $demo['integrity']['changes'];
$byDay = [];
foreach ($changes as $c) {
    $day = substr($c['when'], 0, 10);
    $byDay[$day] = ($byDay[$day] ?? 0) + 1;
}
ksort($byDay);
?>
<div class="grid grid-2">
  <?php card_open('Changes per day', 'bar-chart'); ?>
    <div class="chart-box" style="height:220px"><canvas data-chart="bar" data-color="--amber" aria-label="File changes per day" role="img" data-labels='<?= e(json_encode(array_keys($byDay))) ?>' data-values='<?= e(json_encode(array_values($byDay))) ?>'></canvas></div>
  <?php card_close(); ?>
  <?php card_open('How to read this', 'lightbulb'); ?>
    <ul class="prose">
      <li><span class="chg chg-added">Added</span> — new files. Pay attention to scripts in upload/cache folders.</li>
      <li><span class="chg chg-modified">Modified</span> — content changed. Expected after updates; unexpected in core files.</li>
      <li><span class="chg chg-deleted">Deleted</span> — removed files. Attackers sometimes delete logs.</li>
    </ul>
  <?php card_close(); ?>
</div>
<?php card_open('All changes', 'file-diff');
data_table('changes-table', change_columns(), $changes, ['change' => ['Added', 'Modified', 'Deleted'], 'project' => array_values(array_unique(array_column($changes, 'project')))]);
card_close();
render_footer();
