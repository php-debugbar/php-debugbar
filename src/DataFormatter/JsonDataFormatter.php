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

        if (is_array($data)) {
            $maxDepth = $deep ? $this->maxDepth : 1;
            $result = $this->checkPlainArray($data, $maxDepth);

            // All plain within limits → return as-is
            if ($result === true) {
                return $data;
            }

            // Plain values only, but exceeded limits → use formatArray (no VarCloner needed)
            if ($result === null) {
                return $this->formatArray($data, $deep);
            }

            // Complex values detected → fall through to VarCloner
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
     * Check if an array contains only plain values (scalars/strings/nested arrays).
     *
     * Returns:
     *  - true:  all plain, within max_items and max_depth limits → return as-is
     *  - null:  all plain, but limits exceeded → use formatArray (avoids VarCloner)
     *  - false: contains objects/resources → must use VarCloner
     */
    private function checkPlainArray(array $data, int $maxDepth, ?int &$budget = null, int $depth = 0): bool|null
    {
        $budget ??= $this->maxItems;
        $limitExceeded = false;

        foreach ($data as $v) {
            if (--$budget < 0) {
                $limitExceeded = true;
                break;
            }
            if (is_array($v)) {
                if ($depth >= $maxDepth) {
                    $limitExceeded = true;
                    break;
                }
                $inner = $this->checkPlainArray($v, $maxDepth, $budget, $depth + 1);
                if ($inner === false) {
                    return false; // complex value found deep inside
                }
                if ($inner === null) {
                    $limitExceeded = true;
                }
            } elseif (!is_scalar($v) && $v !== null) {
                return false; // object/resource/non-scalar
            }
        }

        return $limitExceeded ? null : true;
    }

    /**
     * Format an array as plain JSON, recursing into nested arrays and
     * formatting objects/complex values via the dumper inline.
     * Adds '_cut' key if items exceed max_items.
     */
    private function formatArray(array $data, bool $deep): array
    {
        $result = [];
        $count = 0;

        foreach ($data as $k => $v) {
            if ($count >= $this->maxItems) {
                $result['_cut'] = count($data) - $count;
                break;
            }
            if (!$deep && is_array($v)) {
                // Shallow mode: show nested arrays as cut summary
                $n = count($v);
                $result[$k] = $n > 0 ? ['_cut' => $n] : [];
            } else {
                $result[$k] = $this->formatValue($v, $deep);
            }
            $count++;
        }

        return $result;
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
