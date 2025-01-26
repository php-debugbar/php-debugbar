<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

/**
 * Request Hasher
 */
class DataHasher
{
    public function __construct(private string $key)
    {
    }

    public function sign($data)
    {
        if (is_array($data)){
            sort($data);
        }
        $data = json_encode($data);

        return hash_hmac('sha256', $data, $this->key);
    }

    public function verify($data, string $signature)
    {
        if (is_array($data) && isset($data['signature'])) {
            unset ($data['signature']);
        }

        return hash_equals($this->sign($data), $signature);
    }
}