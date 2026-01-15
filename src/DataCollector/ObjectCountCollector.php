<?php

declare(strict_types=1);

namespace DebugBar\DataCollector;

/**
 * Collector for hit counts.
 */
class ObjectCountCollector extends DataCollector implements DataCollectorInterface, Renderable, Resettable
{
    private string $name;
    private string $icon;
    protected int $classCount = 0;
    /** @var array<string, array<string, int>> */
    protected array $classList = [];
    /** @var array<string, int> */
    protected array $classSummary = [];
    protected bool $collectSummary = false;
    /** @var array<string, string> */
    protected array $keyMap = ['value' => 'Count'];

    public function __construct(string $name = 'counter', string $icon = 'box')
    {
        $this->name = $name;
        $this->icon = $icon;
    }

    public function reset(): void
    {
        $this->classList = [];
        $this->classCount = 0;
    }

    /**
     * Allows to define an array to map internal keys to human-readable labels
     */
    public function setKeyMap(array $keyMap): void
    {
        $this->keyMap = $keyMap;
    }

    /**
     * Allows to add a summary row
     */
    public function collectCountSummary(bool $enable = true): void
    {
        $this->collectSummary = $enable;
    }

    public function countClass(string|object $class, int $count = 1, string $key = 'value'): void
    {
        if (is_object($class)) {
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

    public function collect(): array
    {
        uasort($this->classList, fn($a, $b) => array_sum($b) <=> array_sum($a));

        $collect = [
            'data' => $this->classList,
            'count' => $this->classCount,
            'key_map' => $this->keyMap,
            'is_counter' => true,
        ];

        if ($this->collectSummary) {
            $collect['badges'] = $this->classSummary;
        }

        if (! $this->getXdebugLinkTemplate()) {
            return $collect;
        }

        foreach ($this->classList as $class => $count) {
            $reflector = class_exists($class) ? new \ReflectionClass($class) : null;
            $file = $reflector?->getFileName();
            if ($file && $link = $this->getXdebugLink($file)) {
                $collect['data'][$class]['xdebug_link'] = $link;
            }
        }

        return $collect;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWidgets(): array
    {
        $name = $this->getName();

        return [
            "$name" => [
                'icon' => $this->icon,
                'widget' => 'PhpDebugBar.Widgets.TableVariableListWidget',
                'map' => "$name",
                'default' => '{}',
            ],
            "$name:badge" => [
                'map' => "$name.count",
                'default' => 0,
            ],
        ];
    }
}
