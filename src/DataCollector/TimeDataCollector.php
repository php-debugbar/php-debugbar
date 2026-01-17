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

use DebugBar\DebugBarException;

/**
 * Collects info about the request duration as well as providing
 * a way to log duration of any operations
 */
class TimeDataCollector extends DataCollector implements Renderable, Resettable
{
    protected float $requestStartTime;

    protected ?float $requestEndTime = null;

    protected array $startedMeasures = [];

    protected array $measures = [];

    protected bool $memoryMeasure = false;

    public function __construct(?float $requestStartTime = null)
    {
        $this->setRequestStartTime($requestStartTime);
        static::getDefaultDataFormatter(); // initializes formatter for lineal timeline
    }

    public function reset(): void
    {
        $this->measures = [];
        $this->startedMeasures = [];
        $this->setRequestStartTime(microtime(true));
        $this->requestEndTime = null;
    }

    public function setRequestStartTime(?float $requestStartTime = null): void
    {
        if ($requestStartTime === null) {
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'];
            } else {
                $requestStartTime = microtime(true);
            }
        }
        $this->requestStartTime = (float) $requestStartTime;
    }

    /**
     * Starts memory measuring
     */
    public function showMemoryUsage(): void
    {
        $this->memoryMeasure = true;
    }

    /**
     * Starts a measure
     */
    public function startMeasure(string $name, ?string $label = null, ?string $collector = null, ?string $group = null): void
    {
        $start = microtime(true);
        $this->startedMeasures[$name] = [
            'label' => $label ?: $name,
            'start' => $start,
            'memory' => $this->memoryMeasure ? memory_get_usage(false) : null,
            'collector' => $collector,
            'group' => $group,
        ];
    }

    /**
     * Check a measure exists
     */
    public function hasStartedMeasure(string $name): bool
    {
        return isset($this->startedMeasures[$name]);
    }

    /**
     * Stops a measure
     *
     * @throws DebugBarException
     */
    public function stopMeasure(string $name, array $params = []): void
    {
        $end = microtime(true);
        if (!$this->hasStartedMeasure($name)) {
            throw new DebugBarException("Failed stopping measure '$name' because it hasn't been started");
        }
        if (! is_null($this->startedMeasures[$name]['memory'])) {
            $params['memoryUsage'] = memory_get_usage(false) - $this->startedMeasures[$name]['memory'];
        }
        $this->addMeasure(
            $this->startedMeasures[$name]['label'],
            $this->startedMeasures[$name]['start'],
            $end,
            $params,
            $this->startedMeasures[$name]['collector'],
            $this->startedMeasures[$name]['group'],
        );
        unset($this->startedMeasures[$name]);
    }

    /**
     * Adds a measure
     */
    public function addMeasure(string $label, ?float $start = null, ?float $end = null, array $params = [], ?string $collector = null, ?string $group = null): void
    {
        $start ??= microtime(true);
        $end ??= $start;

        if (isset($params['memoryUsage'])) {
            $memory = $this->memoryMeasure ? $params['memoryUsage'] : 0;
            unset($params['memoryUsage']);
        }

        $this->measures[] = [
            'label' => $label,
            'start' => $start,
            'relative_start' => $start - $this->requestStartTime,
            'end' => $end,
            'relative_end' => $end - $this->requestEndTime,
            'duration' => $end - $start,
            'duration_str' => $this->getDataFormatter()->formatDuration($end - $start),
            'memory' => $memory ?? 0,
            'memory_str' => $this->getDataFormatter()->formatBytes($memory ?? 0),
            'params' => array_map([$this->getDataFormatter(), 'formatVar'], $params),
            'collector' => $collector,
            'group' => $group,
        ];
    }

    /**
     * Utility function to measure the execution of a Closure
     */
    public function measure(string $label, \Closure $closure, ?string $collector = null, ?string $group = null): mixed
    {
        $name = spl_object_hash($closure);
        $this->startMeasure($name, $label, $collector, $group);
        $result = $closure();
        $params = is_array($result) ? $result : [];
        $this->stopMeasure($name, $params);
        return $result;
    }

    /**
     * Returns an array of all measures
     */
    public function getMeasures(): array
    {
        return $this->measures;
    }

    /**
     * Returns the request start time
     */
    public function getRequestStartTime(): float
    {
        return $this->requestStartTime;
    }

    /**
     * Returns the request end time
     */
    public function getRequestEndTime(): ?float
    {
        return $this->requestEndTime;
    }

    /**
     * Returns the duration of a request
     */
    public function getRequestDuration(): float
    {
        if ($this->requestEndTime !== null) {
            return $this->requestEndTime - $this->requestStartTime;
        }
        return microtime(true) - $this->requestStartTime;
    }

    /**
     * @throws DebugBarException
     */
    public function collect(): array
    {
        $this->requestEndTime = microtime(true);
        foreach (array_keys($this->startedMeasures) as $name) {
            $this->stopMeasure($name);
        }

        usort($this->measures, function ($a, $b): int {
            if ($a['start'] === $b['start']) {
                return 0;
            }
            return $a['start'] < $b['start'] ? -1 : 1;
        });

        return [
            'count' => count($this->measures),
            'start' => $this->requestStartTime,
            'end' => $this->requestEndTime,
            'duration' => $this->getRequestDuration(),
            'duration_str' => $this->getDataFormatter()->formatDuration($this->getRequestDuration()),
            'measures' => $this->measures,
        ];
    }

    public function getName(): string
    {
        return 'time';
    }

    public function getWidgets(): array
    {
        return [
            "time" => [
                "icon" => "clock",
                "tooltip" => "Request Duration",
                "map" => "time.duration_str",
                'link' => 'timeline',
                "default" => "'0ms'",
            ],
            "timeline" => [
                "icon" => "chart-infographic",
                "widget" => "PhpDebugBar.Widgets.TimelineWidget",
                "map" => "time",
                "default" => "{}",
            ],
        ];
    }
}
