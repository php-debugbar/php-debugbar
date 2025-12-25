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
 * Collects info about memory usage
 */
class MemoryCollector extends DataCollector implements Renderable
{
    protected bool $realUsage = false;

    protected int $memoryRealStart = 0;

    protected int $memoryStart = 0;

    protected int $peakUsage = 0;

    protected int $precision = 0;

    /**
     * Set the precision of the 'peak_usage_str' output.
     */
    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * Returns whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     */
    public function getRealUsage(): bool
    {
        return $this->realUsage;
    }

    /**
     * Sets whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     */
    public function setRealUsage(bool $realUsage): void
    {
        $this->realUsage = $realUsage;
    }

    /**
     * Reset memory baseline, to measure multiple requests in a long running process
     */
    public function resetMemoryBaseline(): void
    {
        $this->memoryStart = memory_get_usage(false);
        $this->memoryRealStart = memory_get_usage(true);
    }

    /**
     * Returns the peak memory usage
     */
    public function getPeakUsage(): int
    {
        return $this->peakUsage - ($this->realUsage ? $this->memoryRealStart : $this->memoryStart);
    }

    /**
     * Updates the peak memory usage value
     */
    public function updatePeakUsage(): void
    {
        $this->peakUsage = memory_get_peak_usage($this->realUsage);
    }

    public function collect(): array
    {
        $this->updatePeakUsage();
        return [
            'peak_usage' => $this->getPeakUsage(),
            'peak_usage_str' => $this->getDataFormatter()->formatBytes($this->getPeakUsage(), $this->precision),
        ];
    }

    public function getName(): string
    {
        return 'memory';
    }

    public function getWidgets(): array
    {
        return [
            "memory" => [
                "icon" => "server-cog",
                "tooltip" => "Memory Usage",
                "map" => "memory.peak_usage_str",
                "default" => "'0B'",
            ],
        ];
    }
}
