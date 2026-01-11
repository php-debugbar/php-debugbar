<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

trait HidesMaskedValues
{
    protected array $maskedKeys = [];

    /** @var array|string[]  */
    public static array $SENSITIVE_KEYS = ['password', 'secret', 'token', 'php-auth-pw'];

    public function hideMaskedValues(array $data): mixed
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isMaskedKey($key)) {
                $data[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->hideMaskedValues($value);
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

        foreach (static::$SENSITIVE_KEYS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

}
