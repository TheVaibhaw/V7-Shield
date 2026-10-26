<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('settings');
page_header('Settings', 'Preferences for the console, scanning defaults, notifications and integrations.');
echo demo_notice('Settings are validated in the browser but not saved. Appearance settings are remembered locally in this browser.');
$sections = [
    'general' => ['General', 'sliders'], 'appearance' => ['Appearance', 'palette'], 'scan-defaults' => ['Scan defaults', 'play-circle'],
    'exclusions' => ['Exclusions', 'funnel'], 'notify' => ['Notifications', 'bell'], 'report-settings' => ['Reports', 'file-earmark-bar-graph'],
    'integrations' => ['Integrations', 'plug'], 'account' => ['Account', 'person'], 'about' => ['Product info', 'info-circle'],
];
?>
<div class="settings-layout">
  <nav class="card settings-nav" aria-label="Settings sections">
    <?php foreach ($sections as $id => [$t, $ico]): ?><a href="#<?= e($id) ?>"><?= icon($ico) ?> <?= e($t) ?></a><?php endforeach; ?>
  </nav>
  <div class="settings-sections">
    <section class="card" id="general"><header class="card-head"><h2><?= icon('sliders') ?> General</h2></header>
      <form class="card-body form-grid" data-validate data-success="General settings are valid (Demo Mode — not saved).">
        <div class="field"><label for="g-org">Organisation name</label><input id="g-org" type="text" value="Example Org" required maxlength="80"><span class="error">Required.</span></div>
        <div class="field"><label for="g-tz">Time zone</label><select id="g-tz"><option>UTC</option><option>Europe/London</option><option>America/New_York</option><option>Asia/Kolkata</option><option>Australia/Perth</option></select></div>
        <div class="field"><label for="g-date">Date format</label><select id="g-date"><option>YYYY-MM-DD HH:mm</option><option>DD/MM/YYYY</option><option>MM/DD/YYYY</option></select></div>
        <div class="field"><label for="g-ret">Data retention (days)</label><input id="g-ret" type="number" min="7" max="3650" value="180" required><span class="error">7 – 3650 days.</span></div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </section>

    <section class="card" id="appearance"><header class="card-head"><h2><?= icon('palette') ?> Appearance</h2></header>
      <div class="card-body">
        <div class="choice-grid" role="radiogroup" aria-label="Theme">
          <label class="choice"><input type="radio" name="theme" value="dark" data-theme-choice><span><strong><?= icon('moon-stars') ?> Dark</strong><small>Default midnight theme.</small></span></label>
          <label class="choice"><input type="radio" name="theme" value="light" data-theme-choice><span><strong><?= icon('sun') ?> Light</strong><small>High-brightness environments.</small></span></label>
        </div>
        <label class="switch"><span><strong>Compact sidebar</strong><small>Show icons only on desktop.</small></span><input type="checkbox" data-compact-choice aria-label="Compact sidebar"></label>
      </div>
    </section>

    <section class="card" id="scan-defaults"><header class="card-head"><h2><?= icon('play-circle') ?> Scan defaults</h2></header>
      <form class="card-body form-grid" data-validate data-success="Scan defaults are valid (Demo Mode — not saved).">
        <div class="field"><label for="d-type">Default scan type</label><select id="d-type"><option>Full</option><option>Quick</option><option>Integrity</option></select></div>
        <div class="field"><label for="d-conc">Parallel scans</label><input id="d-conc" type="number" min="1" max="8" value="2" required><span class="error">1 – 8.</span></div>
        <div class="field"><label for="d-size">Max file size (MB)</label><input id="d-size" type="number" min="1" max="512" value="25" required><span class="error">1 – 512.</span></div>
        <div class="field"><label for="d-timeout">Scan timeout (minutes)</label><input id="d-timeout" type="number" min="5" max="720" value="120" required><span class="error">5 – 720.</span></div>
        <div class="full">
          <label class="switch"><span><strong>Read-only mode</strong><small>Always on in this release. The scanner never modifies files.</small></span><input type="checkbox" checked disabled aria-label="Read-only mode"></label>
          <label class="switch"><span><strong>Follow symbolic links</strong><small>Off by default to avoid scanning outside the project.</small></span><input type="checkbox" aria-label="Follow symbolic links"></label>
        </div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </section>

    <section class="card" id="exclusions"><header class="card-head"><h2><?= icon('funnel') ?> Exclusions</h2></header>
      <form class="card-body form-grid" data-validate data-success="Exclusions are valid (Demo Mode — not saved).">
        <div class="field full"><label for="x-paths">Excluded paths (glob, one per line)</label><textarea id="x-paths" maxlength="4000">node_modules/
