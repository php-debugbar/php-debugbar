<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

trait HasTimeDataCollector
{
    protected ?TimeDataCollector $timeDataCollector = null;

    public function withTimeDataCollector(TimeDataCollector $timeDataCollector): static
    {
        $this->setTimeDataCollector($timeDataCollector);
        return $this;
    }

    public function setTimeDataCollector(TimeDataCollector $timeDataCollector): void
    {
        $this->timeDataCollector = $timeDataCollector;
    }

    public function hasTimeDataCollector(): bool
    {
        return $this->timeDataCollector !== null;
    }

    public function getTimeDataCollector(): ?TimeDataCollector
    {
        return $this->timeDataCollector;
    }

    public function addTimeMeasure(string $label, ?float $start = null, ?float $end = null, array $params = [], ?string $group = null): void
    {
        $collector = $this->getTimeDataCollector();
        $name = $this->getName();
        if ($collector === null) {
            throw new \RuntimeException('TimeDataCollector is not set');
        }

        $collector->addMeasure($label, $start, $end, $params, $name, $group ?? $name);
    }
}
