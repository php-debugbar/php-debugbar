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
 * Collects info about the current request
 */
class RequestDataCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var array[]
     */
    private $blacklist = [
        '_GET' => [],
        '_POST' => [],
        '_COOKIE' => [],
        '_SESSION' => [],
    ];

    /**
     * @return array
     */
    public function collect()
    {
        $vars = array_keys($this->blacklist);
        $data = array();

        foreach ($vars as $var) {
            if (! isset($GLOBALS[$var])) {
                continue;
            }

            $key = "$" . $var;
            $value = $this->masked($GLOBALS[$var], $var);

            if ($this->isHtmlVarDumperUsed()) {
                $data[$key] = $this->getVarDumper()->renderVar($value);
            } else {
                $data[$key] = $this->getDataFormatter()->formatVar($value);
            }
        }

        return $data;
    }

    /**
     * Hide a sensitive value within one of the superglobal arrays.
     *
     * @param string $superGlobalName The name of the superglobal array, e.g. '_GET'
     * @param string|array $key       The key within the superglobal
     * @return void
     */
    public function hideSuperglobalKeys($superGlobalName, $keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        if (!isset($this->blacklist[$superGlobalName])) {
            $this->blacklist[$superGlobalName] = [];
        }

        foreach ($keys as $key) {
            $this->blacklist[$superGlobalName][] = $key;
        }
    }

    /**
     * Checks all values within the given superGlobal array.
     *
     * Blacklisted values will be replaced by a equal length string containing
     * only '*' characters for string values.
     * Non-string values will be replaced with a fixed asterisk count.
     *
     * @param array|\ArrayAccess  $superGlobal     One of the superglobal arrays
     * @param string $superGlobalName The name of the superglobal array, e.g. '_GET'
     *
     * @return array $values without sensitive data
     */
    private function masked($superGlobal, $superGlobalName)
    {
        $blacklisted = $this->blacklist[$superGlobalName];

        $values = $superGlobal;

        foreach ($blacklisted as $key) {
            if (isset($superGlobal[$key])) {
                $values[$key] = str_repeat('*', is_string($superGlobal[$key]) ? strlen($superGlobal[$key]) : 3);
            }
        }

        return $values;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'request';
    }

    /**
     * @return array
     */
    public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return array(
            "request" => array(
                "icon" => "tags",
                "widget" => $widget,
                "map" => "request",
                "default" => "{}"
            )
        );
    }
}
