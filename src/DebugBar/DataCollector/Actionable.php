<?php
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
     * @param string $action
     * @param array|null $payload
     * @return mixed
     */
    function executionAction($action, array $payload = null);
}
