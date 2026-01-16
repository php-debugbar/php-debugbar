<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\Resettable;

class MockCollector extends DataCollector implements Renderable, Resettable
{
    protected array $data;
    protected string $name;
    protected array $widgets;

    public function __construct(array $data = [], string $name = 'mock', array $widgets = [])
    {
        $this->data = $data;
        $this->name = $name;
        $this->widgets = $widgets;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function collect(): array
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function reset(): void
    {
        $this->data = [];
    }
}
