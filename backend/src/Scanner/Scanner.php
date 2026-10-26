<?php
declare(strict_types=1);

namespace V7Shield\Scanner;

/**
 * Read-only malware heuristics scanner.
 *
 * Files are only opened for reading. Nothing is executed, included, modified,
 * moved or deleted. Findings are always classified as "suspicious": a rule
 * match is an indicator for human review, not confirmation of malware.
 */
final class Scanner
{
    public const SEVERITY_RANK = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

    private readonly SignatureDetector $sig;

    public function __construct(
        private readonly RuleEngine $rules,
        private readonly array $builtin,
        private readonly array $limits,
    ) {
        $this->sig = new SignatureDetector();
    }

    /**
     * @return array{files_seen:int,files_scanned:int,files_skipped:int,findings:int,bytes_scanned:int,errors:int,limit_reached:?string,duration:float}
     */
    public function scan(FileWalker $walker, ScanSink $sink): array
    {
        $maxFiles = (int) $this->limits['max_files'];
        $maxSize = (int) $this->limits['max_file_size'];
        $maxDuration = (float) $this->limits['max_duration'];
        $perFile = (int) ($this->limits['max_findings_per_file'] ?? 20);
        $started = microtime(true);
        $lastProgress = 0.0;
        $stats = ['files_seen' => 0, 'files_scanned' => 0, 'files_skipped' => 0, 'findings' => 0, 'bytes_scanned' => 0, 'errors' => 0, 'limit_reached' => null, 'duration' => 0.0];

        foreach ($walker->walk() as $entry) {
            if ($entry['type'] === 'skip') {
                $stats['files_skipped']++;
                if (in_array($entry['reason'], ['unreadable', 'outside_root'], true)) {
                    $stats['errors']++;
                }
                $sink->skipped($entry['path'], $entry['reason'], $entry['detail']);
                continue;
            }
            $stats['files_seen']++;
            if ($stats['files_scanned'] >= $maxFiles) {
                $stats['limit_reached'] = 'max_files';
                break;
            }
            if (microtime(true) - $started > $maxDuration) {
                $stats['limit_reached'] = 'max_duration';
                break;
            }

            $result = $this->scanFile($entry['abs'], $entry['path'], $maxSize, $perFile);
            if (isset($result['skip'])) {
                $stats['files_skipped']++;
                if ($result['skip'][0] !== 'oversized' && $result['skip'][0] !== 'empty') {
                    $stats['errors']++;
                }
                $sink->skipped($entry['path'], $result['skip'][0], $result['skip'][1]);
            } else {
                $stats['files_scanned']++;
                $stats['bytes_scanned'] += $result['size'];
                foreach ($result['findings'] as $f) {
                    $stats['findings']++;
                    $sink->finding($f);
                }
                if ($result['partial'] !== null) {
                    $stats['errors']++;
                    $sink->skipped($entry['path'], 'rule_error', $result['partial']);
                }
            }

            $now = microtime(true);
            if ($now - $lastProgress >= 1.0) {
                $stats['duration'] = round($now - $started, 2);
                $sink->progress($stats);
                $lastProgress = $now;
            }
        }
        $stats['duration'] = round(microtime(true) - $started, 2);
        $sink->progress($stats);
        return $stats;
    }

