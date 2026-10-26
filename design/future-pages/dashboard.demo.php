<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$s = $demo['score'];
$circ = 2 * M_PI * 52;
render_header('dashboard');
?>
<?= demo_notice('The dashboard shows sample statistics for UI demonstration only. They do not represent a real scan.') ?>

<section class="welcome">
  <div>
    <span class="eyebrow"><?= icon('shield-lock') ?> V7-Shield Console</span>
    <h1>Security Overview</h1>
    <p class="muted">Monitor scans, threat detections and file integrity across your WordPress, Joomla and custom PHP projects.</p>
    <div class="welcome-meta">
      <span><?= icon('clock') ?> Last scan: <strong>Today, 08:42</strong> (Acme Marketing Site)</span>
      <label class="select-field"><span class="sr-only">Time period</span>
        <select data-period-select><option value="24h">Last 24 hours</option><option value="7d" selected>Last 7 days</option><option value="30d">Last 30 days</option><option value="90d">Last 90 days</option></select>
      </label>
    </div>
  </div>
  <div class="welcome-actions">
    <a class="btn btn-primary btn-lg" href="<?= e(page_url('scans')) ?>"><?= icon('play-fill') ?> Start New Scan</a>
    <a class="btn btn-ghost btn-lg" href="<?= e(page_url('reports')) ?>"><?= icon('file-earmark-bar-graph') ?> View Reports</a>
  </div>
</section>

<div class="grid dash-top">
  <section class="card posture">
    <header class="card-head"><h2><?= icon('shield-check') ?> Security Posture</h2><?= status_badge($s['label']) ?></header>
    <div class="posture-body">
      <div class="score-ring" style="--pct: <?= (int) $s['value'] ?>">
        <svg viewBox="0 0 120 120" aria-hidden="true">
          <circle cx="60" cy="60" r="52" class="ring-bg"/>
          <circle cx="60" cy="60" r="52" class="ring-fg" stroke-dasharray="<?= round($circ, 2) ?>" stroke-dashoffset="<?= round($circ * (1 - $s['value'] / 100), 2) ?>"/>
        </svg>
        <div class="score-text"><strong><?= (int) $s['value'] ?></strong><span>/100</span></div>
      </div>
      <div class="posture-detail">
        <div class="posture-counts">
          <div class="pc crit"><strong><?= $s['critical'] ?></strong><span>Critical</span></div>
          <div class="pc high"><strong><?= $s['high'] ?></strong><span>High risk</span></div>
          <div class="pc med"><strong><?= $s['warnings'] ?></strong><span>Warnings</span></div>
          <div class="pc ok"><strong><?= $s['passed'] ?></strong><span>Passed checks</span></div>
        </div>
        <p class="posture-note"><?= icon('exclamation-triangle') ?> <span><strong>Top priority:</strong> a possible webshell indicator in <code>wp-content/uploads</code> on Northwind Store, and a PHP payload disguised as a JPEG in the shared uploads directory. Review both before the next deployment.</span></p>
        <a class="link-arrow" href="<?= e(page_url('findings')) ?>">Review critical findings <?= icon('arrow-right') ?></a>
      </div>
    </div>
  </section>

  <section class="card quick-actions">
    <header class="card-head"><h2><?= icon('lightning-charge') ?> Quick Actions</h2></header>
    <div class="qa-grid">
      <a href="<?= e(page_url('scans')) ?>?type=full" class="qa"><?= icon('shield-fill-check') ?><span>Full scan</span></a>
      <a href="<?= e(page_url('scans')) ?>?type=quick" class="qa"><?= icon('lightning') ?><span>Quick scan</span></a>
      <a href="<?= e(page_url('findings')) ?>?severity=critical" class="qa"><?= icon('exclamation-octagon') ?><span>Critical findings</span></a>
      <a href="<?= e(page_url('file-integrity')) ?>" class="qa"><?= icon('fingerprint') ?><span>File integrity</span></a>
      <a href="<?= e(page_url('reports')) ?>" class="qa"><?= icon('file-earmark-pdf') ?><span>Generate report</span></a>
      <a href="<?= e(page_url('projects')) ?>" class="qa"><?= icon('sliders') ?><span>Configure project</span></a>
      <a href="<?= e(page_url('help')) ?>#install" class="qa"><?= icon('book') ?><span>Install docs</span></a>
    </div>
  </section>
