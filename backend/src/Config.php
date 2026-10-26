<?php
declare(strict_types=1);

namespace V7Shield;

final class Config
{
    private function __construct(private readonly array $data)
    {
    }

    public static function load(string $dir): self
    {
        $defaults = require $dir . '/config.example.php';
        // V7SHIELD_CONFIG may point to a local config stored elsewhere (deployments, tests).
        $localFile = getenv('V7SHIELD_CONFIG') ?: $dir . '/config.local.php';
        $local = is_file($localFile) ? require $localFile : [];
        $data = array_replace_recursive($defaults, is_array($local) ? $local : []);
        foreach (['users', 'targets'] as $key) {
            $data[$key] = $local[$key] ?? [];
        }
        if (isset($local['scanner']['exclusions'])) {
            $data['scanner']['exclusions'] = $local['scanner']['exclusions'];
        }
        $data['rules'] = require $dir . '/rules.php';
        $data['is_configured'] = is_file($localFile);
        return new self($data);
    }

    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    }

    public function targetLabels(): array
    {
        $out = [];
        foreach ($this->get('targets', []) as $id => $t) {
            $out[(string) $id] = (string) ($t['label'] ?? $id);
        }
        return $out;
    }
}
