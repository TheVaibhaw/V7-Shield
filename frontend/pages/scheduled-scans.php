<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('scheduled-scans');
page_header('Scheduled Scans', 'Recurring scans run automatically by the backend scheduler (cron).', '<button type="button" class="btn btn-primary" data-modal-open="schedule-modal">' . icon('plus-lg') . ' New schedule</button>');
echo demo_notice('Schedules are not saved. The backend scheduler will execute them once integrated.');
$schedules = [
    ['name' => 'Nightly full scan', 'project' => 'Northwind Store', 'type' => 'Full', 'frequency' => 'Daily 02:00', 'next' => '2026-10-27 02:00', 'last' => 'Completed', 'status' => 'Active'],
    ['name' => 'Hourly uploads', 'project' => 'Uploads Directory', 'type' => 'Quick', 'frequency' => 'Every hour', 'next' => '2026-10-26 10:00', 'last' => 'Running', 'status' => 'Active'],
    ['name' => 'Weekly integrity', 'project' => 'City Library Portal', 'type' => 'Integrity', 'frequency' => 'Sundays 03:00', 'next' => '2026-11-01 03:00', 'last' => 'Completed', 'status' => 'Active'],
    ['name' => 'Weekly full scan', 'project' => 'Legacy CRM', 'type' => 'Full', 'frequency' => 'Fridays 14:00', 'next' => '2026-10-30 14:00', 'last' => 'Completed', 'status' => 'Active'],
    ['name' => 'Monthly DB review', 'project' => 'Northwind Store', 'type' => 'Database', 'frequency' => '1st of month', 'next' => '2026-11-01 04:00', 'last' => 'Failed', 'status' => 'Paused'],
];
card_open('Schedules', 'calendar-week');
data_table('schedule-table', [
    'name' => ['Schedule', fn($r) => '<strong>' . e($r['name']) . '</strong>'],
    'project' => ['Project'], 'type' => ['Type'], 'frequency' => ['Frequency'], 'next' => ['Next run'],
    'last' => ['Last result', fn($r) => status_badge($r['last'])],
    'status' => ['State', fn($r) => status_badge($r['status'])],
    '_act' => ['', fn($r) => detail_button($r['name'], $r) . ' <button type="button" class="btn btn-ghost btn-sm" data-confirm="Delete schedule “' . e($r['name']) . '”? (Demo — removed from this view only.)" data-confirm-ok="Delete" data-remove-row data-demo-toast="Schedule removed from view (demo).">' . icon('trash') . '</button>'],
], $schedules, ['status' => ['Active', 'Paused']]);
card_close();
?>
<div class="modal" id="schedule-modal" role="dialog" aria-modal="true" aria-labelledby="schedule-title" hidden>
  <form class="modal-box wide" data-validate data-success="Schedule validated (Demo Mode — not saved).">
    <h2 id="schedule-title">New scheduled scan</h2>
    <div class="form-grid">
      <div class="field"><label for="s-name">Name</label><input id="s-name" type="text" required maxlength="80"><span class="error">Name is required.</span></div>
      <div class="field"><label for="s-project">Project</label><select id="s-project" required><option value="">Select…</option><?php foreach ($demo['projects'] as $p): ?><option><?= e($p['name']) ?></option><?php endforeach; ?></select><span class="error">Select a project.</span></div>
      <div class="field"><label for="s-type">Scan type</label><select id="s-type"><option>Full</option><option>Quick</option><option>Integrity</option><option>PHP Patterns</option></select></div>
      <div class="field"><label for="s-freq">Frequency</label><select id="s-freq"><option>Hourly</option><option selected>Daily</option><option>Weekly</option><option>Monthly</option></select></div>
      <div class="field"><label for="s-time">Time</label><input id="s-time" type="time" value="02:00" required><span class="error">Choose a time.</span></div>
    </div>
    <div class="modal-actions" style="justify-content:flex-end"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save schedule</button></div>
  </form>
</div>
<?php render_footer();
