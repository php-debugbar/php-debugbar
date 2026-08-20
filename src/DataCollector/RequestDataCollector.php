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
            $data[$name] = $this->getDataFormatter()->formatVar($global);
        }

        return [
            'data' => $data,
            'tooltip' => null,
            'badge' => null,
            'summary' => $this->buildSummary($requestUri ?? null),
        ];
    }

    /**
     * Builds the summary for this request.
     *
     * Summaries are meant to be copied out of the browser (into an issue, a chat or an
     * LLM), so only parameter *names* are listed here; the values stay in the panel.
     * The URI is the one exception, and its query string is masked.
     *
     * @return array<string, string|int>
     */
    protected function buildSummary(?string $requestUri): array
    {
        $status = http_response_code();

        return array_filter([
            'method' => $_SERVER['REQUEST_METHOD'] ?? (php_sapi_name() === 'cli' ? 'CLI' : null),
            'uri' => $requestUri !== null ? $this->hideMaskedUri($requestUri) : null,
            'status' => is_int($status) ? $status : null,
            'get' => $this->summarizeKeys($_GET),
            'post' => $this->summarizeKeys($_POST),
            'cookie' => $this->summarizeKeys($_COOKIE),
            'session' => $this->summarizeKeys($_SESSION ?? []),
        ], fn($value) => $value !== null);
    }

    /**
     * Lists the keys of a superglobal, capped so a large session doesn't flood the summary.
     */
    protected function summarizeKeys(array $values, int $max = 10): ?string
    {
        if (!$values) {
            return null;
        }

        $keys = array_map('strval', array_keys($values));
        $extra = count($keys) - $max;

        return $extra > 0
            ? implode(', ', array_slice($keys, 0, $max)) . " (+{$extra} more)"
            : implode(', ', $keys);
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
        $widget = match (true) {
            $this->isJsonVarDumperUsed() => "PhpDebugBar.Widgets.JsonVariableListWidget",
            $this->isHtmlVarDumperUsed() => "PhpDebugBar.Widgets.HtmlVariableListWidget",
            default => "PhpDebugBar.Widgets.VariableListWidget",
        };

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
            'request:summary' => [
                "map" => "request.summary",
            ]
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
