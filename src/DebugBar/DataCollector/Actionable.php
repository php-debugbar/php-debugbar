<?php

declare(strict_types=1);

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
