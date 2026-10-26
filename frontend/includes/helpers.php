<?php
/**
 * Small view helpers. Every function that outputs dynamic text escapes it.
 */
declare(strict_types=1);

defined('APP_ROOT') || exit;

/** Escape a value for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL of the frontend, detected from the running script (works under / or a sub-folder). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $dir = preg_replace('#/pages$#', '', $dir);
        $base = rtrim((string) $dir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url('assets/' . $path) . '?v=' . $v;
}

/** Look up a page by key in the nav allowlist. */
function nav_item(string $key): ?array
{
    global $config;
    foreach ($config['nav'] as $group => $items) {
        if (isset($items[$key])) {
            [$title, $icon, $path] = $items[$key];
            return compact('group', 'title', 'icon', 'path');
        }
    }
    return null;
}

function page_url(string $key): string
{
    $item = nav_item($key);
    return $item ? url($item['path']) : url('dashboard.php');
}

/** Render the page shell top: <head>, sidebar, topbar. */
function render_header(string $pageKey, ?string $title = null): void
{
    global $config, $demo;
    $item = nav_item($pageKey) ?? ['group' => 'Overview', 'title' => 'Dashboard', 'icon' => 'grid', 'path' => 'dashboard.php'];
    $pageTitle = $title ?? $item['title'];
    require APP_ROOT . '/includes/header.php';
    require APP_ROOT . '/includes/sidebar.php';
    echo '<div class="app-main">';
    require APP_ROOT . '/includes/topbar.php';
    echo '<main id="main" class="content" tabindex="-1">';
}

function render_footer(): void
{
    global $config, $demo;
    echo '</main>';
    require APP_ROOT . '/includes/footer.php';
}

function icon(string $name, string $class = ''): string
{
    return '<i class="bi bi-' . e($name) . ($class ? ' ' . e($class) : '') . '" aria-hidden="true"></i>';
}

/** Page heading block with optional description and right-side actions (trusted HTML). */
function page_header(string $title, string $desc = '', string $actionsHtml = ''): void
{
    echo '<div class="page-head"><div><h1>' . e($title) . '</h1>';
    if ($desc !== '') {
        echo '<p class="muted">' . e($desc) . '</p>';
    }
    echo '</div>';
    if ($actionsHtml !== '') {
        echo '<div class="page-actions">' . $actionsHtml . '</div>';
    }
    echo '</div>';
}

function demo_notice(string $text = 'All data on this page is sample data for UI demonstration. No real scan has been performed.'): string
{
    return '<div class="notice notice-demo" role="note">' . icon('info-circle') . '<span><strong>Demo Mode.</strong> ' . e($text) . '</span></div>';
}

function severity_class(string $sev): string
{
    return match (strtolower($sev)) {
        'critical' => 'crit',
        'high'     => 'high',
        'medium', 'warning' => 'med',
        'low'      => 'low',
        'info', 'informational' => 'info',
        default    => 'neutral',
    };
}

function sev_badge(string $sev): string
{
    return '<span class="badge sev-' . severity_class($sev) . '"><span class="dot"></span>' . e($sev) . '</span>';
}

function status_badge(string $status): string
{
    $cls = match (strtolower($status)) {
        'completed', 'healthy', 'resolved', 'protected', 'passed', 'active', 'verified', 'connected', 'restored' => 'ok',
        'running', 'in review', 'reviewing', 'investigating' => 'run',
        'queued', 'scheduled', 'pending', 'new', 'unreviewed', 'paused' => 'queue',
        'failed', 'at risk', 'critical', 'error' => 'bad',
        'needs attention', 'warning', 'outdated', 'drifted' => 'warn',
        default => 'neutral',
    };
    return '<span class="status st-' . $cls . '">' . e($status) . '</span>';
}

function progress_bar(int $pct, string $cls = ''): string
{
    $pct = max(0, min(100, $pct));
    return '<div class="progress ' . e($cls) . '" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" aria-label="Progress"><span style="width:' . $pct . '%"></span></div>';
}

