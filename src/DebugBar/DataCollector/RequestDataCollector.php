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
 * Collects info about the current request
 */
class RequestDataCollector extends DataCollector implements Renderable, AssetProvider
{
    /** @var array<string, array<string>> */
    private array $blacklist = [
        '_GET' => [],
        '_POST' => [
            'password',
        ],
        '_COOKIE' => [],
        '_SESSION' => [
            'PHPDEBUGBAR_STACK_DATA'
        ],
    ];

    public function collect(): array
    {
        $vars = array_keys($this->blacklist);
        $data = [];

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
     */
    public function hideSuperglobalKeys(string $superGlobalName, string|array $keys): void
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
     * @param array|\ArrayAccess<mixed, mixed> $superGlobal
     */
    private function masked(array|\ArrayAccess $superGlobal, string $superGlobalName): array
    {
        $blacklisted = $this->blacklist[$superGlobalName];

        $values = $superGlobal;
        foreach ($values as $key => $value) {
            if (in_array($key, $blacklisted) || $this->stringContains($key, ['password', 'key', 'secret'])) {
                $values[$key] = str_repeat('*', is_string($superGlobal[$key]) ? strlen($superGlobal[$key]) : 3);
            }
        }

        return $values;
    }

    private function stringContains(string $haystack, array $needles)
    {
        $haystack = strtolower($haystack);
        foreach($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public function getName(): string
    {
        return 'request';
    }

    public function getAssets(): array
    {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
    }

    public function getWidgets(): array
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return [
            "request" => [
                "icon" => "arrows-left-right",
                "widget" => $widget,
                "map" => "request",
                "default" => "{}",
            ],
        ];
    }
}
