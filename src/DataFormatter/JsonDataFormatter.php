<?php

declare(strict_types=1);

namespace DebugBar\DataFormatter;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataFormatter\VarDumper\DebugBarHtmlDumper;
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
        'expanded_depth' => 1,
    ];

    protected ?array $dumperOptions = null;

    public function formatVar(mixed $data, bool $deep = true): mixed
    {
        if ($this->isSimpleValue($data)) {
            return $data;
        }

        $dumper = $this->getDumper();
        if ($dumper instanceof DebugBarJsonDumper) {
            return $dumper->dumpAsArray($this->cloneVar($data, $deep));
        }

        return parent::formatVar($data, $deep);
    }

    /**
     * Check if a value can be represented as plain JSON without the Symfony dump structure.
     * Returns true for scalars, null, and arrays containing only simple values that
     * don't exceed the configured max_items/max_string limits.
     */
    private function isSimpleValue(mixed $data): bool
    {
        $count = 0;
        $maxItems = $this->getClonerOptions()['max_items'] ?? 1000;
        $maxString = $this->getClonerOptions()['max_string'] ?? 10000;

        return $this->isPlainValue($data, $count, $maxItems, $maxString);
    }

    private function isPlainValue(mixed $data, int &$count, int $maxItems, int $maxString): bool
    {
        if ($data === null || is_bool($data) || is_int($data) || is_float($data)) {
            return true;
        }

        if (is_string($data)) {
            return strlen($data) <= $maxString;
        }

        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $value) {
            if (++$count > $maxItems) {
                return false;
            }
            if (!$this->isPlainValue($value, $count, $maxItems, $maxString)) {
                return false;
            }
        }

        return true;
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
        $dumper = new DebugBarHtmlDumper();
        $dumper->resetDumpHeader();

        return [
            'inline_head' => [
                'html_var_dumper' => $dumper->getDumpHeaderByDebugBar(),
            ],
            'base_path' => __DIR__ . '/../../resources/widgets/vardumper',
            'base_url' => 'vendor/php-debugbar/php-debugbar/resources/widgets/vardumper',
            'js' => 'widget.js',
        ];
    }
}