vendor/
wp-content/cache/
*.log</textarea><small>Exclude only what you trust — attackers like to hide in cache folders.</small></div>
        <div class="field full"><label for="x-ext">Excluded extensions</label><input id="x-ext" type="text" value="jpg,png,gif,webp,mp4" data-pattern="^[a-z0-9]+(,[a-z0-9]+)*$"><small>Comma-separated, lowercase. Signature checks still run on excluded media.</small><span class="error">Use comma-separated lowercase extensions, e.g. jpg,png</span></div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </section>

    <section class="card" id="notify"><header class="card-head"><h2><?= icon('bell') ?> Notifications</h2></header>
      <form class="card-body" data-validate data-success="Notification settings are valid (Demo Mode — not saved).">
        <label class="switch"><span><strong>Critical findings</strong><small>Immediately.</small></span><input type="checkbox" checked aria-label="Critical findings"></label>
        <label class="switch"><span><strong>High findings</strong><small>Immediately.</small></span><input type="checkbox" checked aria-label="High findings"></label>
        <label class="switch"><span><strong>Scan failures</strong></span><input type="checkbox" checked aria-label="Scan failures"></label>
        <label class="switch"><span><strong>Daily digest</strong><small>Summary of all activity.</small></span><input type="checkbox" aria-label="Daily digest"></label>
        <div class="field" style="margin-top:12px"><label for="n-email">Alert email</label><input id="n-email" type="email" value="security@example.com" required><span class="error">Enter a valid email address.</span></div>
        <button class="btn btn-primary" type="submit" style="margin-top:12px">Save</button>
      </form>
    </section>

    <section class="card" id="report-settings"><header class="card-head"><h2><?= icon('file-earmark-bar-graph') ?> Reports</h2></header>
      <form class="card-body form-grid" data-validate data-success="Report settings are valid (Demo Mode — not saved).">
        <div class="field"><label for="rp-format">Default format</label><select id="rp-format"><option>PDF</option><option>CSV</option><option>JSON</option></select></div>
        <div class="field"><label for="rp-sched">Scheduled report</label><select id="rp-sched"><option>None</option><option selected>Monthly</option><option>Weekly</option></select></div>
        <div class="field full"><label for="rp-footer">Report footer text</label><input id="rp-footer" type="text" maxlength="120" value="Confidential — internal use only"></div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </section>

    <section class="card" id="integrations"><header class="card-head"><h2><?= icon('plug') ?> Integrations</h2></header>
      <form class="card-body form-grid" data-validate data-success="Integration settings are valid (Demo Mode — not saved).">
        <div class="field full"><label for="i-hook">Webhook URL</label><input id="i-hook" type="url" placeholder="https://hooks.example.com/…"><small>Secrets/tokens are entered on the backend, never stored in the browser.</small><span class="error">Enter a valid https URL.</span></div>
        <div class="field"><label for="i-syslog">Syslog host</label><input id="i-syslog" type="text" placeholder="siem.example.net" data-pattern="^[A-Za-z0-9.\-]*$"><span class="error">Hostname only.</span></div>
        <div class="field"><label for="i-port">Port</label><input id="i-port" type="number" min="1" max="65535" placeholder="514"><span class="error">1 – 65535.</span></div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button> <?= demo_action('Send test', 'send', 'Test delivery requires the backend (Demo Mode).') ?></div>
      </form>
    </section>

    <section class="card" id="account"><header class="card-head"><h2><?= icon('person') ?> Account</h2></header>
      <form class="card-body form-grid" data-validate data-success="Account details are valid (Demo Mode — not saved).">
        <div class="field"><label for="a-name">Display name</label><input id="a-name" type="text" value="<?= e($demo['user']['name']) ?>" required maxlength="60"><span class="error">Required.</span></div>
        <div class="field"><label for="a-email">Email</label><input id="a-email" type="email" value="<?= e($demo['user']['email']) ?>" required><span class="error">Enter a valid email.</span></div>
        <div class="field"><span class="label">Role</span><div><?= status_badge($demo['user']['role']) ?></div></div>
        <div class="field"><span class="label">Password &amp; 2FA</span><div><?= demo_action('Manage security', 'key', 'Authentication is handled by the backend (not implemented in Demo Mode).') ?></div></div>
        <div class="full"><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </section>

    <section class="card" id="about"><header class="card-head"><h2><?= icon('info-circle') ?> Product info</h2></header>
      <div class="card-body"><dl class="kv">
        <div><dt>Product</dt><dd><?= e($config['name']) ?> — <?= e($config['tagline']) ?></dd></div>
        <div><dt>Version</dt><dd><?= e($config['version']) ?></dd></div>
        <div><dt>Mode</dt><dd><?= $config['demo_mode'] ? 'Demo (frontend only)' : 'Connected' ?></dd></div>
        <div><dt>PHP</dt><dd><?= e(PHP_VERSION) ?></dd></div>
        <div><dt>Licence</dt><dd>Open source (see repository)</dd></div>
      </dl></div>
    </section>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  document.querySelectorAll('[data-theme-choice]').forEach((r) => {
    r.checked = r.value === (root.dataset.theme || 'dark');
    r.addEventListener('change', () => { document.querySelector('[data-theme-toggle]').click(); });
  });
  const c = document.querySelector('[data-compact-choice]');
  c.checked = root.classList.contains('sb-collapsed');
  c.addEventListener('change', () => document.querySelector('[data-sidebar-toggle]').click());
});
</script>
<?php render_footer();
