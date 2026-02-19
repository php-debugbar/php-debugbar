<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Storage\RedisStorage;
use DebugBar\Tests\DebugBarTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class RedisStorageTest extends DebugBarTestCase
{
    /** @var MockObject&\Redis */
    private MockObject $redis;

    private RedisStorage $s;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Skipping on Windows');
        }
        /** @var MockObject&\Redis $redis */
        $redis = $this->createMock(\Redis::class);
        $this->redis = $redis;

        $this->s = new RedisStorage($this->redis);
        $this->data = [
            '__meta' => ['id' => 'foo', 'utime' => microtime(true)],
            'data'   => 'test',
        ];
    }

    public function testSave(): void
    {
        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        // In pipeline mode, queued commands return $this (Redis instance) for chaining
        $this->redis->expects($this->once())
            ->method('hMSet')
            ->with(
                'phpdebugbar:entry:foo',
                $this->callback(function (array $fields): bool {
                    return isset($fields['meta'], $fields['data'])
                        && is_string($fields['meta'])
                        && is_string($fields['data']);
                })
            )
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('expire')
            ->with('phpdebugbar:entry:foo', $this->greaterThan(0))
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('zAdd')
            ->with('phpdebugbar:idx:utime', $this->isType('float'), 'foo')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([true, true, true]);

        $this->s->save('foo', $this->data);
    }

    public function testSaveWithTtlDisabledDoesNotExpire(): void
    {
        $s = new RedisStorage($this->redis, 'phpdebugbar');
        $s->setAutoPrune(false);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('hMSet')
            ->willReturn($this->redis);

        // ttlSeconds = 0 => expire() must not be called
        $this->redis->expects($this->never())
            ->method('expire');

        $this->redis->expects($this->once())
            ->method('zAdd')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([true, true]);

        $s->save('foo', $this->data);
    }

    public function testGet(): void
    {
        $meta = ['id' => 'foo', 'utime' => microtime(true)];
        $payload = ['data' => 'test'];

        $this->redis->expects($this->once())
            ->method('hMGet')
            ->with('phpdebugbar:entry:foo', ['meta', 'data'])
            ->willReturn([
                'meta' => json_encode($meta),
                'data' => json_encode($payload),
            ]);

        $result = $this->s->get('foo');

        $this->assertArrayHasKey('__meta', $result);
        $this->assertEquals('test', $result['data']);
        $this->assertEquals($meta, $result['__meta']);
    }

    public function testFind(): void
    {
        $meta = ['id' => 'foo', 'utime' => microtime(true)];

        // find() may call zRevRange multiple times; return ids first, then empty to stop.
        $this->redis->expects($this->exactly(2))
            ->method('zRevRange')
            ->willReturnOnConsecutiveCalls(['foo'], []);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        // In pipeline mode, hGet returns Redis instance for chaining
        $this->redis->expects($this->once())
            ->method('hGet')
            ->with('phpdebugbar:entry:foo', 'meta')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([json_encode($meta)]);

        // No stale ids encountered => no zRem
        $this->redis->expects($this->never())
            ->method('zRem');

        $results = $this->s->find();

        $this->assertCount(1, $results);
        $this->assertEquals($meta, $results[0]);
    }

    public function testClear(): void
    {
        $this->redis->expects($this->once())
            ->method('del')
            ->with('phpdebugbar:idx:utime')
            ->willReturn(1);

        // Simulate SCAN ending immediately with no keys.
        $this->redis->expects($this->once())
            ->method('scan')
            ->willReturnCallback(function (&$it, $pattern, $count): array {
                $it = 0;
                return [];
            });

        // No keys => no pipeline delete
        $this->redis->expects($this->never())
            ->method('multi');

        $this->s->clear();
    }

    public function testPrune(): void
    {
        $this->redis->expects($this->once())
            ->method('zRangeByScore')
            ->with('phpdebugbar:idx:utime', '-inf', $this->isType('string'))
            ->willReturn(['old', 'newerButStillOld']);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        // In pipeline chaining, del/zRem return Redis instance
        $this->redis->expects($this->once())
            ->method('del')
            ->with('phpdebugbar:entry:old', 'phpdebugbar:entry:newerButStillOld')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('zRem')
            ->with('phpdebugbar:idx:utime', 'old', 'newerButStillOld')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([true, true]);

        $this->s->prune(1);
    }

    public function testGarbageCollectionWithDefaultLifetime(): void
    {
        $this->redis->expects($this->once())
            ->method('zRangeByScore')
            ->with('phpdebugbar:idx:utime', '-inf', $this->isType('string'))
            ->willReturn(['veryold']);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('del')
            ->with('phpdebugbar:entry:veryold')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('zRem')
            ->with('phpdebugbar:idx:utime', 'veryold')
            ->willReturn($this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([true, true]);

        $this->s->prune();
    }

    public function testGarbageCollectionNoOldEntries(): void
    {
        $this->redis->expects($this->once())
            ->method('zRangeByScore')
            ->with('phpdebugbar:idx:utime', '-inf', $this->isType('string'))
            ->willReturn([]);

        $this->redis->expects($this->never())
            ->method('multi');

        $this->redis->expects($this->never())
            ->method('del');

        $this->redis->expects($this->never())
            ->method('zRem');

        $this->s->prune(1);
    }

    public function testFindWithUtimeFilter(): void
    {
        $time1 = microtime(true) - 300; // 5 minutes ago
        $time2 = microtime(true) - 200; // 3.3 minutes ago
        $time3 = microtime(true) - 100; // 1.6 minutes ago

        $meta1 = ['id' => 'entry1', 'utime' => $time1];
        $meta2 = ['id' => 'entry2', 'utime' => $time2];
        $meta3 = ['id' => 'entry3', 'utime' => $time3];

        // find() calls zRevRange to get IDs (sorted newest first), then pipelines hGet for metadata
        // Test: Filter for entries AFTER time2 (should return only entry3)
        // With early termination optimization, zRevRange is called only once
        $this->redis->expects($this->once())
            ->method('zRevRange')
            ->willReturn(['entry3', 'entry2', 'entry1']);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        // Pipeline fetches meta for all three entries
        $this->redis->expects($this->exactly(3))
            ->method('hGet')
            ->willReturnOnConsecutiveCalls($this->redis, $this->redis, $this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([
                json_encode($meta3),
                json_encode($meta2),
                json_encode($meta1),
            ]);

        $this->redis->expects($this->never())
            ->method('zRem');

        $results = $this->s->find(['utime' => $time2]);

        $this->assertCount(1, $results);
        $this->assertEquals('entry3', $results[0]['id']);
    }

    public function testFindWithWildcardFilter(): void
    {
        $utime = microtime(true);
        $meta1 = ['id' => 'e1', 'utime' => $utime, 'uri' => '/api/users', 'ip' => '127.0.0.1'];
        $meta2 = ['id' => 'e2', 'utime' => $utime, 'uri' => '/api/posts', 'ip' => '127.0.0.1'];
        $meta3 = ['id' => 'e3', 'utime' => $utime, 'uri' => '/home', 'ip' => '10.0.0.1'];

        $this->redis->expects($this->exactly(2))
            ->method('zRevRange')
            ->willReturnOnConsecutiveCalls(['e1', 'e2', 'e3'], []);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        $this->redis->expects($this->exactly(3))
            ->method('hGet')
            ->willReturnOnConsecutiveCalls($this->redis, $this->redis, $this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([
                json_encode($meta1),
                json_encode($meta2),
                json_encode($meta3),
            ]);

        $this->redis->expects($this->never())
            ->method('zRem');

        // Star (*) wildcard matches multiple characters
        $results = $this->s->find(['uri' => '/api/*']);

        $this->assertCount(2, $results);
        $ids = array_column($results, 'id');
        $this->assertContains('e1', $ids);
        $this->assertContains('e2', $ids);
    }

    public function testFindWithQuestionMarkWildcardFilter(): void
    {
        $utime = microtime(true);
        $meta1 = ['id' => 'e1', 'utime' => $utime, 'uri' => '/api/users', 'ip' => '127.0.0.1'];
        $meta2 = ['id' => 'e2', 'utime' => $utime, 'uri' => '/api/posts', 'ip' => '127.0.0.1'];
        $meta3 = ['id' => 'e3', 'utime' => $utime, 'uri' => '/home', 'ip' => '10.0.0.1'];

        $this->redis->expects($this->exactly(2))
            ->method('zRevRange')
            ->willReturnOnConsecutiveCalls(['e1', 'e2', 'e3'], []);

        $this->redis->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn($this->redis);

        $this->redis->expects($this->exactly(3))
            ->method('hGet')
            ->willReturnOnConsecutiveCalls($this->redis, $this->redis, $this->redis);

        $this->redis->expects($this->once())
            ->method('exec')
            ->willReturn([
                json_encode($meta1),
                json_encode($meta2),
                json_encode($meta3),
            ]);

        $this->redis->expects($this->never())
            ->method('zRem');

        // Question mark (?) wildcard matches exactly one character
        $results = $this->s->find(['uri' => '/api/user?']);

        $this->assertCount(1, $results);
        $this->assertEquals('e1', $results[0]['id']);
    }
}
