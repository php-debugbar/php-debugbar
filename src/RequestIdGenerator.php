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
        // 48-bit millisecond timestamp (lexical sort = time sort)
        $t = (int) (microtime(true) * 1000);

        $time
            = chr(($t >> 40) & 0xFF)
            . chr(($t >> 32) & 0xFF)
            . chr(($t >> 24) & 0xFF)
            . chr(($t >> 16) & 0xFF)
            . chr(($t >>  8) & 0xFF)
            . chr(($t >>  0) & 0xFF);

        $rand = random_bytes(10);

        return bin2hex($time . $rand);
    }
}
