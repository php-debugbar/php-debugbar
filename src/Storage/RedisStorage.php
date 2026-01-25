<?php

declare(strict_types=1);

namespace DebugBar\Storage;

class RedisStorage extends AbstractStorage
{
    protected \Predis\Client|\Redis|\RedisCluster $redis;

    protected string $hash;

    public function __construct(\Predis\Client|\Redis|\RedisCluster $redis, string $hash = 'phpdebugbar')
    {
        $this->redis = $redis;
        $this->hash = $hash;
    }

    public function getExpiration(): ?int
    {
        return $this->autoPrune ? $this->autoPruneProbability * 3600 : null;
    }

    private function entryKey(string $id): string
    {
        return "{$this->hash}:entry:{$id}";
    }

    private function idxKey(): string
    {
        return "{$this->hash}:idx:utime";
    }

    private function isPhpRedis(): bool
    {
        return $this->redis instanceof \Redis || $this->redis instanceof \RedisCluster;
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $id, array $data): void
    {
        $meta = $data['__meta'] ?? [];
        unset($data['__meta']);

        $utime = $meta['utime'] ?? microtime(true);

        $entryKey = $this->entryKey($id);
        $metaJson = json_encode($meta);
        $dataJson = json_encode($data);

        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;
            $r->multi(\Redis::PIPELINE)
                ->hMSet($entryKey, ['meta' => $metaJson, 'data' => $dataJson]);
            if ($expiration = $this->getExpiration()) {
                $r->expire($entryKey, $expiration);
            }
            $r->zAdd($this->idxKey(), (float) $utime, $id)
                ->exec();
            return;
        }

        /** @var \Predis\Client $r */
        $r = $this->redis;
        $r->hmset($entryKey, ['meta' => $metaJson, 'data' => $dataJson]);
        if ($expiration = $this->getExpiration()) {
            $r->expire($entryKey, $expiration);
        }
        $r->zadd($this->idxKey(), [$id => (float) $utime]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): array
    {
        $entryKey = $this->entryKey($id);

        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;
            $res = $r->hMGet($entryKey, ['meta', 'data']);
            $metaJson = $res['meta'] ?? null;
            $dataJson = $res['data'] ?? null;
        } else {
            /** @var \Predis\Client $r */
            $r = $this->redis;
            $res = $r->hmget($entryKey, ['meta', 'data']);
            $metaJson = $res[0] ?? null;
            $dataJson = $res[1] ?? null;
        }

        $data = is_string($dataJson) ? (json_decode($dataJson, true) ?: []) : [];
        $meta = is_string($metaJson) ? json_decode($metaJson, true) : null;

        $data['__meta'] = $meta;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        $need = $offset + $max;
        $batch = max(50, $need * 2);

        $collected = [];
        $start = 0;
        $filterUtime = isset($filters['utime']) ? (float) $filters['utime'] : null;

        while (count($collected) < $need) {
            $ids = $this->zRevRange($this->idxKey(), $start, $start + $batch - 1);
            if (!$ids) {
                break;
            }

            // Fetch meta for candidates (pipeline), filter, and lazily clean stale ids.
            $missingIds = [];
            $metaJsonById = $this->pipelineHGetField($ids, 'meta', $missingIds);

            if ($missingIds) {
                $this->zRemMembers($this->idxKey(), $missingIds);
            }

            foreach ($metaJsonById as $id => $metaJson) {
                $meta = json_decode($metaJson, true);
                if (!is_array($meta)) {
                    continue;
                }

                // Since data is sorted by utime desc, if we hit an entry that's too old, stop scanning
                if ($filterUtime !== null && isset($meta['utime']) && $meta['utime'] <= $filterUtime) {
                    break 2; // Break out of both foreach and while loops
                }

                if ($this->filter($meta, $filters)) {
                    $collected[] = $meta;
                    if (count($collected) >= $need) {
                        break;
                    }
                }
            }

            $start += $batch;
        }

