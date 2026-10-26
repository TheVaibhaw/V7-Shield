<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('notifications');
$unread = count(array_filter($demo['notifications'], fn($n) => !$n['read']));
page_header('Notifications', 'Alerts about detections, scans, integrity changes and reports.', '<button type="button" class="btn btn-ghost" data-mark-all-read>' . icon('check2-all') . ' Mark all as read</button>');
echo demo_notice('Read/unread state changes only in this page view.');
?>
<section class="card">
  <header class="card-head">
    <h2><?= icon('bell') ?> Inbox <span class="badge sev-crit"><span data-unread-count><?= $unread ?></span>&nbsp;unread</span></h2>
    <div class="table-tools" style="margin:0">
      <label class="select-field"><span class="sr-only">State</span><select data-note-filter="state"><option value="">All</option><option value="unread">Unread</option><option value="read">Read</option></select></label>
      <label class="select-field"><span class="sr-only">Severity</span><select data-note-filter="severity"><option value="">All severities</option><option value="critical">Critical</option><option value="high">High</option><option value="medium">Medium</option><option value="info">Info</option></select></label>
      <label class="select-field"><span class="sr-only">Category</span><select data-note-filter="category"><option value="">All categories</option><?php foreach (array_unique(array_column($demo['notifications'], 'category')) as $c): ?><option value="<?= e(strtolower($c)) ?>"><?= e($c) ?></option><?php endforeach; ?></select></label>
    </div>
  </header>
  <ul class="note-list">
    <?php foreach ($demo['notifications'] as $n): ?>
      <li class="note-item<?= $n['read'] ? '' : ' unread' ?>" data-severity="<?= e(strtolower($n['severity'])) ?>" data-category="<?= e(strtolower($n['category'])) ?>">
        <div class="note-main">
          <div class="note-title"><?= e($n['title']) ?></div>
          <p><?= e($n['body']) ?></p>
          <div class="note-meta"><?= sev_badge($n['severity']) ?><span><?= e($n['category']) ?></span><span>·</span><span><?= e($n['time']) ?></span></div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-mark-read><?= $n['read'] ? 'Mark as unread' : 'Mark as read' ?></button>
      </li>
    <?php endforeach; ?>
  </ul>
  <div class="empty-state" id="notes-empty" hidden><?= icon('bell-slash') ?><p>No notifications match these filters.</p></div>
</section>
<?php card_open('Delivery channels', 'send'); check_list([
    ['In-app', 'Active', 'All severities'],
    ['Email', 'Pending', 'Critical & High — requires backend mailer'],
    ['Webhook (Slack/Teams)', 'Pending', 'Configure in Settings › Integrations'],
]); card_close(); render_footer();
