<?php
declare(strict_types=1);

namespace V7Shield\Scanner;

/**
 * Iterative, read-only directory walker.
 *
 * - Never follows symbolic links (files or directories).
 * - Verifies every directory's real path stays inside the scan root.
 * - Includes hidden files unless an exclusion matches.
 * - Yields ['type' => 'file'|'skip', 'path' => relative, 'abs' => absolute, 'reason' => ?, 'detail' => ?].
 */
final class FileWalker
{
    private string $root;

    /** @param list<string> $exclusions fnmatch globs; a trailing "/" matches directories only */
    public function __construct(string $root, private readonly array $exclusions, private readonly int $maxDepth)
    {
        $real = realpath($root);
        if ($real === false || !is_dir($real)) {
            throw new ScanException('Scan root does not exist or is not a directory.');
        }
        $this->root = rtrim($real, DIRECTORY_SEPARATOR);
    }

    public function root(): string
    {
        return $this->root;
    }

    /** @return \Generator<array{type:string,path:string,abs:string,reason?:string,detail?:string}> */
    public function walk(): \Generator
    {
        $stack = [['', 0]];
        while ($stack) {
            [$rel, $depth] = array_pop($stack);
            $absDir = $rel === '' ? $this->root : $this->root . '/' . $rel;

            if (!$this->insideRoot($absDir)) {
                yield $this->skip($rel, $absDir, 'outside_root', 'Directory resolves outside the scan root.');
                continue;
            }
            $entries = @scandir($absDir);
            if ($entries === false) {
                yield $this->skip($rel, $absDir, 'unreadable', 'Directory could not be listed (permissions).');
                continue;
            }
            $subdirs = [];
            foreach ($entries as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $childRel = $rel === '' ? $name : $rel . '/' . $name;
                $childAbs = $absDir . '/' . $name;

                if (is_link($childAbs)) {
                    yield $this->skip($childRel, $childAbs, 'symlink', 'Symbolic links are never followed.');
                    continue;
                }
                $isDir = is_dir($childAbs);
                if ($this->isExcluded($childRel, $name, $isDir)) {
                    yield $this->skip($childRel . ($isDir ? '/' : ''), $childAbs, 'excluded', 'Matched an exclusion pattern.');
                    continue;
                }
                if ($isDir) {
                    if ($depth + 1 > $this->maxDepth) {
                        yield $this->skip($childRel . '/', $childAbs, 'max_depth', 'Directory deeper than the configured limit.');
                        continue;
                    }
                    $subdirs[] = [$childRel, $depth + 1];
                } elseif (is_file($childAbs)) {
                    yield ['type' => 'file', 'path' => $childRel, 'abs' => $childAbs];
                } else {
                    yield $this->skip($childRel, $childAbs, 'special', 'Not a regular file (socket, device or FIFO).');
                }
            }
            // Reverse so traversal order is alphabetical.
            foreach (array_reverse($subdirs) as $d) {
                $stack[] = $d;
            }
        }
    }

    public function isExcluded(string $rel, string $name, bool $isDir): bool
    {
        foreach ($this->exclusions as $pattern) {
            $pattern = trim((string) $pattern);
            if ($pattern === '') {
                continue;
            }
            $dirOnly = str_ends_with($pattern, '/');
            $p = rtrim($pattern, '/');
            if ($dirOnly && !$isDir) {
                continue;
            }
            // Patterns without "/" match the basename at any depth; others match the relative path.
            $subject = str_contains($p, '/') ? $rel : $name;
            if (fnmatch($p, $subject)) {
                return true;
            }
        }
        return false;
    }

    private function insideRoot(string $absDir): bool
    {
        $real = realpath($absDir);
        return $real !== false && ($real === $this->root || str_starts_with($real, $this->root . '/'));
    }

    private function skip(string $rel, string $abs, string $reason, string $detail): array
    {
        return ['type' => 'skip', 'path' => $rel === '' ? '.' : $rel, 'abs' => $abs, 'reason' => $reason, 'detail' => $detail];
    }
}
