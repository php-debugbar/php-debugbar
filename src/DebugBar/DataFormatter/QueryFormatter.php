<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

#[\AllowDynamicProperties]
class QueryFormatter extends DataFormatter
{
    /**
     * Removes extra spaces at the beginning and end of the SQL query and its lines.
     *
     *
     */
    public function formatSql(string $sql): string
    {
        $sql = preg_replace("/\?(?=(?:[^'\\\']*'[^'\\']*')*[^'\\\']*$)(?:\?)/", '?', $sql);
        $sql = trim(preg_replace("/\s*\n\s*/", "\n", $sql));

        return $sql;
    }

    /**
     * Check bindings for illegal (non UTF-8) strings, like Binary data.
     *
     */
    public function checkBindings(array $bindings): array
    {
        foreach ($bindings as &$binding) {
            if (is_string($binding) && !mb_check_encoding($binding, 'UTF-8')) {
                $binding = '[BINARY DATA]';
            }

            if (is_array($binding)) {
                $binding = $this->checkBindings($binding);
                $binding = '[' . implode(',', $binding) . ']';
            }

            if (is_object($binding)) {
                $binding =  json_encode($binding);
            }
        }

        return $bindings;
    }

    /**
     * Format a source object.
     *
     * @param object|null $source If the backtrace is disabled, the $source will be null.
     *
     */
    public function formatSource(?object $source, bool $short = false): string
    {
        if (! is_object($source)) {
            return '';
        }

        $parts = [];

        if (!$short && $source->namespace) {
            $parts['namespace'] = $source->namespace . '::';
        }

        $parts['name'] = $short ? basename($source->name) : $source->name;
        $parts['line'] = ':' . $source->line;

        return implode($parts);
    }
}
