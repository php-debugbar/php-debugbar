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

namespace DebugBar;

use DebugBar\DataCollector\Renderable;

/**
 * Renders the summaries a dataset carries as plain text.
 *
 * Collectors opt in by returning a `summary` key from `collect()` and declaring a
 * `<control>:summary` widget for it. This formatter turns those into something you can
 * paste into an issue, a chat, or a prompt, which is also what `OpenHandler` serves for
 * the `summary` operation.
 */
class SummaryFormatter
{
    /** Safety net so a misbehaving collector can't produce an unpastable wall of text. */
    public const MAX_LENGTH = 20000;

    protected DebugBar $debugBar;

    public function __construct(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    /**
     * Formats a dataset (as returned by DebugBar::getData() or a storage backend).
     *
     * @param array<string, mixed> $data
     */
    public function format(array $data, int $maxLength = self::MAX_LENGTH): string
    {
        $meta = is_array($data['__meta'] ?? null) ? $data['__meta'] : [];

        $blocks = ['# PHP DebugBar summary'];

        // The raw URI in __meta is unmasked, so it is deliberately not repeated here;
        // the request collector contributes a masked one. The client IP is left out for
        // the same reason: summaries are meant to be pasted into issues, chats and prompts.
        $header = array_filter([
            'id' => $meta['id'] ?? null,
            'datetime' => $meta['datetime'] ?? null,
            'method' => $meta['method'] ?? null,
        ], fn($value) => $value !== null && $value !== '');

        if ($header) {
            $blocks[] = self::formatValue($header);
        }

        foreach ($this->getSections($data) as $title => $summary) {
            $body = self::formatValue($summary);
            if (trim($body) === '') {
                continue;
            }
            $blocks[] = "## {$title}\n{$body}";
        }

        $text = implode("\n\n", $blocks);

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . "\n\n(summary truncated)";
        }

        return $text;
    }

    /**
     * Returns the summaries in a dataset, keyed by the title of the control owning them.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function getSections(array $data): array
    {
        $sections = [];
        $seen = [];

        foreach ($this->getSummaryMaps() as $title => $map) {
            $summary = $this->getDictValue($data, $map);
            if ($summary === null || $summary === [] || $summary === '') {
                continue;
            }
            $sections[$title] = $summary;
            $seen[$map] = true;
        }

        // Datasets opened from storage may predate the current collector set, or come from
        // an app that registers collectors this process doesn't know. Pick those up too.
        foreach ($data as $name => $collected) {
            $map = "{$name}.summary";
            if ($name === '__meta' || !is_array($collected) || isset($seen[$map])) {
                continue;
            }
            $summary = $collected['summary'] ?? null;
            if ($summary === null || $summary === [] || $summary === '') {
                continue;
            }
            $sections[self::titleize((string) $name)] = $summary;
        }

        return $sections;
    }

    /**
     * Maps every declared `<control>:summary` widget to its dotted data path.
     *
     * @return array<string, string> title => map
     */
    protected function getSummaryMaps(): array
    {
        $widgets = [];
        foreach ($this->debugBar->getCollectors() as $collector) {
            if ($collector instanceof Renderable) {
                $widgets = array_merge($widgets, $collector->getWidgets());
            }
        }

        $maps = [];
        foreach ($widgets as $name => $options) {
            if (!str_ends_with((string) $name, ':summary') || !isset($options['map'])) {
                continue;
            }

            $control = substr((string) $name, 0, -strlen(':summary'));
            $title = $widgets[$control]['title'] ?? self::titleize($control);
            $maps[$title] = (string) $options['map'];
        }

        return $maps;
    }

    /**
     * Formats a summary value as indented plain text.
     *
     * Lists become `- item` lines and maps become `key = value` lines, matching the
     * rendering in the javascript bar so both produce the same text.
     */
    public static function formatValue(mixed $value, string $indent = ''): string
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $indent . self::formatScalar($value);
        }

        $lines = [];
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $nested = is_array($item) || $item instanceof \stdClass;

            if ($isList) {
                if (!$nested) {
                    $lines[] = $indent . '- ' . self::formatScalar($item);
                    continue;
                }
                // Hoist the first line of the nested block onto the bullet, YAML style.
                $block = self::formatValue($item, $indent . '  ');
                if (trim($block) === '') {
                    continue;
                }
                $lines[] = substr_replace($block, '- ', strlen($indent), 2);
                continue;
            }

            if ($nested) {
                $lines[] = $indent . $key . ':';
                $lines[] = self::formatValue($item, $indent . '  ');
                continue;
            }

            $lines[] = $indent . $key . ' = ' . self::formatScalar($item);
        }

        return implode("\n", $lines);
    }

    protected static function formatScalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value) ?: '',
        };
    }

    protected static function titleize(string $name): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $name));
    }

    /**
     * Resolves a dotted path within a dataset.
     *
     * @param array<string, mixed> $data
     */
    protected function getDictValue(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', $path) as $part) {
            if (is_object($value)) {
                $value = (array) $value;
            }
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
