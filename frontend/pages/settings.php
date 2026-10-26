<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use V7Shield\Service\ScanService;

$s = $v7->get('scanner');
$rules = $v7->get('rules');
[, $dbError] = try_backend(fn($svc) => $svc->history(1, null, 1));

render_header('settings');
page_header('Settings', 'Console preferences and the active scanner configuration.');
echo notice('info', 'Scanner settings are read-only here. They are changed in <code>backend/config/config.local.php</code> by a server administrator, so a browser session cannot widen what may be scanned.');
?>
<div class="grid grid-2">
  <?php card_open('Appearance', 'palette'); ?>
    <div class="choice-grid" role="radiogroup" aria-label="Theme">
      <label class="choice"><input type="radio" name="theme" value="dark" data-theme-choice><span><strong><?= icon('moon-stars') ?> Dark</strong><small>Default theme.</small></span></label>
      <label class="choice"><input type="radio" name="theme" value="light" data-theme-choice><span><strong><?= icon('sun') ?> Light</strong><small>For bright environments.</small></span></label>
    </div>
    <label class="switch"><span><strong>Compact sidebar</strong><small>Icons only on desktop.</small></span><input type="checkbox" data-compact-choice aria-label="Compact sidebar"></label>
    <p class="hint"><?= icon('info-circle') ?> Saved in this browser only.</p>
  <?php card_close(); ?>

  <?php card_open('System status', 'heart-pulse'); ?>
    <ul class="check-list">
      <li><div><strong>Backend configuration</strong><span class="muted">backend/config/config.local.php</span></div><?= status_badge($v7->get('is_configured') ? 'configured' : 'missing') ?></li>
      <li><div><strong>Database</strong><span class="muted"><?= $dbError ? e($dbError) : 'Connected' ?></span></div><?= status_badge($dbError ? 'error' : 'passed') ?></li>
      <li><div><strong>Execution mode</strong><span class="muted"><?= $v7->get('jobs.execution') === 'worker' ? 'Queued for php backend/bin/worker.php' : 'Inline (bounded, runs in the browser request)' ?></span></div><?= status_badge('enabled') ?></li>
      <li><div><strong>Rate limit</strong><span class="muted"><?= (int) $v7->get('jobs.max_starts_per_hour') ?> scan starts per user per hour; one active scan per target</span></div><?= status_badge('enabled') ?></li>
      <li><div><strong>Signed in as</strong><span class="muted">Session expires after <?= (int) ($v7->get('security.session_lifetime') / 60) ?> min of inactivity</span></div><strong><?= e($auth->user()) ?></strong></li>
    </ul>
  <?php card_close(); ?>
</div>

<div class="grid grid-2">
  <?php card_open('Scan targets', 'folder2-open'); ?>
    <?php $targets = $v7->get('targets', []); if (!$targets): ?>
      <?= empty_state('folder-x', 'No targets configured.') ?>
    <?php else: ?>
      <ul class="check-list">
        <?php foreach ($targets as $id => $t):
            $ok = true;
            try { ScanService::resolveConfiguredTarget($v7, (string) $id); } catch (Throwable) { $ok = false; } ?>
          <li><div><strong><?= e($t['label'] ?? $id) ?></strong><span class="muted">ID: <?= e($id) ?></span></div><?= status_badge($ok ? 'passed' : 'missing') ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="hint"><?= icon('shield-lock') ?> Server paths are not shown in the browser.</p>
    <?php endif; ?>
  <?php card_close(); ?>

  <?php card_open('Scanner limits', 'sliders'); ?>
    <dl class="kv">
      <div><dt>Max file size</dt><dd><?= e(fmt_bytes((int) $s['max_file_size'])) ?></dd></div>
      <div><dt>Max directory depth</dt><dd><?= (int) $s['max_depth'] ?></dd></div>
      <div><dt>Max files per scan</dt><dd><?= e(number_format((int) $s['max_files'])) ?></dd></div>
      <div><dt>Max duration</dt><dd><?= (int) $s['max_duration'] ?> s</dd></div>
      <div><dt>Max findings per file</dt><dd><?= (int) $s['max_findings_per_file'] ?></dd></div>
      <div><dt>Symbolic links</dt><dd>Never followed</dd></div>
    </dl>
    <h3 class="section-title">Exclusions</h3>
    <?php foreach ($s['exclusions'] as $x): ?><span class="tag"><?= e($x) ?></span><?php endforeach; ?>
  <?php card_close(); ?>
</div>

<?php card_open('Detection rules (' . e($rules['version']) . ')', 'list-check'); ?>
  <?= notice('warning', 'These heuristics flag <strong>suspicious</strong> code. They will miss some malware and can flag legitimate code. Review the "known false positives" for each rule.', 'exclamation-triangle') ?>
  <div class="table-wrap" style="margin-top:14px"><table class="data-table" id="rules-table" data-per-page="50">
    <thead><tr><th scope="col" data-sort="id" tabindex="0">Rule</th><th scope="col" data-sort="c" tabindex="0">Category</th><th scope="col" data-sort="s" tabindex="0">Severity</th><th scope="col" data-sort="f" tabindex="0">Confidence</th><th scope="col">Known false positives</th></tr></thead>
    <tbody>
    <?php
    $all = $rules['rules'];
    foreach ($rules['builtin'] as $bid => $b) {
        $all[] = ['id' => $bid] + $b;
    }
    foreach ($all as $r): ?>
      <tr>
        <td data-label="Rule" data-value="<?= e($r['id']) ?>"><strong><?= e($r['name']) ?></strong><small class="muted"><?= e($r['id']) ?> — <?= e($r['description']) ?></small></td>
        <td data-label="Category" data-value="<?= e($r['category']) ?>"><?= e($r['category']) ?></td>
        <td data-label="Severity" data-value="<?= V7Shield\Scanner\Scanner::SEVERITY_RANK[$r['severity']] ?>"><?= sev_badge($r['severity']) ?></td>
        <td data-label="Confidence" data-value="<?= (int) $r['confidence'] ?>"><?= (int) $r['confidence'] ?>%</td>
        <td data-label="False positives" data-value=""><small><?= e($r['false_positives']) ?></small></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php card_close(); ?>
<?php render_footer();
