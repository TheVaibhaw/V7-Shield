<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use V7Shield\Security\Csrf;

$targets = $v7->targetLabels();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid(str_param($_POST, 'csrf'))) {
        $error = 'Your session expired. Reload the page and try again.';
    } else {
        [$id, $error] = try_backend(fn($svc) => $svc->start(str_param($_POST, 'target') ?? '', (string) $auth->user(), $_SERVER['REMOTE_ADDR'] ?? null));
        if ($error === null) {
            header('Location: ' . page_url('scan', ['id' => $id]), true, 303);
            exit;
        }
    }
}

$s = $v7->get('scanner');
render_header('scans');
page_header('Start Malware Scan', 'Run a read-only heuristic scan of a configured project directory.');
if ($error) {
    echo notice('danger', e($error), 'exclamation-octagon');
}
?>
<div class="wizard">
  <section class="card">
    <?php if (!$targets): ?>
      <div class="card-body">
        <?= empty_state('folder-x', 'No scan targets are configured.') ?>
        <p class="muted">For safety, directories cannot be entered in the browser. An administrator must add them to <code>targets</code> in <code>backend/config/config.local.php</code>:</p>
        <pre class="evidence">'targets' => [
    'my-site' => ['label' => 'My site', 'path' => '/var/www/my-site'],
],</pre>
      </div>
    <?php else: ?>
      <form method="post" class="card-body" data-start-form>
        <?= csrf_field() ?>
        <h3 class="section-title">Target</h3>
        <div class="field">
          <label for="target">Project directory</label>
          <select id="target" name="target" required>
            <option value="">Select a configured target…</option>
            <?php foreach ($targets as $id => $label): ?><option value="<?= e($id) ?>"><?= e($label) ?> (<?= e($id) ?>)</option><?php endforeach; ?>
          </select>
          <small>Only directories configured by an administrator can be scanned.</small>
          <span class="error">Choose a target.</span>
        </div>
        <h3 class="section-title">What will be checked</h3>
        <ul class="check-list">
          <li><div><strong>Suspicious PHP patterns</strong><span class="muted">Request data reaching eval, shell commands, includes or file writes.</span></div></li>
          <li><div><strong>Obfuscation</strong><span class="muted">Decoded payload evaluation, decode chains, long encoded/escaped strings.</span></div></li>
          <li><div><strong>File signatures</strong><span class="muted">PHP inside images or text files, extension/content mismatches, executables.</span></div></li>
        </ul>
        <div style="display:flex;justify-content:flex-end;margin-top:18px">
          <button type="submit" class="btn btn-primary btn-lg"><?= icon('play-fill') ?> Start scan</button>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <div class="wizard-summary grid">
    <?php card_open('Scan scope', 'sliders'); ?>
      <dl class="kv">
        <div><dt>Mode</dt><dd>Read-only (files are never modified)</dd></div>
        <div><dt>Max file size</dt><dd><?= e(fmt_bytes((int) $s['max_file_size'])) ?></dd></div>
        <div><dt>Max depth</dt><dd><?= (int) $s['max_depth'] ?> levels</dd></div>
        <div><dt>Max files</dt><dd><?= e(number_format((int) $s['max_files'])) ?></dd></div>
        <div><dt>Time limit</dt><dd><?= (int) $s['max_duration'] ?> s</dd></div>
        <div><dt>Symbolic links</dt><dd>Skipped</dd></div>
        <div><dt>Hidden files</dt><dd>Included</dd></div>
        <div><dt>Execution</dt><dd><?= $v7->get('jobs.execution') === 'worker' ? 'CLI worker (queued)' : 'In this browser session' ?></dd></div>
      </dl>
      <h3 class="section-title">Exclusions</h3>
      <?php foreach ($s['exclusions'] as $x): ?><span class="tag"><?= e($x) ?></span><?php endforeach; ?>
      <p class="hint"><?= icon('info-circle') ?> Limits and exclusions are set in the backend configuration.</p>
    <?php card_close(); ?>
  </div>
</div>
<?php render_footer();
