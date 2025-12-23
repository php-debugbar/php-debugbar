<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\TimeDataCollector;

class TimeDataCollectorTest extends DebugBarTestCase
{
    private $s;
    private $c;

    public function setUp(): void
    {
        $this->s = microtime(true);
        $this->c = new TimeDataCollector($this->s);
    }

    public function testAddMeasure()
    {
        $this->c->addMeasure('foo', $this->s, $this->s + 10, ['a' => 'b'], 'timer');
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('foo', $m[0]['label']);
        $this->assertEquals(10, $m[0]['duration']);
        $this->assertEquals(['a' => 'b'], $m[0]['params']);
        $this->assertEquals('timer', $m[0]['collector']);
    }

    public function testStartStopMeasure()
    {
        $this->c->startMeasure('foo', 'bar', 'baz');
        usleep(1000);
        $this->c->stopMeasure('foo', ['bar' => 'baz']);
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('bar', $m[0]['label']);
        $this->assertEquals('baz', $m[0]['collector']);
        $this->assertEquals(['bar' => 'baz'], $m[0]['params']);
        $this->assertLessThan($m[0]['end'], $m[0]['start']);
    }

    public function testCollect()
    {
        $this->c->addMeasure('foo', 0, 10);
        $this->c->addMeasure('bar', 10, 20);
        $data = $this->c->collect();
        $this->assertGreaterThan($this->s, $data['end']);
        $this->assertGreaterThan(0, $data['duration']);
        $this->assertCount(2, $data['measures']);
    }

    public function testMeasure()
    {
        $returned = $this->c->measure('bar', function () {
            usleep(50);
            return 'returnedValue';
        });
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('bar', $m[0]['label']);
        $this->assertLessThan($m[0]['end'], $m[0]['start']);
        $this->assertSame('returnedValue', $returned);
    }
}
