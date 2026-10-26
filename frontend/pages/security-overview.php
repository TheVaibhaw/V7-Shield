<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('overview');
page_header('Security Overview', 'Posture summary across all projects, grouped by severity, category and project.', '<a class="btn btn-ghost" href="' . e(page_url('reports')) . '">' . icon('file-earmark-bar-graph') . ' Build report</a>');
echo demo_notice();
$f = $demo['findings'];
$bySev = array_count_values(array_column($f, 'severity'));
$byCat = array_count_values(array_column($f, 'category'));
arsort($byCat);
$byProj = array_count_values(array_column($f, 'project'));
$sevOrder = ['Critical', 'High', 'Medium', 'Low'];
?>
<div class="grid grid-3">
  <?php card_open('Severity distribution', 'pie-chart'); ?>
    <div class="chart-box" style="height:240px"><canvas data-chart="doughnut" aria-label="Findings by severity" role="img"
      data-labels='<?= e(json_encode($sevOrder)) ?>' data-values='<?= e(json_encode(array_map(fn($s) => $bySev[$s] ?? 0, $sevOrder))) ?>'
      data-colors='["--red","--orange","--amber","--blue"]'></canvas></div>
  <?php card_close(); ?>
  <?php card_open('Findings by category', 'bar-chart'); ?>
    <div class="chart-box" style="height:240px"><canvas data-chart="bar" aria-label="Findings by category" role="img"
      data-labels='<?= e(json_encode(array_keys(array_slice($byCat, 0, 7)))) ?>' data-values='<?= e(json_encode(array_values(array_slice($byCat, 0, 7)))) ?>'></canvas></div>
  <?php card_close(); ?>
  <?php card_open('Project health', 'globe2'); ?>
    <ul class="check-list">
      <?php foreach ($demo['projects'] as $p): ?>
        <li><div><strong><?= e($p['name']) ?></strong><span class="muted"><?= e($p['type']) ?> · <?= (int) ($byProj[$p['name']] ?? 0) ?> open findings</span></div><?= status_badge($p['status']) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php card_close(); ?>
</div>
<?php card_open('Control checklist', 'ui-checks-grid'); ?>
<div class="grid grid-2">
  <?php check_list([
      ['Scheduled scanning', 'Active', '6 of 7 projects have an active schedule.'],
      ['Integrity baselines', 'Active', 'Baselines exist for all projects.'],
      ['PHP execution blocked in uploads', 'Warning', '2 projects allow PHP in upload folders.'],
      ['CMS core up to date', 'Warning', 'Partner Intranet core files modified.'],
  ]); ?>
  <?php check_list([
      ['Admin accounts reviewed', 'Needs Attention', '1 unexpected administrator detected.'],
      ['Debug mode disabled', 'Warning', 'WP_DEBUG enabled on Northwind Store.'],
      ['Database checks', 'Failed', 'Last DB check failed — connection timeout.'],
      ['Notifications configured', 'Passed', 'Email alerts for Critical and High.'],
  ]); ?>
</div>
<?php card_close(); render_footer();
