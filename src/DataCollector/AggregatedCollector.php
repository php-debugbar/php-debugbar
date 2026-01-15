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

namespace DebugBar\DataCollector;

use ArrayAccess;
use DebugBar\DebugBarException;

/**
 * Aggregates data from multiple collectors
 *
 * <code>
 * $aggcollector = new AggregateCollector('foobar');
 * $aggcollector->addCollector(new MessagesCollector('msg1'));
 * $aggcollector->addCollector(new MessagesCollector('msg2'));
 * $aggcollector['msg1']->addMessage('hello world');
 * </code>
 *
 * @implements ArrayAccess<string, DataCollectorInterface>
 */
class AggregatedCollector implements DataCollectorInterface, ArrayAccess, Resettable
{
    protected string $name;

    protected ?string $mergeProperty;

    protected bool|string $sort;

    /** @var array<string, DataCollectorInterface> */
    protected array $collectors = [];

    public function __construct(string $name, ?string $mergeProperty = null, bool|string $sort = false)
    {
        $this->name = $name;
        $this->mergeProperty = $mergeProperty;
        $this->sort = $sort;
    }

    public function reset(): void
    {
        foreach ($this->collectors as $collector) {
            if ($collector instanceof Resettable) {
                $collector->reset();
            }
        }
    }

    public function addCollector(DataCollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    public function getCollectors(): array
    {
        return $this->collectors;
    }

    /**
     * Merge data from one of the key/value pair of the collected data
     */
    public function setMergeProperty(string $property): void
    {
        $this->mergeProperty = $property;
    }

    public function getMergeProperty(): ?string
    {
        return $this->mergeProperty;
    }

    /**
     * Sorts the collected data
     *
     * If true, sorts using sort()
     * If it is a string, sorts the data using the value from a key/value pair of the array
     */
    public function setSort(bool|string $sort): void
    {
        $this->sort = $sort;
    }

    public function getSort(): bool|string
    {
        return $this->sort;
    }

    public function collect(): array
    {
        $aggregate = [];
        foreach ($this->collectors as $collector) {
            $data = $collector->collect();
            if ($this->mergeProperty !== null) {
                $data = $data[$this->mergeProperty];
            }
            $aggregate = array_merge($aggregate, $data);
        }

        return $this->sort($aggregate);
    }

    /**
     * Sorts the collected data
     */
    protected function sort(array $data): array
    {
        if (is_string($this->sort)) {
            $p = $this->sort;
            usort($data, function ($a, $b) use ($p): int {
                if ($a[$p] === $b[$p]) {
                    return 0;
                }
                return $a[$p] < $b[$p] ? -1 : 1;
            });
        } elseif ($this->sort === true) {
            sort($data);
        }
        return $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    // --------------------------------------------
    // ArrayAccess implementation

    /**
     * @throws DebugBarException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new DebugBarException("AggregatedCollector[] is read-only");
    }

    public function offsetGet(mixed $offset): DataCollectorInterface
    {
        return $this->collectors[$offset];
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->collectors[$offset]);
    }

    /**
     * @throws DebugBarException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new DebugBarException("AggregatedCollector[] is read-only");
    }
}
