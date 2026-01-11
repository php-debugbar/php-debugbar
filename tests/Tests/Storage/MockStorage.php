<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Storage\AbstractStorage;
use DebugBar\Storage\StorageInterface;

class MockStorage extends AbstractStorage implements StorageInterface
{
    public array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function save(string $id, array $data): void
    {
        $this->data[$id] = $data;
    }

    public function get(string $id): array
    {
        return $this->data[$id];
    }

    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        return array_slice($this->data, $offset, $max);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function prune(int $hours = 24): void
    {
        $this->clear();
    }
}
