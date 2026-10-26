<?php
declare(strict_types=1);

namespace V7Shield;

final class Text
{
    public static function clean(string $s): string
    {
        if ($s === '' || preg_match('//u', $s) === 1) {
            return $s;
        }
        return htmlspecialchars_decode(htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_QUOTES);
    }

    public static function length(string $s): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($s, 'UTF-8');
        }
        $n = preg_match_all('/./su', $s);
        return $n === false ? strlen($s) : $n;
    }

    public static function cut(string $s, int $max): string
    {
        $s = self::clean($s);
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max, 'UTF-8');
        }
        if (strlen($s) <= $max) {
            return $s;
        }
        preg_match('/^.{0,' . max(0, $max) . '}/su', $s, $m);
        return $m[0] ?? '';
    }
}
