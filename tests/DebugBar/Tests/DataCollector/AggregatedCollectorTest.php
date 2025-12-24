<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\AggregatedCollector;

class AggregatedCollectorTest extends DebugBarTestCase
{
    private AggregatedCollector $c;

    public function setUp(): void
    {
        $this->c = new AggregatedCollector('test');
    }

    public function testAddCollector(): void
    {
        $this->c->addCollector($c = new MockCollector());
        $this->assertContains($c, $this->c->getCollectors());
        $this->assertEquals($c, $this->c['mock']);
        $this->assertArrayHasKey('mock', $this->c);
    }

    public function testCollect(): void
    {
        $this->c->addCollector(new MockCollector(['foo' => 'bar'], 'm1'));
        $this->c->addCollector(new MockCollector(['bar' => 'foo'], 'm2'));
        $data = $this->c->collect();
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('foo', $data);
        $this->assertEquals('bar', $data['foo']);
        $this->assertArrayHasKey('bar', $data);
        $this->assertEquals('foo', $data['bar']);
    }

    public function testMergeProperty(): void
    {
        $this->c->addCollector(new MockCollector(['foo' => ['a' => 'b']], 'm1'));
        $this->c->addCollector(new MockCollector(['foo' => ['c' => 'd']], 'm2'));
        $this->c->setMergeProperty('foo');
        $data = $this->c->collect();
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('a', $data);
        $this->assertEquals('b', $data['a']);
        $this->assertArrayHasKey('c', $data);
        $this->assertEquals('d', $data['c']);
    }

    public function testSort(): void
    {
        $this->c->addCollector(new MockCollector([['foo' => 2, 'id' => 1]], 'm1'));
        $this->c->addCollector(new MockCollector([['foo' => 1, 'id' => 2]], 'm2'));
        $this->c->setSort('foo');
        $data = $this->c->collect();
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]['id']);
        $this->assertEquals(1, $data[1]['id']);
    }
}