    /** @return array{size:int,findings:list<array>,partial:?string}|array{skip:array{0:string,1:string}} */
    public function scanFile(string $abs, string $rel, int $maxSize, int $perFile = 20): array
    {
        clearstatcache(true, $abs);
        $size = @filesize($abs);
        if ($size === false) {
            return ['skip' => ['stat_error', 'File size could not be read.']];
        }
        if ($size > $maxSize) {
            return ['skip' => ['oversized', sprintf('File is %s; limit is %s.', self::bytes($size), self::bytes($maxSize))]];
        }
        if ($size === 0) {
            return ['skip' => ['empty', 'Empty file.']];
        }
        if (!is_readable($abs)) {
            return ['skip' => ['unreadable', 'File is not readable by the scanner user.']];
        }
        $content = @file_get_contents($abs, false, null, 0, $maxSize + 1);
        if ($content === false) {
            return ['skip' => ['read_error', 'File could not be read.']];
        }

        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        $head = substr($content, 0, 64);
        $detected = $this->sig->detect($head);
        $isPhpFile = in_array($ext, SignatureDetector::PHP_EXTENSIONS, true);
        $phpOffset = $this->sig->phpTagOffset($content);
        $raw = [];
        $failedRules = [];

        if (in_array($ext, SignatureDetector::ALT_PHP_EXTENSIONS, true)) {
            $raw[] = $this->builtinFinding('V7-FS-001', 1, 'Extension: .' . $ext);
        }
        if ($this->sig->isExecutable($detected)) {
            $raw[] = $this->builtinFinding('V7-SIG-003', 1, 'Detected ' . strtoupper((string) $detected) . ' executable with extension ".' . $ext . '"');
        } elseif ($this->sig->mismatch($ext, $detected)) {
            $raw[] = $this->builtinFinding('V7-SIG-002', 1, 'Extension ".' . $ext . '" but content looks like ' . ($detected ?? ($this->sig->isBinary($head) ? 'unknown binary' : 'text')));
        }
        if (!$isPhpFile && $phpOffset !== null && ($ext === '' || in_array($ext, SignatureDetector::NON_PHP_EXTENSIONS, true))) {
            $raw[] = $this->builtinFinding('V7-SIG-001', RuleEngine::lineAt($content, $phpOffset), RuleEngine::evidence($content, $phpOffset, 5));
        }

        if ($isPhpFile || $phpOffset !== null) {
            foreach ($this->rules->match($content, $failedRules) as $m) {
                $r = $m['rule'];
                $raw[] = [
                    'rule_id' => $r['id'], 'rule_name' => $r['name'], 'category' => $r['category'],
                    'severity' => $r['severity'], 'confidence' => (int) $r['confidence'],
                    'description' => $r['description'] ?? '', 'false_positives' => $r['false_positives'] ?? '',
                    'line' => $m['line'], 'evidence' => $m['evidence'], 'match_count' => $m['match_count'],
                ];
            }
        }

        $partial = $failedRules ? 'Rules not evaluated (regex limit): ' . implode(', ', $failedRules) : null;
        if (!$raw) {
            return ['size' => $size, 'findings' => [], 'partial' => $partial];
        }
        usort($raw, fn($a, $b) => self::SEVERITY_RANK[$b['severity']] <=> self::SEVERITY_RANK[$a['severity']]);
        $hash = hash('sha256', $content);
        $findings = [];
        foreach (array_slice($raw, 0, $perFile) as $f) {
            $findings[] = $f + ['file_path' => $rel, 'file_size' => $size, 'file_sha256' => $hash, 'classification' => 'suspicious'];
        }
        return ['size' => $size, 'findings' => $findings, 'partial' => $partial];
    }

    private function builtinFinding(string $id, int $line, string $evidence): array
    {
        $b = $this->builtin[$id];
        return [
            'rule_id' => $id, 'rule_name' => $b['name'], 'category' => $b['category'], 'severity' => $b['severity'],
            'confidence' => (int) $b['confidence'], 'description' => $b['description'], 'false_positives' => $b['false_positives'],
            'line' => $line, 'evidence' => $evidence, 'match_count' => 1,
        ];
    }

    public static function bytes(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : ($b >= 1024 ? round($b / 1024, 1) . ' KB' : $b . ' B');
    }

    public static function fromConfig(\V7Shield\Config $config): self
    {
        $rules = $config->get('rules');
        return new self(new RuleEngine($rules), $rules['builtin'], $config->get('scanner'));
    }
}
