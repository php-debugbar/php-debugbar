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
            $result = $dumper->dumpAsArray($this->cloneVar($data, $deep));
            $result['_sd'] = $this->getDumperOptions()['expanded_depth'] ?? 1;
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
        $dumper = new DebugBarHtmlDumper();
        $dumper->resetDumpHeader();

        return [
            'inline_head' => [
                'html_var_dumper' => $dumper->getDumpHeaderByDebugBar(),
            ],
            'js' => 'vardumper.js',
        ];
    }
}
