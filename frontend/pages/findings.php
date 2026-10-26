<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('findings');
page_header('Threat Detections', 'All findings produced by scans. Review each one — heuristics can produce false positives.', export_buttons('findings-table'));
echo demo_notice();
$f = $demo['findings'];
$count = fn(string $sev) => count(rows_where($f, 'severity', [$sev]));
?>
<div class="stat-grid">
  <?= stat_card('exclamation-octagon', 'Critical', (string) $count('Critical'), 'red', '', 'Investigate immediately.') ?>
  <?= stat_card('exclamation-triangle', 'High', (string) $count('High'), 'amber', '', 'Likely malicious or high-impact misconfiguration.') ?>
  <?= stat_card('exclamation-circle', 'Medium', (string) $count('Medium'), 'blue', '', 'Suspicious, needs context.') ?>
  <?= stat_card('info-circle', 'Low', (string) $count('Low'), 'cyan', '', 'Hardening recommendations.') ?>
</div>
<?php
card_open('Findings', 'bug');
data_table('findings-table', finding_columns(), $f, [
    'severity' => ['Critical', 'High', 'Medium', 'Low'],
    'category' => array_values(array_unique(array_column($f, 'category'))),
    'status' => ['New', 'In Review', 'Resolved', 'False Positive'],
], 10);
card_close();
render_footer();
