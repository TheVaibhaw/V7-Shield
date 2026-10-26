<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('joomla');
page_header('Joomla Security', 'Core integrity, extensions, templates and configuration for Joomla sites.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '?type=full">' . icon('play-fill') . ' Scan Joomla site</a>');
echo demo_notice();
$jf = rows_where($demo['findings'], 'project', ['City Library Portal', 'Partner Intranet']);
?>
<div class="stat-grid">
  <?= stat_card('boxes', 'Joomla sites', '2', 'cyan') ?>
  <?= stat_card('puzzle', 'Extensions checked', '27', 'blue', '', '2 not updated in over a year.') ?>
  <?= stat_card('layout-text-window', 'Templates', '4', 'green') ?>
  <?= stat_card('exclamation-triangle', 'Core files modified', '1', 'red', '', 'SiteApplication.php on Partner Intranet.') ?>
</div>
<div class="grid grid-2">
  <?php card_open('Core integrity', 'shield-check'); check_list([
      ['City Library Portal — Joomla 5.x', 'Verified', 'Matches official checksums.'],
      ['Partner Intranet — Joomla 5.x', 'Drifted', 'libraries/src/Application/SiteApplication.php modified.'],
      ['administrator/ directory', 'Warning', 'Log file deleted: administrator/logs/error.php'],
  ]); card_close(); ?>
  <?php card_open('Configuration review', 'gear'); check_list([
      ['Error reporting', 'Passed', 'Set to "None" in production.'],
      ['configuration.php permissions', 'Warning', '0644 — recommended 0444.'],
      ['Two-factor authentication', 'Pending', 'Not enforced for Super Users.'],
      ['Media folder executables', 'Warning', 'images/tmp/run.phtml found.'],
  ]); card_close(); ?>
</div>
<div class="grid grid-2">
  <?php card_open('Extensions', 'puzzle');
  data_table('j-ext', ['name' => ['Extension', fn($r) => '<strong>' . e($r['name']) . '</strong>'], 'kind' => ['Kind'], 'site' => ['Site'], 'status' => ['Status', fn($r) => status_badge($r['status'])]], [
      ['name' => 'com_events', 'kind' => 'Component', 'site' => 'Library', 'status' => 'Healthy'],
      ['name' => 'mod_newsfeed_plus', 'kind' => 'Module', 'site' => 'Intranet', 'status' => 'Outdated'],
      ['name' => 'plg_system_sso', 'kind' => 'Plugin', 'site' => 'Intranet', 'status' => 'Needs Attention'],
      ['name' => 'com_docs', 'kind' => 'Component', 'site' => 'Library', 'status' => 'Healthy'],
  ], ['status' => ['Healthy', 'Outdated', 'Needs Attention']], 5);
  card_close(); ?>
  <?php card_open('Templates', 'layout-text-window'); check_list([
      ['cassiopeia (Library)', 'Warning', 'Long encoded string in index.php — review.'],
      ['atum (admin)', 'Healthy', 'Matches core package.'],
      ['intranet-custom', 'Healthy', 'No suspicious patterns.'],
  ]); card_close(); ?>
</div>
<?php card_open('Suspicious files & findings', 'bug');
data_table('j-findings', finding_columns(), $jf, ['severity' => ['Critical', 'High', 'Medium', 'Low']], 6);
card_close();
card_open('Recent file changes', 'file-diff');
data_table('j-changes', change_columns(), rows_where($demo['integrity']['changes'], 'project', ['City Library Portal', 'Partner Intranet']), [], 5);
card_close();
render_footer();
