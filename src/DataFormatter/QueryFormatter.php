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
        return trim(preg_replace("/\s*\n\s*/", "\n", $sql));
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

        if (!$short && isset($source->namespace) && $source->namespace) {
            $parts['namespace'] = $source->namespace . '::';
        }

        if (isset($source->name) && $source->name) {
            $parts['name'] = $short ? basename($source->name) : $source->name;
        } elseif (isset($source->file) && $source->file) {
            $parts['name'] = basename($source->file);
        } else {
            return '';
        }

        $parts['line'] = ':' . ($source->line ?? 1);

        return implode($parts);
    }

    /**
     * Returns the SQL string with any parameters used embedded
     *
     */
    public function formatSqlWithBindings(string $sql, array $bindings, ?\PDO $pdo = null): string
    {
        foreach ($this->checkBindings($bindings) as $key => $binding) {

            if (is_string($key) && str_starts_with($key, ':')) {
                $key = substr($key, 1);
            }

            if ($binding === null) {
                $binding = 'NULL';
            } elseif (! is_int($binding) && ! is_float($binding)) {
                $binding = $this->quoteBinding($binding, $pdo);
            }

            // This regex matches placeholders only, not the question marks,
            // nested in quotes, while we iterate through the bindings
            // and substitute placeholders by suitable values.
            $regex = is_numeric($key)
                ? "/(?<!\?)\?(?=(?:[^'\\\\']*'[^'\\\\']*')*[^'\\\\']*$)(?!\?)/"
                : "/:{$key}(?![A-Za-z0-9_])(?=(?:[^'\\\\']*'[^'\\\\']*')*[^'\\\\']*$)/";
            $sql = preg_replace($regex, addcslashes((string) $binding, '$'), $sql, is_numeric($key) ? 1 : -1);
        }

        return $sql;
    }

    protected function quoteBinding(string $binding, ?\PDO $pdo = null): string
    {
        try {
            if ($pdo instanceof \PDO) {
                return $pdo->quote($binding);
            }
        } catch (\PDOException $e) {

        }

        $charMap = [
            "\\"   => "\\\\",
            "\x00" => "\\0",
            "\n"   => "\\n",
            "\r"   => "\\r",
            "'"    => "\\'",
            '"'    => '\\"',
            "\x1a" => "\\Z",
        ];

        return "'" . strtr($binding, $charMap) . "'";
    }

    protected function emulateQuote(string $value): string
    {
        $search = ["\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a"];
        $replace = ["\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . str_replace($search, $replace, $value) . "'";
    }
}
