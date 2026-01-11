<?php

declare(strict_types=1);

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
 * Basic request ID generator
 */
class RequestIdGenerator implements RequestIdGeneratorInterface
{
    public function generate(): string
    {
        // milliseconds since Unix epoch (13 digits)
        $ms = (int) floor(microtime(true) * 1000);

        // fixed-width decimal timestamp so lexical == chronological
        $prefix = sprintf('%013d', $ms);

        // random suffix (20 hex chars if 10 bytes)
        $suffix = bin2hex(random_bytes(10));

        return 'X' . $prefix . $suffix;
    }
}
