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
    private int $maxDepth = 5;

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
            $result = $this->formatArray($data, $deep);
            if ($result !== null) {
                return $result;
            }
            // Bail: complex value found — send entire array through VarCloner
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
     * Format an array in a single pass. Each element is formatted inline:
     * scalars/strings pass through, nested arrays recurse, objects go through VarCloner.
     * Adds '_cut' when max_items is exceeded.
     */
    /**
     * Format an array in a single pass. Scalars/strings/nested arrays are handled inline.
     * If any complex value (object/resource) is encountered, bail and send the entire
     * original array through VarCloner.
     *
     * @return array|null null signals a bail — caller should use formatComplex instead
     */
    private function formatArray(array $data, bool $deep, int $depth = 0): ?array
    {
        $maxDepth = $deep ? $this->maxDepth : 1;
        $result = [];
        $count = 0;

        foreach ($data as $k => $v) {
            if ($count >= $this->maxItems) {
                $result['_cut'] = count($data) - $count;
                break;
            }
            if (is_string($v)) {
                $result[$k] = strlen($v) <= $this->maxString ? $v : substr($v, 0, $this->maxString) . '[..' . (strlen($v) - $this->maxString) . ']';
            } elseif (is_int($v) || is_float($v) || is_bool($v) || $v === null) {
                $result[$k] = $v;
            } elseif (is_array($v)) {
                if ($depth >= $maxDepth) {
                    $n = count($v);
                    $result[$k] = $n > 0 ? ['_cut' => $n] : [];
                } else {
                    $inner = $this->formatArray($v, $deep, $depth + 1);
                    if ($inner === null) {
                        return null; // bail — complex value found deeper
                    }
                    $result[$k] = $inner;
                }
            } else {
                return null; // bail — object/resource encountered
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
