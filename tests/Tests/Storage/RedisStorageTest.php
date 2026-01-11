<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\Storage\RedisStorage;
use PHPUnit\Framework\MockObject\MockObject;

class RedisStorageTest extends DebugBarTestCase
{
    private MockObject $redis;
    private RedisStorage $s;
    private array $data;

    public function setUp(): void
    {
        $this->redis = $this->createMock(\Redis::class);
        $this->s = new RedisStorage($this->redis);
        $this->data = ['__meta' => ['id' => 'foo', 'utime' => microtime(true)], 'data' => 'test'];
    }

    public function testSave(): void
    {
        $this->redis->expects($this->exactly(2))
            ->method('hSet')
            ->willReturn(1);

        $this->s->save('foo', $this->data);
    }

    public function testGet(): void
    {
        $this->redis->expects($this->exactly(2))
            ->method('hGet')
            ->willReturnOnConsecutiveCalls(
                json_encode(['data' => 'test']),
                json_encode(['id' => 'foo', 'utime' => microtime(true)])
            );

        $result = $this->s->get('foo');
        $this->assertArrayHasKey('__meta', $result);
        $this->assertEquals('test', $result['data']);
    }

    public function testFind(): void
    {
        $meta = ['id' => 'foo', 'utime' => microtime(true)];

        // Mock hScan - for Predis it returns [$cursor, $data]
        // Mock will take the Predis path since get_class won't match 'Redis'
        $this->redis->expects($this->atLeastOnce())
            ->method('hScan')
            ->willReturnCallback(function ($hash, $cursor) use ($meta) {
                if ($cursor === '0') {
                    // Return cursor '0' (stop) and data
                    return ['0', ['foo' => json_encode($meta)]];
                }
                return ['0', []];
            });

        $results = $this->s->find();
        $this->assertCount(1, $results);
        $this->assertEquals($meta, $results[0]);
    }

    public function testClear(): void
    {
        $this->redis->expects($this->exactly(2))
            ->method('del')
            ->willReturn(1);

        $this->s->clear();
    }

    public function testPrune(): void
    {
        $oldMeta = ['id' => 'old', 'utime' => microtime(true) - 7200]; // 2 hours old
        $newMeta = ['id' => 'new', 'utime' => microtime(true)]; // Fresh

        $this->redis->expects($this->atLeastOnce())
            ->method('hScan')
            ->willReturnCallback(function ($hash, $cursor) use ($oldMeta, $newMeta) {
                if ($cursor === '0') {
                    // Return cursor '0' (stop) and data
                    return ['0', [
                        'old' => json_encode($oldMeta),
                        'new' => json_encode($newMeta),
                    ]];
                }
                return ['0', []];
            });

        $this->redis->expects($this->exactly(2))
            ->method('hDel')
            ->willReturn(1);

        // Delete entries older than 1 hour
        $this->s->prune(1);
    }

    public function testGarbageCollectionWithDefaultLifetime(): void
    {
        $veryOldMeta = ['id' => 'veryold', 'utime' => microtime(true) - 172800]; // 2 days old

        $this->redis->expects($this->atLeastOnce())
            ->method('hScan')
            ->willReturnCallback(function ($hash, $cursor) use ($veryOldMeta) {
                if ($cursor === '0') {
                    // Return cursor '0' (stop) and data
                    return ['0', ['veryold' => json_encode($veryOldMeta)]];
                }
                return ['0', []];
            });

        $this->redis->expects($this->exactly(2))
            ->method('hDel')
            ->willReturn(1);

        // Use default lifetime (1 day)
        $this->s->prune();
    }

    public function testGarbageCollectionNoOldEntries(): void
    {
        $newMeta = ['id' => 'new', 'utime' => microtime(true)];

        $this->redis->expects($this->atLeastOnce())
            ->method('hScan')
            ->willReturnCallback(function ($hash, $cursor) use ($newMeta) {
                if ($cursor === '0') {
                    // Return cursor '0' (stop) and data
                    return ['0', ['new' => json_encode($newMeta)]];
                }
                return ['0', []];
            });

        $this->redis->expects($this->never())
            ->method('hDel');

        // All entries are fresh
        $deleted = $this->s->prune(1);
    }
}
