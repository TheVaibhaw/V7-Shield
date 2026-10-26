<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('quarantine');
page_header('Quarantine', 'Files isolated for review. Quarantined files are moved out of the web root and made non-executable.');
echo '<div class="notice notice-danger" role="note">' . icon('cone-striped') . '<span><strong>Interface prototype.</strong> Restore and delete actions below do not touch any file. Real quarantine operations will be performed only by the backend, with audit logging.</span></div>';
card_open('Quarantined items', 'archive');
data_table('quarantine-table', [
    'id' => ['ID', fn($r) => '<code>' . e($r['id']) . '</code>'],
    'file' => ['Original path', fn($r) => '<code class="trunc" title="' . e($r['file']) . '">' . e($r['file']) . '</code>'],
    'project' => ['Project'],
    'reason' => ['Detection reason'],
    'hash' => ['SHA-256', fn($r) => '<span class="mono">' . e($r['hash']) . '</span>'],
    'when' => ['Quarantined'],
    'status' => ['Review', fn($r) => status_badge($r['status'])],
    '_act' => ['', fn($r) => detail_button($r['file'], $r)
        . ' <button type="button" class="btn btn-ghost btn-sm" data-confirm="Restore ' . e($r['file']) . ' to its original location? (Demo — no file is restored.)" data-confirm-ok="Restore (demo)" data-demo-toast="Restore simulated in the UI only.">' . icon('arrow-counterclockwise') . '</button>'
        . ' <button type="button" class="btn btn-ghost btn-sm" aria-label="Delete permanently" data-confirm="Permanently delete ' . e($r['file']) . '? This would be irreversible on a real system. (Demo — nothing is deleted.)" data-confirm-ok="Delete (demo)" data-remove-row data-demo-toast="Removed from the list (demo). No file was deleted.">' . icon('trash') . '</button>'],
], $demo['quarantine'], ['status' => ['Pending', 'Reviewing', 'Restored']]);
card_close();
?>
<div class="grid grid-2">
  <?php card_open('Quarantine policy', 'shield-lock'); ?>
    <?php check_list([
        ['Automatic quarantine', 'Disabled', 'V7-Shield never quarantines without explicit approval.'],
        ['Storage location', 'Pending', 'Configured on the backend, outside the web root.'],
        ['Retention', 'Active', 'Items kept for 30 days before permanent deletion is offered.'],
        ['Audit log', 'Pending', 'Every restore/delete will be written to Activity Logs.'],
    ]); ?>
  <?php card_close(); ?>
  <?php card_open('Restore checklist', 'list-check'); ?>
    <ul class="prose"><li>Confirm the file exists in the vendor package or your repository.</li><li>Check the file hash against a trusted copy.</li><li>Mark the related finding as a false positive so it is not re-quarantined.</li></ul>
  <?php card_close(); ?>
</div>
<?php render_footer();
