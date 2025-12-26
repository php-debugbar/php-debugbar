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
class ConfigCollector extends DataCollector implements Renderable, AssetProvider
{
    protected string $name;

    protected array $data;

    public function __construct(array $data = [], string $name = 'config')
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Sets the data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function collect(): array
    {
        $data = [];
        foreach ($this->data as $k => $v) {
            if ($this->isHtmlVarDumperUsed()) {
                $v = $this->getVarDumper()->renderVar($v);
            } elseif (!is_string($v)) {
                $v = $this->getDataFormatter()->formatVar($v);
            }
            $data[$k] = $v;
        }
        return $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAssets(): array
    {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
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
