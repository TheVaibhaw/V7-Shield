<?php
declare(strict_types=1);

namespace V7Shield\Scanner;

/** Detects file types from magic bytes and compares them with extensions. */
final class SignatureDetector
{
    public const PHP_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'inc'];
    public const ALT_PHP_EXTENSIONS = ['php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar'];

    /** extension => list of acceptable detected types */
    private const EXPECTED = [
        'jpg' => ['jpeg'], 'jpeg' => ['jpeg'], 'png' => ['png'], 'gif' => ['gif'], 'webp' => ['webp'],
        'bmp' => ['bmp'], 'ico' => ['ico', 'png'], 'pdf' => ['pdf'], 'zip' => ['zip'], 'gz' => ['gzip'],
        'mp4' => ['mp4'], 'woff' => ['woff'], 'woff2' => ['woff2'],
    ];

    /** Extensions where a PHP open tag is not expected. */
    public const NON_PHP_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'svg', 'pdf', 'zip', 'gz',
        'mp4', 'mp3', 'woff', 'woff2', 'ttf', 'eot', 'css', 'js', 'json', 'txt', 'xml', 'csv', 'log', 'htaccess', 'ini'];

    public function detect(string $head): ?string
    {
        return match (true) {
            str_starts_with($head, "\xFF\xD8\xFF") => 'jpeg',
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => 'png',
            str_starts_with($head, 'GIF87a'), str_starts_with($head, 'GIF89a') => 'gif',
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' => 'webp',
            str_starts_with($head, 'BM') => 'bmp',
            str_starts_with($head, "\x00\x00\x01\x00") => 'ico',
            str_starts_with($head, '%PDF-') => 'pdf',
            str_starts_with($head, "PK\x03\x04"), str_starts_with($head, "PK\x05\x06") => 'zip',
            str_starts_with($head, "\x1F\x8B") => 'gzip',
            substr($head, 4, 4) === 'ftyp' => 'mp4',
            str_starts_with($head, 'wOFF') => 'woff',
            str_starts_with($head, 'wOF2') => 'woff2',
            str_starts_with($head, "\x7FELF") => 'elf',
            str_starts_with($head, 'MZ') => 'pe',
            in_array(substr($head, 0, 4), ["\xCF\xFA\xED\xFE", "\xCE\xFA\xED\xFE", "\xFE\xED\xFA\xCF", "\xCA\xFE\xBA\xBE"], true) => 'macho',
            default => null,
        };
    }

    /** True when the extension has a known signature and the content does not match it. */
    public function mismatch(string $ext, ?string $detected): bool
    {
        return isset(self::EXPECTED[$ext]) && !in_array($detected, self::EXPECTED[$ext], true);
    }

    public function isExecutable(?string $detected): bool
    {
        return in_array($detected, ['elf', 'pe', 'macho'], true);
    }

    public function isBinary(string $head): bool
    {
        return str_contains($head, "\0");
    }

    /** Find a PHP open tag. Returns byte offset or null. Ignores "<?xml". */
    public function phpTagOffset(string $content): ?int
    {
        if (preg_match('~<\?(?:php\b|=)~i', $content, $m, PREG_OFFSET_CAPTURE)) {
            return $m[0][1];
        }
        return null;
    }
}
