<?php

declare(strict_types=1);
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2025 Barry vd. Heuvel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

/**
 * Data Hasher
 */
readonly class DataHasher
{
    public function __construct(private readonly string $key) {}

    public function sign(mixed $data): string
    {
        if (is_array($data)) {
            sort($data);
        }
        $data = json_encode($data);

        return hash_hmac('sha256', $data, $this->key);
    }

    public function verify(mixed $data, string $signature): bool
    {
        if (is_array($data) && isset($data['signature'])) {
            unset($data['signature']);
        }

        return hash_equals($this->sign($data), $signature);
    }
}
