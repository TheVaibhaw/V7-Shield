<?php
declare(strict_types=1);

defined('APP_ROOT') || exit;

use V7Shield\Database;
use V7Shield\Repository\ScanRepository;
use V7Shield\Security\Csrf;
use V7Shield\Service\ScanService;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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

function nav_item(string $key): ?array
{
    global $config;
    foreach ($config['nav'] as $group => $items) {
        if (isset($items[$key])) {
            [$title, $icon, $path] = $items[$key];
            return compact('group', 'title', 'icon', 'path');
        }
    }
    if (isset($config['hidden'][$key])) {
        [$title, $icon, $path, $group] = $config['hidden'][$key];
        return compact('group', 'title', 'icon', 'path');
    }
    return null;
}

function page_url(string $key, array $query = []): string
{
    $item = nav_item($key);
    $u = $item ? url($item['path']) : url('dashboard.php');
    return $query ? $u . '?' . http_build_query($query) : $u;
}

function render_header(string $pageKey, ?string $title = null): void
{
    global $config, $auth;
    $item = nav_item($pageKey) ?? throw new LogicException('Page not in allowlist: ' . $pageKey);
    $pageTitle = $title ?? $item['title'];
    $navKey = $pageKey === 'scan' ? 'scan-history' : $pageKey;
    require APP_ROOT . '/includes/header.php';
    require APP_ROOT . '/includes/sidebar.php';
    echo '<div class="app-main">';
    require APP_ROOT . '/includes/topbar.php';
    echo '<main id="main" class="content" tabindex="-1">';
}

function render_footer(): void
{
    global $config, $v7;
    echo '</main>';
    require APP_ROOT . '/includes/footer.php';
}

function icon(string $name, string $class = ''): string
{
    return '<i class="bi bi-' . e($name) . ($class ? ' ' . e($class) : '') . '" aria-hidden="true"></i>';
}

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

function notice(string $type, string $html, string $icon = 'info-circle'): string
{
    return '<div class="notice notice-' . e($type) . '" role="' . ($type === 'danger' ? 'alert' : 'note') . '">' . icon($icon) . '<span>' . $html . '</span></div>';
}

function severity_class(string $sev): string
{
    return match (strtolower($sev)) {
        'critical' => 'crit',
        'high'     => 'high',
        'medium'   => 'med',
        'low'      => 'low',
        default    => 'neutral',
    };
}

function sev_badge(string $sev): string
{
    return '<span class="badge sev-' . severity_class($sev) . '"><span class="dot"></span>' . e(ucfirst($sev)) . '</span>';
}

function status_badge(string $status): string
{
    $cls = match (strtolower($status)) {
        'completed', 'passed', 'configured', 'enabled' => 'ok',
        'running' => 'run',
        'queued', 'pending' => 'queue',
        'failed', 'error', 'missing' => 'bad',
        'warning', 'limit reached', 'not configured' => 'warn',
        default => 'neutral',
    };
    return '<span class="status st-' . $cls . '">' . e(ucfirst($status)) . '</span>';
}

function progress_bar(int $pct, string $cls = ''): string
{
    $pct = max(0, min(100, $pct));
    return '<div class="progress ' . e($cls) . '" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" aria-label="Progress"><span style="width:' . $pct . '%"></span></div>';
}

function stat_card(string $icon, string $label, string $value, string $tone = 'cyan', string $tip = ''): string
{
    $h = '<div class="stat-card tone-' . e($tone) . '">';
    $h .= '<div class="stat-icon">' . icon($icon) . '</div><div class="stat-body">';
    $h .= '<div class="stat-value">' . e($value) . '</div><div class="stat-label">' . e($label);
    if ($tip) {
        $h .= ' <span class="tip" tabindex="0" aria-label="' . e($tip) . '" data-tip="' . e($tip) . '">' . icon('info-circle') . '</span>';
    }
    return $h . '</div></div></div>';
}

function card_open(string $title, string $icon = '', string $extraHtml = '', string $class = ''): void
{
    echo '<section class="card ' . e($class) . '"><header class="card-head"><h2>' . ($icon ? icon($icon) . ' ' : '') . e($title) . '</h2>' . $extraHtml . '</header><div class="card-body">';
}

