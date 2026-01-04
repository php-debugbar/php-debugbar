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
    protected bool $showUriIndicator = true;

    public function collect(): array
    {
        $globals = [
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            '$_COOKIE' => $_COOKIE,
            '$_SESSION' => $_SESSION ?? [],
        ];

        $data = [];

        if ($requestUri = $_SERVER['REQUEST_URI'] ?? null) {
            $data['uri'] = parse_url($requestUri, PHP_URL_PATH);
        }

        foreach ($globals as $name => $global) {

            foreach ($global as $key => $value) {
                if ($this->isMaskedKey($key)) {
                    $global[$key] = '***';
                }
            }

            if ($this->isHtmlVarDumperUsed()) {
                $data[$name] = $this->getVarDumper()->renderVar($global);
            } else {
                $data[$name] = $this->getDataFormatter()->formatVar($global);
            }
        }

        return [
            'data' => $data,
            'tooltip' => null,
            'badge' => null,
        ];
    }

    public function setShowUriIndicator(bool $showUriIndicator = true): void
    {
        $this->showUriIndicator = $showUriIndicator;
    }

    /**
     * Hide a sensitive value within one of the superglobal arrays.
     *
     * @deprecated use addMaskedKeys($keys)
     */
    public function hideSuperglobalKeys(string $superGlobalName, string|array $keys): void
    {
        $this->addMaskedKeys($keys);
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

        $widgets = [
            "request" => [
                "icon" => "arrows-left-right",
                "widget" => $widget,
                "map" => "request.data",
                "default" => "{}",
            ],
            'request:badge' => [
                "map" => "request.badge",
                "default" => "null",
            ],
        ];

        if ($this->showUriIndicator) {
            $widgets['request_uri'] = [
                "icon" => "share-3",
                "map" => "request.data.uri",
                "link" => "request",
                "default" => "",
            ];
            $widgets['request_uri:tooltip'] = [
                "map" => "request.tooltip",
                "default" => "{}",
            ];
        }

        return $widgets;
    }
}
