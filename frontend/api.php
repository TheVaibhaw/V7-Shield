<?php
declare(strict_types=1);

const V7_API = true;
require __DIR__ . '/includes/bootstrap.php';

use V7Shield\Security\Csrf;
use V7Shield\ServiceException;

$actions = ['summary' => 'GET', 'status' => 'GET', 'findings' => 'GET', 'history' => 'GET', 'start' => 'POST', 'run' => 'POST'];
$action = str_param($_GET, 'action') ?? '';
if (!isset($actions[$action])) {
    json_response(['error' => 'Unknown action.'], 404);
}
if ($_SERVER['REQUEST_METHOD'] !== $actions[$action]) {
    header('Allow: ' . $actions[$action]);
    json_response(['error' => 'Method not allowed.'], 405);
}
if ($actions[$action] === 'POST' && !Csrf::valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? str_param($_POST, 'csrf'))) {
    json_response(['error' => 'Invalid or missing CSRF token.'], 403);
}

$user = (string) $auth->user();

try {
    $svc = scan_service();
    switch ($action) {
        case 'summary':
            json_response($svc->summary());
        case 'status':
            json_response($svc->status(int_param($_GET, 'id', 0, 1)));
        case 'findings':
            json_response($svc->findings(int_param($_GET, 'scan_id', 0, 1), $_GET, int_param($_GET, 'page', 1, 1, 100000)));
        case 'history':
            json_response($svc->history(int_param($_GET, 'page', 1, 1, 100000), str_param($_GET, 'status')));
        case 'start':
            $target = str_param($_POST, 'target') ?? '';
            $id = $svc->start($target, $user, $_SERVER['REMOTE_ADDR'] ?? null);
            json_response(['id' => $id, 'execution' => $v7->get('jobs.execution')], 201);
        case 'run':
            if ($v7->get('jobs.execution') !== 'inline') {
                json_response(['error' => 'Scans are processed by the CLI worker.'], 409);
            }
            $id = int_param($_GET, 'id', 0, 1);
            $job = $svc->status($id);
            if ($job['requested_by'] !== $user) {
                json_response(['error' => 'Only the user who started this scan can run it.'], 403);
            }
            session_write_close();
            ignore_user_abort(true);
            set_time_limit((int) $v7->get('scanner.max_duration', 300) + 60);
            $ran = $svc->run($id);
            json_response(['ran' => $ran, 'status' => $svc->status($id)]);
    }
} catch (ServiceException $e) {
    json_response(['error' => $e->getMessage()], $e->status());
} catch (Throwable $e) {
    error_log('[V7-Shield] API ' . $action . ': ' . $e->getMessage());
    json_response(['error' => 'Internal error.'], 500);
}
