<?php

namespace DebugBar\Tests\Storage;

use DebugBar\Storage\StorageInterface;

class MockStorage implements StorageInterface
{
    public $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function save($id, $data)
    {
        $this->data[$id] = $data;
    }

    public function get($id)
    {
        return $this->data[$id];
    }

    public function find(array $filters = [], $max = 20, $offset = 0)
    {
        return array_slice($this->data, $offset, $max);
    }

    public function clear()
    {
        $this->data = [];
    }
}
