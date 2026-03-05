<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

trait HidesMaskedValues
{
    protected array $maskedKeys = [];

    private array $patterns = [];

    /** @var array|string[]  */
    public static array $SENSITIVE_KEYS = ['password', 'secret', 'token', 'php-auth-pw'];

    public function hideMaskedValues(array $data, ?string $prefix = null): mixed
    {
        foreach ($data as $key => $value) {
            $prefixed = is_null($prefix) ? null : "$prefix.$key";
            if (is_string($key) && $this->isMaskedKey($key)) {
                $data[$key] = $this->maskValue($value);
            } elseif (!is_null($prefix) && $this->isMaskedKey($prefixed)) {
                $data[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->hideMaskedValues($value, $prefixed ?? "$key");
            }
        }

        return $data;
    }

    public function addMaskedKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->maskedKeys[] = strtolower($key);
        }
        $this->maskedKeys = array_unique($this->maskedKeys);
        $this->patterns = array_filter(
            $this->maskedKeys,
            fn($key) => (bool) array_filter(['*', '?', '[', ']'], fn($n) => str_contains($key, $n))
        );
    }

    public function maskValue(mixed $value): string
    {
        if (is_string($value)) {
            if (strlen($value) > 9) {
                return substr($value, 0, 2) . '***' . substr($value, -2);
            } elseif (strlen($value) > 5) {
                return substr($value, 0, 2) . '***';
            }

            return str_repeat('*', strlen($value));
        }

        return '***';
    }

    protected function isMaskedKey(string $key): bool
    {
        $key = strtolower($key);

        // Special case for stack data, skip to avoid recursive data
        if ($key === 'phpdebugbar_stack_data') {
            return true;
        }

        if (in_array($key, $this->maskedKeys, true)) {
            return true;
        }

        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }

        foreach (static::$SENSITIVE_KEYS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

}
