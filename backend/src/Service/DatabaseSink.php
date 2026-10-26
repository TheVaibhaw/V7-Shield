<?php
declare(strict_types=1);

namespace V7Shield\Service;

use V7Shield\Repository\ScanRepository;
use V7Shield\Scanner\ScanSink;

/** Buffers scanner output and writes it to the database in batches. */
final class DatabaseSink implements ScanSink
{
    private array $findings = [];
    private array $skips = [];
    private int $skipsStored = 0;

    public function __construct(private readonly ScanRepository $repo, private readonly int $scanId, private readonly int $maxSkipsStored)
    {
    }

    public function finding(array $finding): void
    {
        $this->findings[] = $finding;
        if (count($this->findings) >= 100) {
            $this->flush();
        }
    }

    public function skipped(string $path, string $reason, string $detail): void
    {
        if ($this->skipsStored + count($this->skips) < $this->maxSkipsStored) {
            $this->skips[] = [$path, $reason, $detail];
        }
        if (count($this->skips) >= 200) {
            $this->flush();
        }
    }

    public function progress(array $stats): void
    {
        $this->flush();
        $this->repo->updateProgress($this->scanId, $stats);
    }

    public function flush(): void
    {
        $this->repo->insertFindings($this->scanId, $this->findings);
        $this->repo->insertSkips($this->scanId, $this->skips);
        $this->skipsStored += count($this->skips);
        $this->findings = $this->skips = [];
    }
}
