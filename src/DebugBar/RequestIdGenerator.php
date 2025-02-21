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
 * Basic request ID generator
 */
class RequestIdGenerator implements RequestIdGeneratorInterface
{
    /**
     * @return string
     */
    public function generate()
    {
        return 'X'.bin2hex(random_bytes(16));
    }
}
