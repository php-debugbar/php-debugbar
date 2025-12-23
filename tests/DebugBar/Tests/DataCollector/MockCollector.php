<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class MockCollector extends DataCollector implements Renderable
{
    protected $data;
    protected $name;
    protected $widgets;

    public function __construct($data = [], $name = 'mock', $widgets = [])
    {
        $this->data = $data;
        $this->name = $name;
        $this->widgets = $widgets;
    }

    public function collect()
    {
        return $this->data;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getWidgets()
    {
        return $this->widgets;
    }
}
