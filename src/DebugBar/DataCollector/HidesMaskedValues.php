<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

trait HidesMaskedValues
{
    protected array $maskedKeys = [];

    /** @var array|string[]  */
    public static array $SENSITIVE_KEYS = ['password', 'secret', 'token'];

    public function addMaskedKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->maskedKeys[] = strtolower($key);
        }
        $this->maskedKeys = array_unique($this->maskedKeys);
    }

    protected function isMaskedKey(string $key): bool
    {
        $key = strtolower($key);

        // Special case for stack data, skip to avoid recursive data
        if ($key === 'phpdebugbar_stack_data') {
            return true;
        }

        if (in_array($key, $this->maskedKeys)) {
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
