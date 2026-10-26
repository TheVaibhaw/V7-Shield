<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('help');
page_header('Help & Documentation', 'Get started, understand findings and troubleshoot V7-Shield.', '<a class="btn btn-ghost" href="' . e($config['repo_url']) . '" target="_blank" rel="noopener noreferrer">' . icon('github') . ' Repository</a>');
?>
<div class="help-grid">
  <a class="help-tile" href="#start"><?= icon('rocket-takeoff') ?><strong>Getting started</strong><small>First project and first scan.</small></a>
  <a class="help-tile" href="#install"><?= icon('download') ?><strong>Installation</strong><small>Requirements and local server.</small></a>
  <a class="help-tile" href="#scans"><?= icon('search') ?><strong>Scan types</strong><small>What each scan checks.</small></a>
  <a class="help-tile" href="#false-positives"><?= icon('hand-thumbs-up') ?><strong>False positives</strong><small>How to verify and suppress.</small></a>
  <a class="help-tile" href="#hardening"><?= icon('shield-lock') ?><strong>Hardening</strong><small>Quick wins for PHP sites.</small></a>
  <a class="help-tile" href="#troubleshooting"><?= icon('tools') ?><strong>Troubleshooting</strong><small>Common problems.</small></a>
  <a class="help-tile" href="#contributing"><?= icon('people') ?><strong>Contributing</strong><small>Rules, code and docs.</small></a>
</div>

<?php card_open('Getting started', 'rocket-takeoff'); ?>
<div class="prose" id="start"><ol>
  <li>Add a site or directory in <a href="<?= e(page_url('projects')) ?>">Websites &amp; Projects</a>.</li>
  <li>Create an integrity baseline in <a href="<?= e(page_url('file-integrity')) ?>">File Integrity</a> while you trust the current files.</li>
  <li>Run a <a href="<?= e(page_url('scans')) ?>">Full scan</a>, then review <a href="<?= e(page_url('findings')) ?>">Threat Detections</a> starting with Critical.</li>
  <li>Set up <a href="<?= e(page_url('scheduled-scans')) ?>">Scheduled Scans</a> and <a href="<?= e(page_url('settings')) ?>#notify">notifications</a>.</li>
</ol></div>
<?php card_close(); ?>

<?php card_open('Installation', 'download'); ?>
<div class="prose" id="install">
  <p>The admin panel is plain PHP 8.2+ with no build step.</p>
  <pre><code># from the repository root
php -S localhost:8000 -t frontend
# then open http://localhost:8000/</code></pre>
  <p>Under Apache, point a virtual host (or sub-folder) at <code>frontend/</code>; the included <code>.htaccess</code> sets the index and denies access to <code>config/</code>, <code>data/</code> and <code>includes/</code>.</p>
  <p>The scanning engine lives in <code>backend/</code> (planned). Until it is connected the console runs in <strong>Demo Mode</strong>.</p>
</div>
<?php card_close(); ?>

<?php card_open('Scan types', 'search'); ?>
<div class="prose" id="scans"><ul>
  <li><strong>Full</strong> — every file: signatures, patterns, integrity, CMS checks and (optionally) read-only database checks.</li>
  <li><strong>Quick</strong> — high-risk locations only: uploads, cache, temp and files changed since the last scan.</li>
  <li><strong>Integrity</strong> — SHA-256 comparison with the stored baseline.</li>
  <li><strong>PHP patterns</strong> — webshell, obfuscation and dangerous-function heuristics.</li>
</ul><p>All scans are <strong>read-only</strong>. Quarantine always requires explicit human approval.</p></div>
<?php card_close(); ?>

<?php card_open('False positives', 'hand-thumbs-up'); ?>
<div id="false-positives">
  <details class="faq"><summary>Why is a legitimate plugin flagged?</summary><div>Licence checks, minified code and remote-update routines often use base64 or dynamic calls. Compare the file with the official package from the vendor before acting.</div></details>
  <details class="faq"><summary>How do I suppress a verified file?</summary><div>Mark the finding as <em>False Positive</em> and add a narrowly scoped exclusion (exact path, not a whole folder) in Settings › Exclusions.</div></details>
  <details class="faq"><summary>Does a changed hash mean I am hacked?</summary><div>No. Updates and deployments change hashes. Unexpected changes to core files, or new scripts in upload folders, deserve investigation.</div></details>
</div>
<?php card_close(); ?>

<?php card_open('Hardening quick wins', 'shield-lock'); ?>
<div class="prose" id="hardening">
  <h3>Block PHP execution in upload folders (Apache)</h3>
  <pre><code>&lt;FilesMatch "\.(php|phtml|phar)$"&gt;
    Require all denied
&lt;/FilesMatch&gt;</code></pre>
  <h3>Nginx</h3>
  <pre><code>location ~* /uploads/.*\.(php|phtml|phar)$ { deny all; }</code></pre>
  <ul><li>Set <code>DISALLOW_FILE_EDIT</code> in WordPress.</li><li>Enforce 2FA for administrators.</li><li>Use a dedicated read-only database user for V7-Shield.</li></ul>
</div>
<?php card_close(); ?>

<?php card_open('Troubleshooting', 'tools'); ?>
<div id="troubleshooting">
  <details class="faq"><summary>Charts do not appear</summary><div>Chart.js and icons load from a CDN. If you are offline, download them into <code>assets/vendor/</code> and update <code>includes/header.php</code> / <code>footer.php</code>.</div></details>
  <details class="faq"><summary>Styles look broken under a sub-folder</summary><div>URLs are built from the running script path. Make sure you open pages through the web server, not as local files.</div></details>
  <details class="faq"><summary>Database checks fail</summary><div>Verify the read-only user, host allow-list and firewall on the backend. The frontend never sees credentials.</div></details>
</div>
<?php card_close(); ?>

<?php card_open('Contributing', 'people'); ?>
<div class="prose" id="contributing">
  <p>Contributions are welcome: detection rules, CMS checks, translations, UI improvements and docs.</p>
  <ul><li>Keep the frontend framework-free (Core PHP + vanilla JS).</li><li>Escape all dynamic output with <code>e()</code>.</li><li>Add new pages to the allowlist in <code>config/app.php</code>.</li><li>Run <code>php -l</code> on changed files before opening a pull request.</li></ul>
  <p><a href="<?= e($config['repo_url']) ?>" target="_blank" rel="noopener noreferrer">Open the repository</a></p>
</div>
<?php card_close(); render_footer();
