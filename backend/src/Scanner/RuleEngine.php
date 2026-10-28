<?php
declare(strict_types=1);

namespace V7Shield\Scanner;

final class RuleEngine
{
    public const EVIDENCE_LENGTH = 160;

    /** @var list<array> */
    private array $rules = [];

    public function __construct(array $ruleConfig)
    {
        foreach ($ruleConfig['rules'] ?? [] as $rule) {
            foreach (['id', 'name', 'category', 'severity', 'confidence', 'pattern'] as $key) {
                if (!isset($rule[$key])) {
                    throw new ScanException('Invalid rule configuration: missing ' . $key);
                }
            }
            if (@preg_match($rule['pattern'], '') === false) {
                throw new ScanException('Invalid rule pattern: ' . $rule['id']);
            }
            if (array_key_exists('extensions', $rule)) {
                $rule['extensions'] = self::normaliseExtensions($rule['extensions'], (string) $rule['id']);
            }
            $this->rules[] = $rule;
        }
    }

    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Runs the rules that apply to a file.
     *
     * A rule without an `extensions` list applies only in PHP context (a PHP-family
     * file or a file containing a PHP tag). A rule with an `extensions` list applies
     * only to files with one of those extensions, e.g. `'extensions' => ['js']`.
     *
     * @param list<string> $failed receives IDs of rules that could not be evaluated
     * @param string|null $extension lower-case file extension without the dot
     * @param bool $phpContext whether the file is PHP or contains a PHP tag
     * @return list<array{rule:array,line:int,evidence:string,match_count:int}>
     */
    public function match(string $content, array &$failed = [], ?string $extension = null, bool $phpContext = true): array
    {
        $out = [];
        $failed = [];
        foreach ($this->rules as $rule) {
            if (!self::applies($rule, $extension, $phpContext)) {
                continue;
            }
            $n = @preg_match_all($rule['pattern'], $content, $m, PREG_OFFSET_CAPTURE);
            if ($n === false) {
                // Backtrack/JIT limit on pathological input: report it instead of silently passing.
                $failed[] = $rule['id'];
                continue;
            }
            if ($n > 0) {
                $offset = $m[0][0][1];
                $out[] = [
                    'rule' => $rule,
                    'line' => self::lineAt($content, $offset),
                    'evidence' => self::evidence($content, $offset, strlen($m[0][0][0])),
                    'match_count' => $n,
                ];
            }
        }
        return $out;
    }

    private static function applies(array $rule, ?string $extension, bool $phpContext): bool
    {
        if (!isset($rule['extensions'])) {
            return $phpContext;
        }
        return $extension !== null && in_array(strtolower($extension), $rule['extensions'], true);
    }

    /** @return list<string> */
    private static function normaliseExtensions(mixed $extensions, string $id): array
    {
        if (!is_array($extensions) || $extensions === []) {
            throw new ScanException('Invalid rule extensions: ' . $id);
        }
        $out = [];
        foreach ($extensions as $ext) {
            if (!is_string($ext) || !preg_match('~^\.?[a-z0-9]{1,10}$~i', $ext)) {
                throw new ScanException('Invalid rule extensions: ' . $id);
            }
            $out[] = strtolower(ltrim($ext, '.'));
        }
        return array_values(array_unique($out));
    }

    public static function lineAt(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, $offset) + 1;
    }

    public static function evidence(string $content, int $offset, int $length): string
    {
        $len = self::EVIDENCE_LENGTH;
        $matchLen = min($length, $len - 40);
        $start = max(0, $offset - 30);
        $excerpt = substr($content, $start, ($offset - $start) + $matchLen + 30);
        $excerpt = (string) preg_replace('~\s+~', ' ', $excerpt);
        $excerpt = (string) preg_replace('~[^\x20-\x7E]~', '?', $excerpt);
        $excerpt = (string) preg_replace('~([A-Za-z0-9+/=\\\\]{24})[A-Za-z0-9+/=\\\\]{16,}~', '$1…', $excerpt);
        $excerpt = trim($excerpt);
        if (\V7Shield\Text::length($excerpt) > $len) {
            $excerpt = \V7Shield\Text::cut($excerpt, $len) . '…';
        }
        return ($start > 0 ? '…' : '') . $excerpt;
    }
}