/** Button that opens the detail drawer with the given key/value record. */
function detail_button(string $title, array $record, string $label = 'View'): string
{
    $json = json_encode(['title' => $title, 'fields' => $record], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return '<button type="button" class="btn btn-ghost btn-sm" data-detail="' . e((string) $json) . '">' . icon('eye') . ' ' . e($label) . '</button>';
}

/** Metric/stat card. */
function stat_card(string $icon, string $label, string $value, string $tone = 'cyan', string $trend = '', string $tip = ''): string
{
    $h = '<div class="stat-card tone-' . e($tone) . '"' . ($tip ? ' title="' . e($tip) . '"' : '') . '>';
    $h .= '<div class="stat-icon">' . icon($icon) . '</div><div class="stat-body">';
    $h .= '<div class="stat-value">' . e($value) . '</div><div class="stat-label">' . e($label);
    if ($tip) {
        $h .= ' <span class="tip" tabindex="0" aria-label="' . e($tip) . '" data-tip="' . e($tip) . '">' . icon('info-circle') . '</span>';
    }
    $h .= '</div>';
    if ($trend !== '') {
        $up = str_starts_with($trend, '+');
        $h .= '<div class="stat-trend ' . ($up ? 'up' : 'down') . '">' . icon($up ? 'arrow-up-right' : 'arrow-down-right') . ' ' . e($trend) . '</div>';
    }
    return $h . '</div></div>';
}

/**
 * Render an enhanced data table (search, filters, sort, pagination handled by app.js).
 *
 * @param array<string, array{0:string,1?:callable|null}> $columns key => [label, renderer]
 * @param list<array<string,mixed>> $rows
 * @param array<string, list<string>> $filters row-key => allowed values
 */
function data_table(string $id, array $columns, array $rows, array $filters = [], int $perPage = 8, string $emptyText = 'No records match your filters.'): void
{
    echo '<div class="table-tools" data-table-for="' . e($id) . '">';
    echo '<label class="search-field">' . icon('search') . '<span class="sr-only">Search table</span><input type="search" placeholder="Search…" data-table-search></label>';
    foreach ($filters as $key => $values) {
        echo '<label class="select-field"><span class="sr-only">Filter by ' . e($key) . '</span><select data-table-filter="' . e($key) . '"><option value="">All ' . e(ucfirst(str_replace('_', ' ', $key))) . '</option>';
        foreach ($values as $v) {
            echo '<option value="' . e(strtolower($v)) . '">' . e($v) . '</option>';
        }
        echo '</select></label>';
    }
    echo '</div>';
    echo '<div class="table-wrap"><table class="data-table" id="' . e($id) . '" data-per-page="' . $perPage . '"><thead><tr>';
    foreach ($columns as $key => $col) {
        $sortable = !str_starts_with($key, '_');
        echo '<th scope="col"' . ($sortable ? ' data-sort="' . e($key) . '" tabindex="0" aria-sort="none"' : '') . '>' . e($col[0]) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $attrs = '';
        foreach (array_keys($filters) as $fk) {
            $attrs .= ' data-' . e($fk) . '="' . e(strtolower((string) ($row[$fk] ?? ''))) . '"';
        }
        echo '<tr' . $attrs . '>';
        foreach ($columns as $key => $col) {
            $raw = $row[$key] ?? '';
            $html = isset($col[1]) && $col[1] !== null ? ($col[1])($row) : e($raw);
            $sortVal = is_scalar($raw) ? (string) $raw : '';
            echo '<td data-label="' . e($col[0]) . '" data-value="' . e($sortVal) . '">' . $html . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="empty-state" hidden>' . icon('inbox') . '<p>' . e($emptyText) . '</p></div></div>';
    echo '<div class="pager" data-pager-for="' . e($id) . '"></div>';
}

function card_open(string $title, string $icon = '', string $extraHtml = '', string $class = ''): void
{
    echo '<section class="card ' . e($class) . '"><header class="card-head"><h2>' . ($icon ? icon($icon) . ' ' : '') . e($title) . '</h2>' . $extraHtml . '</header><div class="card-body">';
}

function card_close(): void
{
    echo '</div></section>';
}

/** Key/value checklist rows: [label, status, note]. */
function check_list(array $items): void
{
    echo '<ul class="check-list">';
    foreach ($items as [$label, $status, $note]) {
        echo '<li><div><strong>' . e($label) . '</strong><span class="muted">' . e($note) . '</span></div>' . status_badge($status) . '</li>';
    }
    echo '</ul>';
}

function demo_action(string $label, string $icon, string $message, string $class = 'btn-ghost'): string
{
    return '<button type="button" class="btn ' . e($class) . '" data-demo-toast="' . e($message) . '">' . icon($icon) . ' ' . e($label) . '</button>';
}

/* ---------- Shared table column sets ---------- */

function finding_columns(): array
{
    return [
        'id' => ['ID', fn($r) => '<code>' . e($r['id']) . '</code>'],
        'severity' => ['Severity', fn($r) => sev_badge($r['severity'])],
        'title' => ['Finding', fn($r) => '<strong>' . e($r['title']) . '</strong>'],
        'resource' => ['Affected resource', fn($r) => '<code class="trunc" title="' . e($r['resource']) . '">' . e($r['resource']) . '</code>'],
        'project' => ['Project'],
        'category' => ['Category'],
        'confidence' => ['Confidence'],
        'detected' => ['Detected'],
        'status' => ['Review', fn($r) => status_badge($r['status'])],
        '_act' => ['', fn($r) => detail_button($r['title'], $r)],
    ];
}

function scan_columns(): array
{
    return [
        'started' => ['Date'],
        'name' => ['Scan', fn($r) => '<strong>' . e($r['name']) . '</strong>'],
        'project' => ['Project'],
        'type' => ['Type'],
        'duration' => ['Duration'],
        'files' => ['Files', fn($r) => e(number_format($r['files']))],
        'findings' => ['Findings'],
        'status' => ['Status', fn($r) => status_badge($r['status'])],
        '_act' => ['', fn($r) => detail_button($r['name'], $r)],
    ];
}

function event_columns(): array
{
    return [
        'time' => ['Timestamp', fn($r) => '<span class="mono">' . e($r['time']) . '</span>'],
        'category' => ['Category'],
        'severity' => ['Severity', fn($r) => sev_badge($r['severity'])],
        'source' => ['Source'],
        'project' => ['Project'],
        'title' => ['Event', fn($r) => '<strong>' . e($r['title']) . '</strong><small class="muted">' . e($r['desc']) . '</small>'],
        '_act' => ['', fn($r) => detail_button($r['title'], $r)],
    ];
}

function change_columns(): array
{
    return [
        'change' => ['Change', fn($r) => '<span class="chg chg-' . e(strtolower($r['change'])) . '">' . e($r['change']) . '</span>'],
        'file' => ['File', fn($r) => '<code class="trunc" title="' . e($r['file']) . '">' . e($r['file']) . '</code>'],
        'project' => ['Project'],
        'size' => ['Size'],
        'hash' => ['SHA-256 (truncated)', fn($r) => '<span class="mono">' . e($r['hash']) . '</span>'],
        'when' => ['Detected'],
        '_act' => ['', fn($r) => detail_button($r['file'], $r)],
    ];
}

/** Filter demo rows by a column value. */
function rows_where(array $rows, string $key, array $values): array
{
    return array_values(array_filter($rows, fn($r) => in_array($r[$key] ?? null, $values, true)));
}

function export_buttons(string $tableId): string
{
    return '<button type="button" class="btn btn-ghost btn-sm" data-export="' . e($tableId) . '" data-format="csv">' . icon('filetype-csv') . ' CSV</button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-export="' . e($tableId) . '" data-format="json">' . icon('filetype-json') . ' JSON</button>';
}
