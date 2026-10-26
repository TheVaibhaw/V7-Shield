<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('scans');
page_header('Start New Scan', 'Configure what to scan and how. Scans are read-only by default and never modify files.');
echo demo_notice('Submitting this form runs a UI simulation only. No files are read until the backend scanner is connected.');

$types = [
    ['full', 'Full scan', 'All files, patterns, integrity and CMS checks.', 'shield-fill-check'],
    ['quick', 'Quick scan', 'High-risk locations: uploads, cache, recently changed files.', 'lightning'],
    ['integrity', 'Integrity check', 'Compare file hashes with the stored baseline.', 'fingerprint'],
    ['patterns', 'PHP patterns', 'Webshell, obfuscation and dangerous-function heuristics.', 'filetype-php'],
];
?>
<div class="wizard">
  <form class="card" id="scan-form" data-validate data-custom-submit data-confirm="Start this scan? In Demo Mode this only runs a UI simulation." data-confirm-title="Start scan" data-confirm-ok="Start simulation">
    <div class="card-body">
      <h3 class="section-title">1. Target</h3>
      <div class="form-grid">
        <div class="field">
          <label for="project">Project / website</label>
          <select id="project" name="project" required>
            <option value="">Select a project…</option>
            <?php foreach ($demo['projects'] as $p): ?><option value="<?= e($p['id']) ?>"><?= e($p['name']) ?> (<?= e($p['type']) ?>)</option><?php endforeach; ?>
          </select>
          <span class="error">Choose the project to scan.</span>
        </div>
        <div class="field">
          <label for="scope_path">Scope (sub-path, optional)</label>
          <input id="scope_path" name="scope_path" type="text" placeholder="e.g. wp-content/" maxlength="200" data-pattern="^[A-Za-z0-9_\-./]*$">
          <small>Relative to the project root. Letters, numbers, <code>_ - . /</code> only.</small>
          <span class="error">Use a relative path without special characters.</span>
        </div>
      </div>

      <h3 class="section-title">2. Scan type</h3>
      <div class="choice-grid">
        <?php foreach ($types as $i => [$val, $label, $desc, $ico]): ?>
          <label class="choice"><input type="radio" name="scan_type" value="<?= e($val) ?>" data-label="<?= e($label) ?>"<?= $i === 0 ? ' checked' : '' ?>>
            <span><strong><?= icon($ico) ?> <?= e($label) ?></strong><small><?= e($desc) ?></small></span></label>
        <?php endforeach; ?>
      </div>

      <h3 class="section-title">3. Checks</h3>
      <div class="field">
        <span class="label">CMS-specific checks</span>
        <div class="choice-grid" data-require-one>
          <label class="choice"><input type="checkbox" name="checks" value="WordPress core" checked><span><strong>WordPress</strong><small>Core checksums, plugins, themes, admin users.</small></span></label>
          <label class="choice"><input type="checkbox" name="checks" value="Joomla core"><span><strong>Joomla</strong><small>Core files, extensions, templates.</small></span></label>
          <label class="choice"><input type="checkbox" name="checks" value="Generic PHP" checked><span><strong>Generic PHP</strong><small>Patterns, obfuscation, executables.</small></span></label>
        </div>
        <span class="error">Select at least one check group.</span>
      </div>
      <label class="switch"><span><strong>Include database checks</strong><small>Read-only queries for injected scripts and suspicious options. Requires a configured read-only DB user.</small></span><input type="checkbox" name="db_checks" aria-label="Include database checks"></label>

      <h3 class="section-title">4. Options</h3>
      <div class="form-grid">
        <div class="field full">
          <label for="exclusions">File exclusions (one glob per line)</label>
          <textarea id="exclusions" name="exclusions" maxlength="2000">node_modules/
vendor/
*.log
wp-content/cache/</textarea>
        </div>
        <div class="field">
          <label for="max_size">Max file size (MB)</label>
          <input id="max_size" name="max_size" type="number" min="1" max="512" value="25" required>
          <span class="error">Enter a value between 1 and 512.</span>
        </div>
        <div class="field">
          <label for="mode">Mode</label>
          <select id="mode" name="mode">
            <option value="readonly" selected>Read-only (recommended)</option>
            <option value="suggest">Read-only + quarantine suggestions</option>
          </select>
          <small>V7-Shield never deletes or edits files automatically.</small>
        </div>
      </div>
    </div>
    <div class="card-body" style="border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
      <button type="reset" class="btn btn-ghost">Reset</button>
      <button type="submit" class="btn btn-primary"><?= icon('play-fill') ?> Start scan</button>
    </div>
  </form>

  <div class="wizard-summary grid">
    <section class="card">
      <header class="card-head"><h2><?= icon('clipboard-check') ?> Confirmation summary</h2></header>
      <div class="card-body"><dl class="kv" id="scan-summary"></dl></div>
    </section>
    <section class="card" id="scan-sim" hidden>
      <header class="card-head"><h2><?= icon('activity') ?> Scan progress</h2><span data-sim-status></span></header>
      <div class="card-body">
        <p class="hint" style="margin-top:0"><?= icon('cone-striped') ?> UI simulation — no files are being read.</p>
        <?= progress_bar(0, 'running') ?>
        <dl class="kv">
          <div><dt>Progress</dt><dd data-sim-pct>0%</dd></div>
          <div><dt>Phase</dt><dd data-sim-phase>Starting</dd></div>
          <div><dt>Items processed</dt><dd data-sim-files>0</dd></div>
          <div><dt>Elapsed</dt><dd data-sim-elapsed>0m 00s</dd></div>
        </dl>
        <div class="scan-log" aria-live="polite"></div>
        <div class="scan-card-actions" style="margin-top:12px">
          <button type="button" class="btn btn-ghost btn-sm" data-sim-action="pause"><?= icon('pause-fill') ?> Pause</button>
          <button type="button" class="btn btn-ghost btn-sm" data-sim-action="cancel"><?= icon('x-circle') ?> Cancel</button>
          <a class="btn btn-ghost btn-sm" href="<?= e(page_url('active-scans')) ?>"><?= icon('activity') ?> Active scans</a>
        </div>
      </div>
    </section>
  </div>
</div>
<?php render_footer();
