<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

/**
 * DataCollector Interface
 */
interface Resettable
{
    /**
     * Reset the collector to the original state
     *
     */
    public function reset(): void;
}
