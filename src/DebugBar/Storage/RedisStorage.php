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

namespace DebugBar\Storage;

/**
 * Stores collected data into Redis
 */
class RedisStorage implements StorageInterface
{
    protected \Predis\Client|\Redis|\RedisCluster|null $redis = null;

    /** @var string */
    protected $hash;

    /**
     * @param \Predis\Client|\Redis $redis Redis Client
     */
    public function __construct(\Predis\Client|\Redis|\RedisCluster $redis, string $hash = 'phpdebugbar')
    {
        $this->redis = $redis;
        $this->hash = $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $id, array $data): void
    {
        $this->redis->hSet("$this->hash:meta", $id, serialize($data['__meta']));
        unset($data['__meta']);
        $this->redis->hSet("$this->hash:data", $id, serialize($data));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): array
    {
        return array_merge(
            unserialize($this->redis->hGet("$this->hash:data", $id)) ?: [],
            ['__meta' => unserialize($this->redis->hGet("$this->hash:meta", $id))],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        $results = [];
        $isPhpRedis = get_class($this->redis) === 'Redis' || get_class($this->redis) === 'RedisCluster';
        $cursor = match (true) {
            $isPhpRedis && version_compare(phpversion('redis'), '6.1.0', '>=') => null,
            default => '0',
        };

        do {
            if ($isPhpRedis) {
                $data = $this->redis->hScan("$this->hash:meta", $cursor);
            } else {
                [$cursor, $data] = $this->redis->hScan("$this->hash:meta", $cursor);
            }

            foreach ($data as $meta) {
                if ($meta = unserialize($meta)) {
                    if ($this->filter($meta, $filters)) {
                        $results[] = $meta;
                    }
                }
            }
        } while ($cursor);

        usort($results, static function ($a, $b) {
            return $b['utime'] <=> $a['utime'];
        });

        return array_slice($results, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     */
    protected function filter(array $meta, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($meta[$key]) || fnmatch($value, $meta[$key]) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->redis->del("$this->hash:data");
        $this->redis->del("$this->hash:meta");
    }
}
