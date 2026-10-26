<?php
declare(strict_types=1);

namespace V7Shield\Service;

use V7Shield\Config;
use V7Shield\Repository\ScanRepository;
use V7Shield\Scanner\FileWalker;
use V7Shield\Scanner\ScanException;
use V7Shield\Scanner\Scanner;
use V7Shield\ServiceException;

final class ScanService
{
    public const STATUSES = ['queued', 'running', 'completed', 'failed', 'cancelled'];
    public const SEVERITIES = ['critical', 'high', 'medium', 'low'];
    public const CATEGORIES = ['execution', 'obfuscation', 'webshell', 'remote-code', 'file-signature'];

    public function __construct(private readonly Config $config, private readonly ScanRepository $repo)
    {
    }

    public function resolveTarget(string $targetId): array
    {
        return self::resolveConfiguredTarget($this->config, $targetId);
    }

    public static function resolveConfiguredTarget(Config $config, string $targetId): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $targetId)) {
            throw new ServiceException('Unknown scan target.', 422);
        }
        $targets = $config->get('targets', []);
        if (!isset($targets[$targetId]['path'])) {
            throw new ServiceException('Unknown scan target.', 422);
        }
        $real = realpath((string) $targets[$targetId]['path']);
        if ($real === false || !is_dir($real) || !is_readable($real)) {
            throw new ServiceException('The configured directory for this target is missing or unreadable.', 422);
        }
        return ['id' => $targetId, 'label' => (string) ($targets[$targetId]['label'] ?? $targetId), 'path' => $real];
    }

    public function failStale(): int
    {
        $running = (int) $this->config->get('jobs.stale_after', 900);
        $queued = $this->config->get('jobs.execution') === 'worker' ? (int) $this->config->get('jobs.queued_timeout', 3600) : $running;
        return $this->repo->failStale($running, $queued);
    }

    public function start(string $targetId, string $user, ?string $ip): int
    {
        $target = $this->resolveTarget($targetId);
        $this->failStale();
        $pdo = $this->repo->pdo();
        $lock = 'v7shield_start_' . $targetId;
        if ((int) $pdo->query('SELECT GET_LOCK(' . $pdo->quote($lock) . ', 5)')->fetchColumn() !== 1) {
            throw new ServiceException('Another scan request is being processed. Try again.', 429);
        }
        try {
            $max = (int) $this->config->get('jobs.max_starts_per_hour', 10);
            if ($this->repo->countStartedSince($user, 3600) >= $max) {
                throw new ServiceException("Rate limit reached: at most {$max} scans per hour.", 429);
            }
            if ($this->repo->countActiveForTarget($targetId) > 0) {
                throw new ServiceException('A scan for this target is already queued or running.', 409);
            }
            $s = $this->config->get('scanner');
            $scope = [
                'max_file_size' => $s['max_file_size'], 'max_depth' => $s['max_depth'], 'max_files' => $s['max_files'],
                'max_duration' => $s['max_duration'], 'exclusions' => array_values($s['exclusions']),
                'execution' => $this->config->get('jobs.execution'),
            ];
            return $this->repo->createJob($target['id'], $target['label'], $user, $ip, (string) $this->config->get('rules.version'), $scope);
        } finally {
            $pdo->query('SELECT RELEASE_LOCK(' . $pdo->quote($lock) . ')');
        }
    }

    public function run(int $jobId): bool
    {
        $job = $this->repo->find($jobId);
        if ($job === null || !$this->repo->claim($jobId)) {
            return false;
        }
        $stats = ['files_seen' => 0, 'files_scanned' => 0, 'files_skipped' => 0, 'findings' => 0, 'bytes_scanned' => 0, 'errors' => 0, 'limit_reached' => null];
        $sink = new DatabaseSink($this->repo, $jobId, (int) $this->config->get('scanner.max_skips_stored', 2000));
        try {
            $target = $this->resolveTarget((string) $job['target_id']);
            $scope = json_decode((string) $job['scope'], true) ?: [];
            $limits = array_merge($this->config->get('scanner'), array_intersect_key($scope, array_flip(['max_file_size', 'max_depth', 'max_files', 'max_duration'])));
            $walker = new FileWalker($target['path'], $scope['exclusions'] ?? $limits['exclusions'], (int) $limits['max_depth']);
            $rules = $this->config->get('rules');
            $scanner = new Scanner(new \V7Shield\Scanner\RuleEngine($rules), $rules['builtin'], $limits);
            $stats = $scanner->scan($walker, $sink);
            $sink->flush();
            $this->repo->finish($jobId, 'completed', $stats);
        } catch (ScanException | ServiceException $e) {
            $sink->flush();
            $this->repo->finish($jobId, 'failed', $stats, $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[V7-Shield] Scan ' . $jobId . ' failed: ' . $e->getMessage());
            try {
                $sink->flush();
            } catch (\Throwable) {
            }
            $this->repo->finish($jobId, 'failed', $stats, 'Internal scanner error. See server log.');
        }
        return true;
    }

    public function status(int $id): array
    {
        $job = $this->repo->find($id) ?? throw new ServiceException('Scan not found.', 404);
        return self::publicJob($job) + ['skip_reasons' => $this->repo->skipCounts($id)];
    }

    public function findings(int $scanId, array $filters, int $page, int $perPage = 25): array
    {
        $this->repo->find($scanId) ?? throw new ServiceException('Scan not found.', 404);
        $filters = self::cleanFilters($filters);
        $total = $this->repo->countFindings($scanId, $filters);
        $page = min(max(1, $page), max(1, (int) ceil($total / $perPage)));
        return [
            'items' => array_map([self::class, 'publicFinding'], $this->repo->findings($scanId, $filters, $perPage, ($page - 1) * $perPage)),
            'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'filters' => $filters,
        ];
    }

    public function finding(int $id): array
    {
        return self::publicFinding($this->repo->finding($id) ?? throw new ServiceException('Finding not found.', 404));
    }

    public function skips(int $scanId): array
    {
        return $this->repo->skips($scanId);
    }

    public function history(int $page, ?string $status, int $perPage = 20): array
    {
        $this->failStale();
        $status = in_array($status, self::STATUSES, true) ? $status : null;
        $total = $this->repo->countJobs($status);
        $page = min(max(1, $page), max(1, (int) ceil($total / $perPage)));
        return [
            'items' => array_map([self::class, 'publicJob'], $this->repo->history($perPage, ($page - 1) * $perPage, $status)),
            'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'status' => $status,
        ];
    }

    public function summary(): array
    {
        $this->failStale();
        $latest = $this->repo->latestCompleted();
        return [
            'targets' => count($this->config->get('targets', [])),
            'status_counts' => $this->repo->statusCounts(),
            'latest' => $latest ? self::publicJob($latest) : null,
            'latest_categories' => $latest ? $this->repo->categoryCounts((int) $latest['id']) : [],
            'recent' => array_map([self::class, 'publicJob'], $this->repo->history(5, 0)),
            'rules_version' => $this->config->get('rules.version'),
            'rule_count' => count($this->config->get('rules.rules', [])) + count($this->config->get('rules.builtin', [])),
        ];
    }

    public static function cleanFilters(array $f): array
    {
        $out = [];
        if (isset($f['severity']) && in_array($f['severity'], self::SEVERITIES, true)) {
            $out['severity'] = $f['severity'];
        }
        if (isset($f['category']) && in_array($f['category'], self::CATEGORIES, true)) {
            $out['category'] = $f['category'];
        }
        if (isset($f['q']) && is_string($f['q']) && trim($f['q']) !== '') {
            $out['q'] = \V7Shield\Text::cut(trim($f['q']), 100);
        }
        return $out;
    }

    public static function publicJob(array $j): array
    {
        $scope = is_string($j['scope'] ?? null) ? (json_decode($j['scope'], true) ?: []) : [];
        return [
            'id' => (int) $j['id'], 'target_id' => $j['target_id'], 'target_label' => $j['target_label'], 'status' => $j['status'],
            'requested_by' => $j['requested_by'], 'created_at' => $j['created_at'], 'started_at' => $j['started_at'] ?? null,
            'finished_at' => $j['finished_at'] ?? null, 'files_seen' => (int) ($j['files_seen'] ?? 0), 'files_scanned' => (int) $j['files_scanned'],
            'files_skipped' => (int) $j['files_skipped'], 'findings_count' => (int) $j['findings_count'],
            'critical' => (int) $j['critical_count'], 'high' => (int) $j['high_count'], 'medium' => (int) $j['medium_count'], 'low' => (int) $j['low_count'],
            'errors_count' => (int) $j['errors_count'], 'bytes_scanned' => (int) ($j['bytes_scanned'] ?? 0),
            'limit_reached' => $j['limit_reached'], 'error_message' => $j['error_message'] ?? null,
            'rules_version' => $j['rules_version'] ?? null,
            'exclusions' => $scope['exclusions'] ?? null,
        ];
    }

    public static function publicFinding(array $f): array
    {
        return [
            'id' => (int) $f['id'], 'scan_id' => (int) $f['scan_id'], 'rule_id' => $f['rule_id'], 'rule_name' => $f['rule_name'],
            'category' => $f['category'], 'severity' => $f['severity'], 'confidence' => (int) $f['confidence'],
            'classification' => $f['classification'], 'file_path' => $f['file_path'], 'file_size' => (int) $f['file_size'],
            'file_sha256' => $f['file_sha256'], 'line' => (int) $f['line_no'], 'match_count' => (int) $f['match_count'],
            'evidence' => $f['evidence'], 'description' => $f['description'], 'false_positives' => $f['false_positives'],
            'created_at' => $f['created_at'],
        ];
    }
}