function card_close(): void
{
    echo '</div></section>';
}

function empty_state(string $icon, string $text, string $actionHtml = ''): string
{
    return '<div class="empty-state">' . icon($icon) . '<p>' . e($text) . '</p>' . $actionHtml . '</div>';
}


function scan_service(): ScanService
{
    global $v7;
    static $svc = null;
    return $svc ??= new ScanService($v7, new ScanRepository(Database::connect($v7)));
}

function try_backend(callable $fn): array
{
    try {
        return [$fn(scan_service()), null];
    } catch (V7Shield\ServiceException $e) {
        return [null, $e->getMessage()];
    } catch (Throwable $e) {
        error_log('[V7-Shield] ' . $e->getMessage());
        return [null, 'The scanner backend returned an error. Check the server log.'];
    }
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Csrf::token()) . '">';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function int_param(array $src, string $key, int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
{
    $v = filter_var($src[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    return $v === false ? $default : $v;
}

function str_param(array $src, string $key): ?string
{
    $v = $src[$key] ?? null;
    return is_string($v) && $v !== '' ? $v : null;
}


function fmt_dt(?string $utc): string
{
    if ($utc === null || $utc === '') {
        return '<span class="muted">—</span>';
    }
    return '<time datetime="' . e(str_replace(' ', 'T', $utc)) . 'Z" data-utc>' . e($utc) . ' UTC</time>';
}

function fmt_duration(?string $start, ?string $end): string
{
    if (!$start) {
        return '—';
    }
    $from = strtotime($start . ' UTC');
    $to = $end ? strtotime($end . ' UTC') : time();
    if ($from === false || $to === false) {
        return '—';
    }
    $s = max(0, $to - $from);
    return $s >= 60 ? intdiv($s, 60) . 'm ' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) . 's' : $s . 's';
}

function fmt_bytes(int $b): string
{
    return V7Shield\Scanner\Scanner::bytes($b);
}

function pager(int $page, int $pages, int $total, array $query): string
{
    $h = '<nav class="pager" aria-label="Pagination"><span>' . e(number_format($total)) . ' result' . ($total === 1 ? '' : 's') . '</span>';
    if ($pages > 1) {
        $h .= '<div class="pages">';
        $link = function (int $p, string $label, bool $current = false, bool $disabled = false) use ($query): string {
            if ($disabled) {
                return '<button type="button" disabled>' . $label . '</button>';
            }
            return '<a class="pg' . ($current ? ' current' : '') . '"' . ($current ? ' aria-current="page"' : '') . ' href="?' . e(http_build_query(['page' => $p] + $query)) . '">' . $label . '</a>';
        };
        $h .= $link($page - 1, '&lsaquo;', false, $page <= 1);
        for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
            $h .= $link($p, (string) $p, $p === $page);
        }
        $h .= $link($page + 1, '&rsaquo;', false, $page >= $pages);
        $h .= '</div>';
    }
    return $h . '</nav>';
}

function scan_status_label(array $job): string
{
    $label = status_badge($job['status']);
    if ($job['status'] === 'completed' && $job['limit_reached']) {
        $label .= ' ' . status_badge('limit reached');
    }
    return $label;
}

function severity_chips(array $job): string
{
    $h = '<span class="sev-chips">';
    foreach (['critical', 'high', 'medium', 'low'] as $s) {
        if ($job[$s] > 0) {
            $h .= '<span class="badge sev-' . severity_class($s) . '" title="' . e(ucfirst($s)) . '">' . (int) $job[$s] . '</span>';
        }
    }
    return $h . ($job['findings_count'] === 0 ? '<span class="muted">0</span>' : '') . '</span>';
}

function detail_button(string $title, array $fields, string $label = 'Details'): string
{
    $json = json_encode(['title' => $title, 'fields' => $fields], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
    return '<button type="button" class="btn btn-ghost btn-sm" data-detail="' . e((string) $json) . '">' . icon('eye') . ' ' . e($label) . '</button>';
}
