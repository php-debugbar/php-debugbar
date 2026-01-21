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
 * Collects array data
 */
class ConfigCollector extends DataCollector implements Renderable, Resettable
{
    protected string $name;

    protected array $data;

    public function __construct(array $data = [], string $name = 'config')
    {
        $this->name = $name;
        $this->setData($data);
    }

    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * Sets the data
     */
    public function setData(array $data): void
    {
        $this->data = [];
        foreach ($data as $k => $v) {
            if (!is_string($v)) {
                $v = $this->getDataFormatter()->formatVar($v);

                $expanded = strpos((string) $v, 'sf-dump-expanded');
                if ($expanded !== false) {
                    $v = substr_replace($v, 'sf-dump-compact', $expanded, 16);
                }
            }
            $this->data[$k] = $v;
        }
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
        $name = $this->getName();
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return [
            "$name" => [
                "icon" => "adjustments",
                "widget" => $widget,
                "map" => "$name",
                "default" => "{}",
            ],
        ];
    }
}
