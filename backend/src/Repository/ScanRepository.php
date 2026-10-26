<?php
declare(strict_types=1);

namespace V7Shield\Repository;

use PDO;
use V7Shield\Scanner\Scanner;

final class ScanRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function createJob(string $targetId, string $label, string $user, ?string $ip, string $rulesVersion, array $scope): int
    {
        $st = $this->pdo->prepare('INSERT INTO scan_jobs (target_id, target_label, requested_by, requested_ip, rules_version, scope, created_at)
            VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $st->execute([$targetId, $label, $user, $ip, $rulesVersion, json_encode($scope, JSON_UNESCAPED_SLASHES)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function claim(int $id): bool
    {
        $st = $this->pdo->prepare("UPDATE scan_jobs SET status = 'running', started_at = UTC_TIMESTAMP(), heartbeat_at = UTC_TIMESTAMP()
            WHERE id = ? AND status = 'queued'");
        $st->execute([$id]);
        return $st->rowCount() === 1;
    }

    public function nextQueuedId(): ?int
    {
        $id = $this->pdo->query("SELECT id FROM scan_jobs WHERE status = 'queued' ORDER BY id LIMIT 1")->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function updateProgress(int $id, array $s): void
    {
        $st = $this->pdo->prepare('UPDATE scan_jobs SET files_seen = ?, files_scanned = ?, files_skipped = ?, findings_count = ?,
            errors_count = ?, bytes_scanned = ?, heartbeat_at = UTC_TIMESTAMP() WHERE id = ?');
        $st->execute([$s['files_seen'], $s['files_scanned'], $s['files_skipped'], $s['findings'], $s['errors'], $s['bytes_scanned'], $id]);
    }

    public function finish(int $id, string $status, array $s, ?string $error = null): void
    {
        $this->updateProgress($id, $s);
        $st = $this->pdo->prepare("UPDATE scan_jobs j SET status = ?, finished_at = UTC_TIMESTAMP(), limit_reached = ?, error_message = ?,
            critical_count = (SELECT COUNT(*) FROM scan_findings WHERE scan_id = j.id AND severity = 'critical'),
            high_count     = (SELECT COUNT(*) FROM scan_findings WHERE scan_id = j.id AND severity = 'high'),
            medium_count   = (SELECT COUNT(*) FROM scan_findings WHERE scan_id = j.id AND severity = 'medium'),
            low_count      = (SELECT COUNT(*) FROM scan_findings WHERE scan_id = j.id AND severity = 'low')
            WHERE id = ? AND status = 'running'");
        $st->execute([$status, $s['limit_reached'] ?? null, $error === null ? null : \V7Shield\Text::cut($error, 500), $id]);
    }

    public function insertFindings(int $scanId, array $findings): void
    {
        if (!$findings) {
            return;
        }
        $st = $this->pdo->prepare('INSERT INTO scan_findings (scan_id, rule_id, rule_name, category, severity, severity_rank, confidence,
            classification, file_path, file_size, file_sha256, line_no, match_count, evidence, description, false_positives)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $this->pdo->beginTransaction();
        try {
            foreach ($findings as $f) {
                $st->execute([
                    $scanId, $f['rule_id'], $f['rule_name'], $f['category'], $f['severity'], Scanner::SEVERITY_RANK[$f['severity']],
                    $f['confidence'], 'suspicious', \V7Shield\Text::cut($f['file_path'], 1024), $f['file_size'], $f['file_sha256'],
                    $f['line'], $f['match_count'], \V7Shield\Text::cut($f['evidence'], 500), \V7Shield\Text::cut($f['description'], 500),
                    \V7Shield\Text::cut($f['false_positives'], 500),
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function insertSkips(int $scanId, array $skips): void
    {
        if (!$skips) {
            return;
        }
        $st = $this->pdo->prepare('INSERT INTO scan_skips (scan_id, file_path, reason, detail) VALUES (?, ?, ?, ?)');
        $this->pdo->beginTransaction();
        try {
            foreach ($skips as [$path, $reason, $detail]) {
                $st->execute([$scanId, \V7Shield\Text::cut($path, 1024), $reason, \V7Shield\Text::cut($detail, 255)]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM scan_jobs WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function countActiveForTarget(string $targetId): int
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM scan_jobs WHERE target_id = ? AND status IN ('queued','running')");
        $st->execute([$targetId]);
        return (int) $st->fetchColumn();
    }

    public function countStartedSince(string $user, int $seconds): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM scan_jobs WHERE requested_by = ? AND created_at >= UTC_TIMESTAMP() - INTERVAL ? SECOND');
        $st->execute([$user, $seconds]);
        return (int) $st->fetchColumn();
    }

    public function failStale(int $seconds): int
    {
        $st = $this->pdo->prepare("UPDATE scan_jobs SET status = 'failed', finished_at = UTC_TIMESTAMP(),
            error_message = 'Scan stopped responding (process ended or timed out).'
            WHERE status = 'running' AND COALESCE(heartbeat_at, started_at) < UTC_TIMESTAMP() - INTERVAL ? SECOND");
        $st->execute([$seconds]);
        return $st->rowCount();
    }

    public function history(int $limit, int $offset, ?string $status = null): array
    {
        $sql = 'SELECT id, target_id, target_label, status, requested_by, created_at, started_at, finished_at, files_scanned, files_skipped,
            findings_count, critical_count, high_count, medium_count, low_count, errors_count, limit_reached FROM scan_jobs';
        $args = [];
        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $args[] = $status;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        $st = $this->pdo->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    public function countJobs(?string $status = null): int
    {
        if ($status === null) {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM scan_jobs')->fetchColumn();
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM scan_jobs WHERE status = ?');
        $st->execute([$status]);
        return (int) $st->fetchColumn();
    }

    /** @param array{severity?:string,category?:string,q?:string} $filters */
    public function findings(int $scanId, array $filters, int $limit, int $offset): array
    {
        [$where, $args] = $this->findingWhere($scanId, $filters);
        $st = $this->pdo->prepare('SELECT * FROM scan_findings WHERE ' . $where
            . ' ORDER BY severity_rank DESC, confidence DESC, id LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset));
        $st->execute($args);
        return $st->fetchAll();
    }

    public function countFindings(int $scanId, array $filters): int
    {
        [$where, $args] = $this->findingWhere($scanId, $filters);
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM scan_findings WHERE ' . $where);
        $st->execute($args);
        return (int) $st->fetchColumn();
    }

    public function finding(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM scan_findings WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function skips(int $scanId, int $limit = 200): array
    {
        $st = $this->pdo->prepare('SELECT file_path, reason, detail FROM scan_skips WHERE scan_id = ? ORDER BY reason, id LIMIT ' . max(1, $limit));
        $st->execute([$scanId]);
        return $st->fetchAll();
    }

    public function skipCounts(int $scanId): array
    {
        $st = $this->pdo->prepare('SELECT reason, COUNT(*) n FROM scan_skips WHERE scan_id = ? GROUP BY reason ORDER BY n DESC');
        $st->execute([$scanId]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function latestCompleted(): ?array
    {
        $row = $this->pdo->query("SELECT * FROM scan_jobs WHERE status = 'completed' ORDER BY finished_at DESC, id DESC LIMIT 1")->fetch();
        return $row ?: null;
    }

    public function statusCounts(): array
    {
        return $this->pdo->query('SELECT status, COUNT(*) FROM scan_jobs GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function categoryCounts(int $scanId): array
    {
        $st = $this->pdo->prepare('SELECT category, COUNT(*) FROM scan_findings WHERE scan_id = ? GROUP BY category ORDER BY 2 DESC');
        $st->execute([$scanId]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function findingWhere(int $scanId, array $filters): array
    {
        $where = 'scan_id = ?';
        $args = [$scanId];
        if (!empty($filters['severity'])) {
            $where .= ' AND severity = ?';
            $args[] = $filters['severity'];
        }
        if (!empty($filters['category'])) {
            $where .= ' AND category = ?';
            $args[] = $filters['category'];
        }
        if (!empty($filters['q'])) {
            $where .= ' AND (file_path LIKE ? OR rule_id LIKE ? OR rule_name LIKE ?)';
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            array_push($args, $like, $like, $like);
        }
        return [$where, $args];
    }
}
