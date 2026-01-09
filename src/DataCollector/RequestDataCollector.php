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
class RequestDataCollector extends DataCollector implements Renderable
{
    public function __construct()
    {
        $this->addMaskedKeys([
            'PHP_AUTH_PW',
            'php-auth-pw',
        ]);
    }

    protected bool $showUriIndicator = false;

    public function collect(): array
    {
        $data = [
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            '$_COOKIE' => $_COOKIE,
            '$_SESSION' => $_SESSION ?? [],
        ];

        if ($requestUri = $_SERVER['REQUEST_URI'] ?? null) {
            $data = ['uri' => $requestUri] + $data;
        }

        $data = $this->hideMaskedValues($data);

        foreach ($data as $name => $global) {
            if (is_string($global)) {
                continue;
            }
            $data[$name] = $this->getDataFormatter()->formatVar($global);
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
        $this->addMaskedKeys((array) $keys);
    }

    public function getName(): string
    {
        return 'request';
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
