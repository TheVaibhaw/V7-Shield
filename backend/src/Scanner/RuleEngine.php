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
            $this->rules[] = $rule;
        }
    }

    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * @param list<string> $failed receives IDs of rules that could not be evaluated
     * @return list<array{rule:array,line:int,evidence:string,match_count:int}>
     */
    public function match(string $content, array &$failed = []): array
    {
        $out = [];
        $failed = [];
        foreach ($this->rules as $rule) {
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
        if (mb_strlen($excerpt) > $len) {
            $excerpt = mb_substr($excerpt, 0, $len) . '…';
        }
        return ($start > 0 ? '…' : '') . $excerpt;
    }
}
