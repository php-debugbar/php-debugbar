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

namespace DebugBar\DataFormatter;

use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class DataFormatter implements DataFormatterInterface
{
    protected static array $defaultClonerOptions = [
        'max_string' => 10_000,
        'max_items' => 1000,
    ];

    protected ?array $clonerOptions = null;

    protected ?VarCloner $cloner = null;

    protected ?DataDumperInterface $dumper = null;

    public function formatVar(mixed $data, bool $deep = true): string
    {
        if ($deep) {
            // Set sensible default max depth for deep dumps if not set
            $maxDepth = $this->clonerOptions['max_depth'] ?? (is_object($data) ? 2 : 4);
        } else {
            $maxDepth = min($this->clonerOptions['max_depth'] ?? 1, is_object($data) ? 0 : 1);
        }

        $cloner = $this->getCloner();
        $data = $cloner->cloneVar($data)->withMaxDepth($maxDepth);

        return trim($this->dumpClonedVar($data));
    }

    protected function dumpClonedVar(Data $data): string
    {
        $dumper = $this->getDumper();
        if ($dumper instanceof CliDumper) {
            return $dumper->dump($data, true);
        }
        return $dumper->dump($data);

    }

    public function formatDuration(float|int $seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 0.1) {
            return round($seconds * 1000, 2) . 'ms';
        } elseif ($seconds < 1) {
            return round($seconds * 1000) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

    public function formatBytes(float|int|string|null $size, int $precision = 2): string
    {
        $size = (int) $size;
        if ($size === 0) {
            return "0B";
        }

        $sign = $size < 0 ? '-' : '';
        $size = abs($size);

        $base = log($size) / log(1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return $sign . round(pow(1024, $base - floor($base)), $precision) . $suffixes[(int) floor($base)];
    }

    public function formatClassName(object $object): string
    {
        $class = \get_class($object);

        if (false === ($pos = \strpos($class, "@anonymous\0"))) {
            return $class;
        }

        if (false === ($parent = \get_parent_class($class))) {
            return \substr($class, 0, $pos + 10);
        }

        return $parent . '@anonymous';
    }

    /**
     * Gets the array of non-default VarCloner configuration options.
     *
     */
    public function getClonerOptions(): array
    {
        if ($this->clonerOptions === null) {
            $this->clonerOptions = static::$defaultClonerOptions;
        }
        return $this->clonerOptions;
    }

    /**
     * Merges an array of non-default VarCloner configuration options with the existing non-default
     * options.
     *
     * Configuration options are:
     *  - casters: a map of VarDumper Caster objects to use instead of the default casters.
     *  - additional_casters: a map of VarDumper Caster objects to use in addition to the default
     *    casters.
     *  - max_items: maximum number of items to clone beyond the minimum depth.
     *  - max_string: maximum string size
     *  - min_depth: minimum tree depth to clone before counting items against the max_items limit.
     *
     */
    public function mergeClonerOptions(array $options): void
    {
        $this->clonerOptions = $options + $this->getClonerOptions();
        $this->cloner = null;
    }

    /**
     * Resets the array of non-default VarCloner configuration options without retaining any of the
     * existing non-default options.
     *
     * Configuration options are:
     *  - casters: a map of VarDumper Caster objects to use instead of the default casters.
     *  - additional_casters: a map of VarDumper Caster objects to use in addition to the default
     *    casters.
     *  - max_items: maximum number of items to clone beyond the minimum depth.
     *  - max_string: maximum string size
     *  - min_depth: minimum tree depth to clone before counting items against the max_items limit.
     *
     */
    public function resetClonerOptions(?array $options = null): void
    {
        $this->clonerOptions = ($options ?: []) + static::$defaultClonerOptions;
        $this->cloner = null;
    }

    /**
     * Gets the VarCloner instance with configuration options set.
     *
     */
    protected function getCloner(): VarCloner
    {
        if (!$this->cloner) {
            $clonerOptions = $this->getClonerOptions();
            if (isset($clonerOptions['casters'])) {
                $this->cloner = new VarCloner($clonerOptions['casters']);
            } else {
                $this->cloner = new VarCloner();
            }
            if (isset($clonerOptions['additional_casters'])) {
                $this->cloner->addCasters($clonerOptions['additional_casters']);
            }
            if (isset($clonerOptions['max_items'])) {
                $this->cloner->setMaxItems($clonerOptions['max_items']);
            }
            if (isset($clonerOptions['max_string'])) {
                $this->cloner->setMaxString($clonerOptions['max_string']);
            }
            if (isset($clonerOptions['min_depth'])) {
                $this->cloner->setMinDepth($clonerOptions['min_depth']);
            }
        }
        return $this->cloner;
    }

    protected function getDumper(): DataDumperInterface
    {
        if (!$this->dumper) {
            $this->dumper = new CliDumper();
        }

        return $this->dumper;
    }
}
