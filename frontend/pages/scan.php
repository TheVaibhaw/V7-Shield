<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$id = int_param($_GET, 'id', 0, 1);
[$job, $error] = $id ? try_backend(fn($svc) => $svc->status($id)) : [null, 'Scan not found.'];

render_header('scan', $job ? 'Scan #' . $job['id'] : 'Scan Details');
if ($error) {
    echo notice('danger', e($error), 'exclamation-octagon');
    echo '<p><a class="btn btn-ghost" href="' . e(page_url('scan-history')) . '">' . icon('arrow-left') . ' Back to history</a></p>';
    render_footer();
    return;
}
$active = in_array($job['status'], ['queued', 'running'], true);
$inline = $v7->get('jobs.execution') === 'inline';
page_header('Scan #' . $job['id'] . ' — ' . $job['target_label'], 'Started by ' . $job['requested_by'] . ' · rules ' . (string) $job['rules_version'],
    '<a class="btn btn-ghost" href="' . e(page_url('scan-history')) . '">' . icon('clock-history') . ' History</a>'
    . (!$active ? '<a class="btn btn-primary" href="' . e(page_url('findings', ['scan_id' => $job['id']])) . '">' . icon('bug') . ' View findings</a>' : ''));
?>
<section class="card" id="scan-live" data-scan-id="<?= (int) $job['id'] ?>" data-status="<?= e($job['status']) ?>"
  data-autorun="<?= $active && $inline && $job['status'] === 'queued' && $job['requested_by'] === $auth->user() ? '1' : '0' ?>">
  <header class="card-head"><h2><?= icon('activity') ?> Status</h2><span data-live="status"><?= scan_status_label($job) ?></span></header>
  <div class="card-body live-status">
    <?php if ($active): ?>
      <div class="progress running" role="progressbar" aria-label="Scan in progress" aria-busy="true"><span style="width:100%"></span></div>
      <p class="muted" data-live="message"><?= $job['status'] === 'queued'
          ? ($inline ? 'Starting scan…' : 'Waiting for the CLI worker (php backend/bin/worker.php).')
          : 'Scanning… counts below update as files are processed.' ?></p>
    <?php elseif ($job['status'] === 'failed'): ?>
      <?= notice('danger', '<strong>Scan failed.</strong> ' . e($job['error_message'] ?? 'Unknown error.'), 'x-octagon') ?>
    <?php elseif ($job['limit_reached']): ?>
      <?= notice('warning', '<strong>Partial scan.</strong> A configured limit was reached (' . e($job['limit_reached']) . '). Files after that point were not scanned.', 'exclamation-triangle') ?>
    <?php else: ?>
      <?= notice($job['findings_count'] ? 'warning' : 'success', $job['findings_count']
          ? '<strong>' . (int) $job['findings_count'] . ' suspicious indicator(s) found.</strong> Review each one; heuristic matches can be false positives.'
          : '<strong>No rule matched.</strong> This does not guarantee the files are clean — the rule set is limited.', $job['findings_count'] ? 'exclamation-triangle' : 'check-circle') ?>
    <?php endif; ?>
    <div class="stat-grid">
      <?= stat_card('files', 'Files scanned', number_format($job['files_scanned']), 'cyan') ?>
      <?= stat_card('skip-forward', 'Skipped', number_format($job['files_skipped']), 'blue', 'Excluded, oversized, empty, unreadable or symlinked entries.') ?>
      <?= stat_card('bug', 'Findings', number_format($job['findings_count']), $job['findings_count'] ? 'red' : 'green') ?>
      <?= stat_card('exclamation-octagon', 'Errors', number_format($job['errors_count']), $job['errors_count'] ? 'amber' : 'green', 'Unreadable files or directories, and read errors.') ?>
    </div>
    <dl class="kv">
      <div><dt>Created</dt><dd><?= fmt_dt($job['created_at']) ?></dd></div>
      <div><dt>Started</dt><dd><?= fmt_dt($job['started_at']) ?></dd></div>
      <div><dt>Finished</dt><dd><?= fmt_dt($job['finished_at']) ?></dd></div>
      <div><dt>Duration</dt><dd><?= e(fmt_duration($job['started_at'], $job['finished_at'])) ?></dd></div>
      <div><dt>Data scanned</dt><dd><?= e(fmt_bytes($job['bytes_scanned'])) ?></dd></div>
    </dl>
  </div>
</section>

<?php if (!$active):
    [$skips] = try_backend(fn($svc) => $svc->skips($job['id']));
    $sev = ['critical' => $job['critical'], 'high' => $job['high'], 'medium' => $job['medium'], 'low' => $job['low']]; ?>
<div class="grid grid-2">
  <?php card_open('Findings by severity', 'bar-chart'); ?>
    <?php if ($job['findings_count'] === 0): ?>
      <?= empty_state('shield-check', 'No findings in this scan.') ?>
    <?php else: ?>
      <div class="sev-bars">
        <?php foreach ($sev as $k => $n): ?>
          <a class="sev-bar" href="<?= e(page_url('findings', ['scan_id' => $job['id'], 'severity' => $k])) ?>"><span><?= e(ucfirst($k)) ?></span>
            <div class="track"><span style="width:<?= round($n / max(1, $job['findings_count']) * 100) ?>%;background:var(--<?= ['critical' => 'red', 'high' => 'orange', 'medium' => 'amber', 'low' => 'blue'][$k] ?>)"></span></div><strong><?= (int) $n ?></strong></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php card_close(); ?>
  <?php card_open('Skipped files', 'skip-forward'); ?>
    <?php if (!$job['skip_reasons']): ?>
      <?= empty_state('check2-all', 'Nothing was skipped.') ?>
    <?php else: ?>
      <div style="margin-bottom:10px"><?php foreach ($job['skip_reasons'] as $r => $n): ?><span class="tag"><?= e($r) ?>: <?= (int) $n ?></span><?php endforeach; ?></div>
      <div class="table-wrap"><table class="data-table" id="skips-table" data-per-page="10"><thead><tr><th scope="col" data-sort="p" tabindex="0">Path</th><th scope="col" data-sort="r" tabindex="0">Reason</th><th scope="col">Detail</th></tr></thead><tbody>
        <?php foreach ($skips ?? [] as $sk): ?><tr><td data-label="Path" data-value="<?= e($sk['file_path']) ?>"><code class="trunc" title="<?= e($sk['file_path']) ?>"><?= e($sk['file_path']) ?></code></td><td data-label="Reason" data-value="<?= e($sk['reason']) ?>"><?= e($sk['reason']) ?></td><td data-label="Detail" data-value=""><?= e($sk['detail']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <div class="pager" data-pager-for="skips-table"></div>
      <p class="hint"><?= icon('info-circle') ?> Up to <?= (int) $v7->get('scanner.max_skips_stored') ?> skipped entries are stored per scan. The "Skipped" total above is always exact.</p>
    <?php endif; ?>
  <?php card_close(); ?>
</div>
<?php endif; ?>
<?php render_footer();
