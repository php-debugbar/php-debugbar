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

namespace DebugBar\DataCollector;

/**
 * Indicates that a DataCollector is able to run action use the OpenHandler
 */
interface Actionable
{
    /**
     * Execute an action with a possible payload.
     *
     */
    public function executionAction(string $action, ?array $payload = null): mixed;
}