</div>

<div class="stat-grid">
  <?php foreach ($demo['metrics'] as $m) echo stat_card(...$m); ?>
</div>

<div class="grid dash-mid">
  <section class="card chart-card">
    <header class="card-head"><h2><?= icon('graph-up') ?> Threat Activity</h2>
      <div class="seg" role="tablist" aria-label="Chart period" data-chart-range>
        <?php foreach (['24h' => '24H', '7d' => '7D', '30d' => '30D', '90d' => '90D'] as $k => $l): ?>
          <button type="button" role="tab" data-range="<?= $k ?>" aria-selected="<?= $k === '7d' ? 'true' : 'false' ?>"><?= $l ?></button>
        <?php endforeach; ?>
        <button type="button" role="tab" data-range="empty" aria-selected="false" title="Preview the empty state">Empty</button>
      </div>
    </header>
    <div class="card-body">
      <div class="chart-box"><canvas id="threat-chart" aria-label="Findings over time by severity" role="img"></canvas>
        <div class="empty-state chart-empty" hidden><?= icon('graph-down') ?><p>No findings in this period.</p></div>
        <div class="loading chart-loading"><span class="spinner"></span> Loading chart…</div>
      </div>
      <script type="application/json" id="threat-series"><?= json_encode($demo['series'], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
  </section>

  <section class="card">
    <header class="card-head"><h2><?= icon('fingerprint') ?> File Integrity</h2><a class="link-arrow" href="<?= e(page_url('file-integrity')) ?>">Details <?= icon('arrow-right') ?></a></header>
    <div class="card-body">
      <?php $i = $demo['integrity']['summary']; ?>
      <div class="mini-stats">
        <div><strong class="t-green">+<?= $i['added'] ?></strong><span>Added</span></div>
        <div><strong class="t-amber">~<?= $i['modified'] ?></strong><span>Modified</span></div>
        <div><strong class="t-red">−<?= $i['deleted'] ?></strong><span>Deleted</span></div>
        <div><strong class="t-cyan"><?= $i['hash_changed'] ?></strong><span>Hash changed</span></div>
      </div>
      <dl class="kv">
        <div><dt>Baseline</dt><dd><?= status_badge($i['baseline']) ?> since <?= e($i['baseline_date']) ?></dd></div>
        <div><dt>Last integrity check</dt><dd><?= e($i['last_check']) ?></dd></div>
      </dl>
      <ul class="change-list">
        <?php foreach (array_slice($demo['integrity']['changes'], 0, 4) as $c): ?>
          <li><span class="chg chg-<?= strtolower($c['change']) ?>"><?= e($c['change']) ?></span><code title="<?= e($c['file']) ?>"><?= e($c['file']) ?></code></li>
        <?php endforeach; ?>
      </ul>
      <p class="hint"><?= icon('info-circle') ?> Changes are compared against a known baseline. A changed file is not automatically malware — it may be a legitimate update.</p>
    </div>
  </section>
</div>

<?php card_open('Recent Scans', 'clock-history', '<a class="link-arrow" href="' . e(page_url('scan-history')) . '">All scans ' . icon('arrow-right') . '</a>'); ?>
<?php data_table('recent-scans', [
    'name' => ['Scan', fn($r) => '<strong>' . e($r['name']) . '</strong>'],
    'project' => ['Project'],
    'type' => ['Type'],
    'status' => ['Status', fn($r) => status_badge($r['status'])],
    'progress' => ['Progress', fn($r) => progress_bar($r['progress'], strtolower($r['status'])) . '<small class="muted">' . $r['progress'] . '%</small>'],
    'files' => ['Files', fn($r) => e(number_format($r['files']))],
    'findings' => ['Findings'],
    'started' => ['Started'],
    'duration' => ['Duration'],
    '_act' => ['', fn($r) => detail_button($r['name'], $r)],
], $demo['scans'], ['status' => ['Completed', 'Running', 'Queued', 'Failed', 'Cancelled']], 5); ?>
<?php card_close(); ?>

<?php card_open('Recent Threat Detections', 'bug', '<a class="link-arrow" href="' . e(page_url('findings')) . '">All findings ' . icon('arrow-right') . '</a>'); ?>
<?php data_table('recent-findings', [
    'severity' => ['Severity', fn($r) => sev_badge($r['severity'])],
    'title' => ['Finding', fn($r) => '<strong>' . e($r['title']) . '</strong>'],
    'resource' => ['Affected resource', fn($r) => '<code class="trunc" title="' . e($r['resource']) . '">' . e($r['resource']) . '</code>'],
    'project' => ['Project'],
    'category' => ['Category'],
    'detected' => ['Detected'],
    'status' => ['Review', fn($r) => status_badge($r['status'])],
    '_act' => ['', fn($r) => detail_button($r['title'], $r)],
], $demo['findings'], ['severity' => ['Critical', 'High', 'Medium', 'Low'], 'status' => ['New', 'In Review', 'Resolved', 'False Positive']], 6); ?>
<?php card_close(); ?>

<div class="grid dash-bottom">
  <section class="card">
    <header class="card-head"><h2><?= icon('globe2') ?> Websites &amp; Projects</h2><a class="link-arrow" href="<?= e(page_url('projects')) ?>">Manage <?= icon('arrow-right') ?></a></header>
    <ul class="project-list">
      <?php foreach ($demo['projects'] as $p):
          $ico = ['WordPress' => 'wordpress', 'Joomla' => 'boxes', 'Custom PHP' => 'filetype-php', 'Directory' => 'folder2'][$p['type']]; ?>
        <li>
          <span class="proj-icon"><?= icon($ico) ?></span>
          <div class="proj-main"><strong><?= e($p['name']) ?></strong><small><?= e($p['type']) ?> · <code><?= e($p['location']) ?></code></small></div>
          <div class="proj-meta"><?= status_badge($p['status']) ?><small><?= (int) $p['findings'] ?> findings · <?= e($p['last_scan']) ?></small></div>
          <?= detail_button($p['name'], $p) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="card">
    <header class="card-head"><h2><?= icon('broadcast') ?> Security Events</h2><a class="link-arrow" href="<?= e(page_url('security-events')) ?>">Timeline <?= icon('arrow-right') ?></a></header>
    <ol class="timeline">
      <?php foreach (array_slice($demo['events'], 0, 7) as $ev): ?>
        <li class="tl-<?= severity_class($ev['severity']) ?>">
          <span class="tl-dot"></span>
          <div><div class="tl-top"><strong><?= e($ev['title']) ?></strong><time><?= e(substr($ev['time'], 5)) ?></time></div>
          <p><?= e($ev['desc']) ?></p>
          <small><?= e($ev['category']) ?> · <?= e($ev['project']) ?> · <?= sev_badge($ev['severity']) ?> <a href="<?= e(page_url('security-events')) ?>">Details</a></small></div>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="card">
    <header class="card-head"><h2><?= icon('signpost-split') ?> Recommended Next Steps</h2></header>
    <ol class="steps">
      <li><strong>Investigate the uploads webshell indicator.</strong><span>Compare the file with backups and server logs before removing anything.</span><a href="<?= e(page_url('malware-analysis')) ?>">Open analysis</a></li>
      <li><strong>Block PHP execution in upload directories.</strong><span>Add a server rule denying <code>.php</code>/<code>.phtml</code> execution under <code>uploads/</code>.</span><a href="<?= e(page_url('help')) ?>#hardening">How-to</a></li>
      <li><strong>Verify the new administrator account.</strong><span>Confirm "support_tmp" with the site owner; disable it if unknown.</span><a href="<?= e(page_url('wordpress')) ?>">WordPress checks</a></li>
      <li><strong>Update outdated plugins.</strong><span>contact-form-x has public advisories; update after testing on staging.</span><a href="<?= e(page_url('wordpress')) ?>">View plugins</a></li>
      <li><strong>Re-run the failed database check.</strong><span>Verify the read-only DB user and network access.</span><a href="<?= e(page_url('database')) ?>">Database security</a></li>
    </ol>
  </section>
</div>
<?php render_footer(); ?>
