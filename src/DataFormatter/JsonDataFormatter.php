<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataFormatter\VarDumper\DebugBarJsonDumper;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * Clones and renders variables as JSON-serializable structures using the DebugBarJsonDumper.
 *
 * The JSON output is rendered client-side by the VarDumpRenderer JavaScript widget,
 * eliminating the dependency on Symfony's HtmlDumper JS/CSS.
 */
class JsonDataFormatter extends DataFormatter implements AssetProvider
{
    protected static array $defaultDumperOptions = [
        'expanded_depth' => 0,
    ];

    protected ?array $dumperOptions = null;

    /** Resolved limits — cached on first formatVar call to avoid repeated lookups */
    private ?int $maxString = null;
    private ?int $maxItems = null;
    private ?int $maxDepth = null;

    /**
     * Returns the raw value for scalars/short strings, or a dump node array for complex types.
     *
     * Simple values (null, bool, int, float, short string) pass through unchanged.
     * Complex values return a dump node array — see {@see DebugBarJsonDumper::dumpAsArray()}.
     *
     * @return null|bool|int|float|string|array
     */
    public function formatVar(mixed $data, bool $deep = true): mixed
    {
        // Resolve limits once (reset to null when cloner options change)
        if ($this->maxString === null) {
            $opts = $this->getClonerOptions();
            $this->maxString = $opts['max_string'] ?? 10000;
            $this->maxItems = $opts['max_items'] ?? 1000;
            $this->maxDepth = $opts['max_depth'] ?? 5;
        }

        return $this->formatValue($data, $deep);
    }

    private function formatValue(mixed $data, bool $deep): mixed
    {
        if (is_string($data)) {
            if (strlen($data) <= $this->maxString) {
                return $data;
            }
            return substr($data, 0, $this->maxString) . '[..' . (strlen($data) - $this->maxString) . ']';
        }

        if ($data === null || is_bool($data) || is_int($data) || is_float($data)) {
            return $data;
        }

        if (is_array($data) && count($data) <= $this->maxItems) {
            // Fast path: if all leaf values are scalars within limits, return as-is
            $maxDepth = $deep ? $this->maxDepth : 1;
            if ($this->isPlainArray($data, maxDepth: $maxDepth)) {
                return $data;
            }
        }

        return $this->formatComplex($data, $deep);
    }

    protected function cloneVar(mixed $data, bool $deep): Data
    {
        $isNonIterableObject = is_object($data) && !is_iterable($data);
        if ($deep) {
            // Set sensible default max depth for deep dumps if not set
            $maxDepth = $this->clonerOptions['max_depth'] ?? ($isNonIterableObject ? 3 : 5);
        } else {
            $maxDepth = min($this->clonerOptions['max_depth'] ?? 1, $isNonIterableObject ? 0 : 1);
        }

        $cloner = $this->getCloner();

        return $cloner->cloneVar($data)->withMaxDepth($maxDepth);
    }

    /**
     * Fast check: returns true if the array (recursively) contains only scalar/null values
     * within the configured max_items and max_depth limits.
     */
    private function isPlainArray(array $data, ?int &$budget = null, int $depth = 0, ?int $maxDepth = null): bool
    {
        $budget ??= $this->maxItems;
        $maxDepth ??= $this->maxDepth;
        if ($depth > $maxDepth) {
            return false;
        }
        foreach ($data as $v) {
            if (--$budget < 0) {
                return false;
            }
            if (is_array($v)) {
                if (!$this->isPlainArray($v, $budget, $depth + 1, $maxDepth)) {
                    return false;
                }
            } elseif (!is_scalar($v) && $v !== null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Format a non-array, non-scalar value (objects, resources, etc.) through the full dumper.
     */
    private function formatComplex(mixed $data, bool $deep): mixed
    {
        $dumper = $this->getDumper();
        if ($dumper instanceof DebugBarJsonDumper) {
            return $dumper->dumpAsArray($this->cloneVar($data, $deep));
        }

        return parent::formatVar($data, $deep);
    }

    protected function getDumper(): DataDumperInterface
    {
        if (!$this->dumper) {
            $this->dumper = new DebugBarJsonDumper();
        }
        return $this->dumper;
    }

    public function getDumperOptions(): array
    {
        if ($this->dumperOptions === null) {
            $this->dumperOptions = static::$defaultDumperOptions;
        }
        return $this->dumperOptions;
    }

    public function mergeClonerOptions(array $options): void
    {
        parent::mergeClonerOptions($options);
        $this->maxString = null; // reset cache
    }

    public function resetClonerOptions(?array $options = null): void
    {
        parent::resetClonerOptions($options);
        $this->maxString = null; // reset cache
    }

    public function mergeDumperOptions(array $options): void
    {
        $this->dumperOptions = $options + $this->getDumperOptions();
        $this->dumper = null;
    }

    public function resetDumperOptions(?array $options = null): void
    {
        $this->dumperOptions = ($options ?: []) + static::$defaultDumperOptions;
        $this->dumper = null;
    }

    public function getAssets(): array
    {
        return [
            'css' => 'vardumper.css',
            'js' => 'vardumper.js',
        ];
    }
}
