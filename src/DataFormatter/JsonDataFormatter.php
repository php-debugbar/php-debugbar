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
        if (is_string($data)) {
            $maxLength = $this->getClonerOptions()['max_string'] ?? 10000;
            if (strlen($data) <= $maxLength) {
                return $data;
            }
            return substr($data, 0, $maxLength) . '[truncated ' . (strlen($data) - $maxLength) . ' chars]';
        }

        if ($this->isSimpleValue($data)) {
            return $data;
        }

        if (is_array($data)) {
            return $this->formatArray($data, $deep);
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
     * Format an array as plain JSON, recursing into nested arrays and
     * formatting objects/complex values via the dumper inline.
     * Adds '_cut' key if items exceed max_items.
     */
    private function formatArray(array $data, bool $deep): array
    {
        $maxItems = $this->getClonerOptions()['max_items'] ?? 1000;
        $result = [];
        $count = 0;

        foreach ($data as $k => $v) {
            if ($count >= $maxItems) {
                $result['_cut'] = count($data) - $count;
                break;
            }
            if (!$deep && is_array($v)) {
                // Shallow mode: show nested arrays as cut summary
                $n = count($v);
                $result[$k] = $n > 0 ? ['_cut' => $n] : [];
            } else {
                $result[$k] = $this->formatVar($v, $deep);
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
            $result = $dumper->dumpAsArray($this->cloneVar($data, $deep));
            $result['_sd'] = $this->getDumperOptions()['expanded_depth'] ?? 0;
            return $result;
        }

        return parent::formatVar($data, $deep);
    }

    /**
     * Check if a value can be represented as plain JSON without the Symfony dump structure.
     * Only scalars, null, and short strings qualify — arrays always go through the dumper
     * to preserve type info (array vs object) and element counts.
     */
    private function isSimpleValue(mixed $data): bool
    {
        if ($data === null || is_bool($data) || is_int($data) || is_float($data)) {
            return true;
        }

        if (is_string($data)) {
            $maxString = $this->getClonerOptions()['max_string'] ?? 10000;
            return strlen($data) <= $maxString;
        }

        return false;
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
