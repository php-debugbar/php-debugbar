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
    protected $name;

    protected $data;

    /**
     * @param array  $data
     * @param string $name
     */
    public function __construct(array $data = [], $name = 'config')
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Sets the data
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function collect()
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
    }

    /**
     * @return array
     */
    public function getWidgets()
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
