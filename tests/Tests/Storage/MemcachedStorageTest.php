<?php

declare(strict_types=1);

namespace DebugBar\Tests\Storage;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\Storage\MemcachedStorage;
use PHPUnit\Framework\MockObject\MockObject;

class MemcachedStorageTest extends DebugBarTestCase
{
    private MockObject $memcached;
    private MemcachedStorage $s;
    private array $data;

    public function setUp(): void
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached extension not loaded');
        }

        $this->memcached = $this->createMock(\Memcached::class);
        $this->s = new MemcachedStorage($this->memcached);
        $this->data = ['__meta' => ['id' => 'foo', 'utime' => microtime(true)], 'data' => 'test'];
    }

    public function testSave(): void
    {
        $this->memcached->expects($this->once())
            ->method('set')
            ->willReturn(true);

        $this->memcached->expects($this->once())
            ->method('append')
            ->willReturn(true);

        $this->memcached->expects($this->never())
            ->method('touch');

        $this->s->save('foo', $this->data);
    }

    public function testSaveWithExpiration(): void
    {
        // @phpstan-ignore-next-line argument.type
        $s = new MemcachedStorage($this->memcached, 'phpdebugbar', 3600);

        $this->memcached->expects($this->once())
            ->method('set')
            ->willReturn(true);

        $this->memcached->expects($this->once())
            ->method('append')
            ->willReturn(true);

        $this->memcached->expects($this->once())
            ->method('touch')
            ->willReturn(true);

        $s->save('foo', $this->data);
    }

    public function testGet(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->willReturn($this->data);

        $result = $this->s->get('foo');
        $this->assertEquals($this->data, $result);
    }

    public function testFind(): void
    {
        $keys = 'key1|key2|key3';

        $this->memcached->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $keys,
                [
                    'key3' => $this->data,
                    'key2' => ['__meta' => ['id' => 'bar', 'utime' => microtime(true)]],
                    'key1' => ['__meta' => ['id' => 'baz', 'utime' => microtime(true)]],
                ]
            );

        $results = $this->s->find();
        $this->assertCount(3, $results);
    }

    public function testClear(): void
    {
        $keys = 'key1|key2';

        $this->memcached->expects($this->once())
            ->method('get')
            ->willReturn($keys);

        $this->memcached->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->memcached->expects($this->once())
            ->method('deleteMulti')
            ->willReturn(true);

        $this->s->clear();
    }

    public function testGarbageCollection(): void
    {
        // Memcached has built-in expiration, so should just run
        $this->s->prune(1);
    }
}
