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
        foreach ($data as $id => $value) {
            $this->save($id, $value);
        }
    }

    public function save(string $id, array $data): void
    {
        $this->data[$id] = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function get(string $id): array
    {
        return json_decode($this->data[$id], true);
    }

    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        $results = [];

        $data = array_slice($this->data, $offset, $max);;
        foreach (array_keys($data) as $id) {
            $results[$id] = $this->get($id);
        }

        return $results;
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
