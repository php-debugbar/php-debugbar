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

use Symfony\Component\Uid\Ulid;

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
        return (new Ulid())->toString();
    }
}
