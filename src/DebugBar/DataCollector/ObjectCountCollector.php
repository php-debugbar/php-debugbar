<?php

namespace DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;

/**
 * Collector for hit counts.
 */
class ObjectCountCollector extends DataCollector implements DataCollectorInterface, Renderable
{
    /** @var string */
    private $name;
    /** @var string */
    private $icon;
    /** @var int */
    protected $classCount = 0;
    /** @var array */
    protected $classList = [];
    /** @var array */
    protected $classSummary = [];
    /** @var bool */
    protected $collectSummary = false;
    /** @var array */
    protected $keyMap = ['value' => 'Count'];

    /**
     * @param string $name
     * @param string $icon
     */
    public function __construct($name = 'counter', $icon = 'cubes')
    {
        $this->name = $name;
        $this->icon = $icon;
    }

    /**
     * Allows to define an array to map internal keys to human-readable labels
     */
    public function setKeyMap(array $keyMap)
    {
        $this->keyMap = $keyMap;
    }

    /**
     * Allows to add a summary row
     */
    public function collectCountSummary(bool $enable = true)
    {
        $this->collectSummary = $enable;
    }

    /**
     * @param string|mixed $class
     * @param int $count
     * @param string $key
     */
    public function countClass($class, $count = 1, $key = 'value') {
        if (! is_string($class)) {
            $class = get_class($class);
        }

        if (!isset($this->classList[$class])) {
            $this->classList[$class] = [];
        }

        if ($this->collectSummary) {
            $this->classSummary[$key] = ($this->classSummary[$key] ?? 0) + $count;
        }

        $this->classList[$class][$key] = ($this->classList[$class][$key] ?? 0) + $count;
        $this->classCount += $count;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        uasort($this->classList, fn($a, $b) => array_sum($b) <=> array_sum($a));

        $collect = [
            'data' => $this->classList,
            'count' => $this->classCount,
            'key_map' => $this->keyMap,
            'is_counter' => true
        ];

        if ($this->collectSummary) {
            $collect['badges'] = $this->classSummary;
        }

        if (! $this->getXdebugLinkTemplate()) {
            return $collect;
        }

        foreach ($this->classList as $class => $count) {
            $reflector = class_exists($class) ? new \ReflectionClass($class) : null;

            if ($reflector && $link = $this->getXdebugLink($reflector->getFileName())) {
                $collect['data'][$class]['xdebug_link'] = $link;
            }
        }

        return $collect;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        $name = $this->getName();

        return [
            "$name" => [
                'icon' => $this->icon,
                'widget' => 'PhpDebugBar.Widgets.TableVariableListWidget',
                'map' => "$name",
                'default' => '{}'
            ],
            "$name:badge" => [
                'map' => "$name.count",
                'default' => 0
            ]
        ];
    }
}
