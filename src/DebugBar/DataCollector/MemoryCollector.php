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
 * Collects info about memory usage
 */
class MemoryCollector extends DataCollector implements Renderable
{
    protected $realUsage = false;

    protected $memoryRealStart = 0;

    protected $memoryStart = 0;

    protected $peakUsage = 0;

    protected $precision = 0;

    /**
     * Set the precision of the 'peak_usage_str' output.
     *
     * @param int $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * Returns whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     *
     * @return bool
     */
    public function getRealUsage()
    {
        return $this->realUsage;
    }

    /**
     * Sets whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     *
     * @param bool $realUsage
     */
    public function setRealUsage($realUsage)
    {
        $this->realUsage = $realUsage;
    }

    /**
     * Reset memory baseline, to measure multiple requests in a long running process
     *
     * @return void
     */
    public function resetMemoryBaseline()
    {
        $this->memoryStart = memory_get_usage(false);
        $this->memoryRealStart = memory_get_usage(true);
    }

    /**
     * Returns the peak memory usage
     *
     * @return integer
     */
    public function getPeakUsage()
    {
        return $this->peakUsage - ($this->realUsage ? $this->memoryRealStart : $this->memoryStart);
    }

    /**
     * Updates the peak memory usage value
     */
    public function updatePeakUsage()
    {
        $this->peakUsage = memory_get_peak_usage($this->realUsage);
    }

    /**
     * @return array
     */
    public function collect()
    {
        $this->updatePeakUsage();
        return array(
            'peak_usage' => $this->getPeakUsage(),
            'peak_usage_str' => $this->getDataFormatter()->formatBytes($this->getPeakUsage(), $this->precision)
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'memory';
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return array(
            "memory" => array(
                "icon" => "server-cog",
                "tooltip" => "Memory Usage",
                "map" => "memory.peak_usage_str",
                "default" => "'0B'"
            )
        );
    }
}
