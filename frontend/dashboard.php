<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

[$sum, $error] = try_backend(fn($svc) => $svc->summary());
render_header('dashboard');
?>
<section class="welcome">
  <div>
    <span class="eyebrow"><?= icon('shield-lock') ?> V7-Shield Console</span>
    <h1>Security Overview</h1>
    <p class="muted">Read-only malware heuristics for your configured PHP project directories.</p>
    <?php if ($sum && $sum['latest']): ?>
      <div class="welcome-meta"><span><?= icon('clock') ?> Last completed scan: <strong><?= fmt_dt($sum['latest']['finished_at']) ?></strong> (<?= e($sum['latest']['target_label']) ?>)</span></div>
    <?php endif; ?>
  </div>
  <div class="welcome-actions">
    <a class="btn btn-primary btn-lg" href="<?= e(page_url('scans')) ?>"><?= icon('play-fill') ?> Start Malware Scan</a>
    <a class="btn btn-ghost btn-lg" href="<?= e(page_url('findings')) ?>"><?= icon('bug') ?> View Findings</a>
  </div>
</section>

<?php if ($error): ?>
  <?= notice('danger', '<strong>Backend unavailable.</strong> ' . e($error), 'exclamation-octagon') ?>
<?php elseif (!$v7->get('is_configured') || $sum['targets'] === 0): ?>
  <?= notice('info', '<strong>Setup needed.</strong> No scan targets are configured. Add directories to <code>targets</code> in <code>backend/config/config.local.php</code>, then start a scan.', 'gear') ?>
<?php endif; ?>

<?php if ($sum):
    $l = $sum['latest'];
    $active = ($sum['status_counts']['running'] ?? 0) + ($sum['status_counts']['queued'] ?? 0); ?>
<div class="stat-grid">
  <?= stat_card('files', 'Files in last scan', $l ? number_format($l['files_scanned']) : '—', 'cyan', 'Files read by the most recent completed scan.') ?>
  <?= stat_card('bug', 'Findings in last scan', $l ? number_format($l['findings_count']) : '—', $l && $l['findings_count'] ? 'red' : 'green', 'Suspicious indicators, not confirmed malware.') ?>
  <?= stat_card('exclamation-octagon', 'Critical in last scan', $l ? (string) $l['critical'] : '—', $l && $l['critical'] ? 'red' : 'green') ?>
  <?= stat_card('activity', 'Active scans', (string) $active, $active ? 'cyan' : 'blue', 'Queued or running.') ?>
  <?= stat_card('folder2-open', 'Configured targets', (string) $sum['targets'], 'blue') ?>
  <?= stat_card('check2-circle', 'Completed scans', (string) ($sum['status_counts']['completed'] ?? 0), 'green') ?>
  <?= stat_card('x-octagon', 'Failed scans', (string) ($sum['status_counts']['failed'] ?? 0), ($sum['status_counts']['failed'] ?? 0) ? 'amber' : 'green') ?>
  <?= stat_card('list-check', 'Detection rules', (string) $sum['rule_count'], 'cyan', 'Rule set ' . $sum['rules_version'] . '. Limited heuristics; will not detect all malware.') ?>
</div>

<div class="grid dash-mid">
  <?php card_open('Last scan by severity', 'pie-chart', $l ? '<a class="link-arrow" href="' . e(page_url('scan', ['id' => $l['id']])) . '">Scan #' . $l['id'] . ' ' . icon('arrow-right') . '</a>' : ''); ?>
    <?php if (!$l): ?>
      <?= empty_state('graph-up', 'No completed scans yet.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '">' . icon('play-fill') . ' Run your first scan</a>') ?>
    <?php elseif ($l['findings_count'] === 0): ?>
      <?= empty_state('shield-check', 'The last scan reported no findings. The rule set is limited, so this is not a guarantee.') ?>
    <?php else: ?>
      <div class="chart-box" style="height:260px"><canvas data-chart="doughnut" role="img" aria-label="Findings by severity in the last scan"
        data-labels='<?= e(json_encode(['Critical', 'High', 'Medium', 'Low'])) ?>' data-values='<?= e(json_encode([$l['critical'], $l['high'], $l['medium'], $l['low']])) ?>'
        data-colors='["--red","--orange","--amber","--blue"]'></canvas></div>
    <?php endif; ?>
  <?php card_close(); ?>
  <?php card_open('Last scan by category', 'tags'); ?>
    <?php if (!$sum['latest_categories']): ?>
      <?= empty_state('tags', 'No categorised findings to show.') ?>
    <?php else: ?>
      <ul class="check-list">
        <?php foreach ($sum['latest_categories'] as $cat => $n): ?>
          <li><div><strong><?= e($cat) ?></strong></div><a class="badge sev-neutral" href="<?= e(page_url('findings', ['scan_id' => $l['id'], 'category' => $cat])) ?>"><?= (int) $n ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="hint"><?= icon('info-circle') ?> Findings are heuristic indicators for human review. Verify against trusted copies before acting.</p>
  <?php card_close(); ?>
</div>

<?php card_open('Recent scans', 'clock-history', '<a class="link-arrow" href="' . e(page_url('scan-history')) . '">All scans ' . icon('arrow-right') . '</a>'); ?>
  <?php if (!$sum['recent']): ?>
    <?= empty_state('clock-history', 'No scans have been run yet.') ?>
  <?php else: ?>
    <ul class="check-list">
      <?php foreach ($sum['recent'] as $j): ?>
        <li><div><strong><a href="<?= e(page_url('scan', ['id' => $j['id']])) ?>">#<?= $j['id'] ?> · <?= e($j['target_label']) ?></a></strong>
          <span class="muted"><?= fmt_dt($j['created_at']) ?> · <?= e(number_format($j['files_scanned'])) ?> files</span></div>
          <span style="display:flex;gap:10px;align-items:center"><?= severity_chips($j) ?><?= scan_status_label($j) ?></span></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php card_close(); ?>
<?php endif; ?>
<?php render_footer();
