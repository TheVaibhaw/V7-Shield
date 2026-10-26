<?php
declare(strict_types=1);

namespace V7Shield\Scanner;

/** Receives scanner output (database repository, CLI printer, tests). */
interface ScanSink
{
    public function finding(array $finding): void;

    public function skipped(string $path, string $reason, string $detail): void;

    public function progress(array $stats): void;
}