        // Already sorted by utime desc thanks to ZREVRANGE
        return array_slice($collected, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     */
    protected function filter(array $meta, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            // utime is handled separately in find() for early termination optimization
            if ($key === 'utime') {
                continue;
            }
            if (!isset($meta[$key]) || fnmatch((string) $value, (string) $meta[$key]) === false) {
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
        // Always clear the index.
        $this->redis->del($this->idxKey());

        // Best-effort delete entry keys (works well on non-cluster Redis; on RedisCluster keys will expire anyway).
        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;

            // RedisCluster pattern scan across nodes is non-trivial; do not attempt here.
            if ($r instanceof \RedisCluster) {
                return;
            }

            $it = null;
            do {
                $keys = $r->scan($it, "{$this->hash}:entry:*", 1000);
                if (is_array($keys) && $keys) {
                    $r->multi(\Redis::PIPELINE)->del(...$keys)->exec();
                }
            } while ($it !== 0 && $it !== null);

            return;
        }

        /** @var \Predis\Client $r */
        $r = $this->redis;
        $cursor = '0';
        do {
            $resp = $r->scan($cursor, ['match' => "{$this->hash}:entry:*", 'count' => 1000]);
            $cursor = (string) ($resp[0] ?? '0');
            $keys = $resp[1] ?? [];
            if ($keys) {
                $r->del($keys);
            }
        } while ($cursor !== '0');
    }

    /**
     * {@inheritdoc}
     */
    public function prune(int $hours = 24): void
    {
        $cutoff = microtime(true) - ($hours * 3600);

        // Find ids older than cutoff via index.
        $oldIds = $this->zRangeByScore($this->idxKey(), '-inf', (string) $cutoff);
        if (!$oldIds) {
            return;
        }

        $entryKeys = array_map(fn($id) => $this->entryKey((string) $id), $oldIds);

        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;

            $pipe = $r->multi(\Redis::PIPELINE);
            $pipe->del(...$entryKeys);
            $pipe->zRem($this->idxKey(), ...array_map('strval', $oldIds));
            $pipe->exec();

            return;
        }

        /** @var \Predis\Client $r */
        $r = $this->redis;
        $r->pipeline(function ($pipe) use ($entryKeys, $oldIds): void {
            $pipe->del($entryKeys);
            $pipe->zrem($this->idxKey(), $oldIds);
        });
    }

    // ------------------------
    // Redis compatibility helpers
    // ------------------------

    /**
     * @return list<string>
     */
    private function zRevRange(string $key, int $start, int $stop): array
    {
        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;
            $ids = $r->zRevRange($key, $start, $stop);
        } else {
            /** @var \Predis\Client $r */
            $r = $this->redis;
            $ids = $r->zrevrange($key, $start, $stop);
        }

        if (!is_array($ids) || $ids === []) {
            return [];
        }

        return array_values(array_map('strval', $ids));
    }

    /**
     * @return list<string>
     */
    private function zRangeByScore(string $key, string $min, string $max): array
    {
        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;
            $ids = $r->zRangeByScore($key, $min, $max);
        } else {
            /** @var \Predis\Client $r */
            $r = $this->redis;
            $ids = $r->zrangebyscore($key, $min, $max);
        }

        if (!is_array($ids) || $ids === []) {
            return [];
        }

        return array_values(array_map('strval', $ids));
    }

    /**
     * @param list<string> $members
     */
    private function zRemMembers(string $key, array $members): void
    {
        if (!$members) {
            return;
        }

        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;
            $r->zRem($key, ...$members);
            return;
        }

        /** @var \Predis\Client $r */
        $r = $this->redis;
        $r->zrem($key, ...$members);
    }

    /**
     * Pipeline HGET field for a list of ids; returns map id => fieldValue (string).
     *
     * @param list<string> $ids
     * @param list<string> $missingIds (output)
     *
     * @return array<string, string>
     */
    private function pipelineHGetField(array $ids, string $field, array &$missingIds): array
    {
        $missingIds = [];

        if ($this->isPhpRedis()) {
            /** @var \Redis|\RedisCluster $r */
            $r = $this->redis;

            $pipe = $r->multi(\Redis::PIPELINE);
            foreach ($ids as $id) {
                $pipe->hGet($this->entryKey($id), $field);
            }
            $vals = $pipe->exec();

            $out = [];
            foreach ($ids as $i => $id) {
                $val = $vals[$i] ?? null;
                if (!is_string($val) || $val === '') {
                    $missingIds[] = $id; // entry expired or missing field
                    continue;
                }
                $out[$id] = $val;
            }
            return $out;
        }

        /** @var \Predis\Client $r */
        $r = $this->redis;

        $vals = $r->pipeline(function ($pipe) use ($ids, $field): void {
            foreach ($ids as $id) {
                $pipe->hget($this->entryKey($id), $field);
            }
        });

        $out = [];
        foreach ($ids as $i => $id) {
            $val = $vals[$i] ?? null;
            if (!is_string($val) || $val === '') {
                $missingIds[] = $id;
                continue;
            }
            $out[$id] = $val;
        }

        return $out;
    }
}
