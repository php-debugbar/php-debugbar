<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataFormatter\DataFormatter;
use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\TimeDataCollector;

class TimeDataCollectorTest extends DebugBarTestCase
{
    private float $s;
    private TimeDataCollector $c;

    public function setUp(): void
    {
        $this->s = microtime(true);
        $this->c = new TimeDataCollector($this->s);
        $this->c->setDataFormatter(new DataFormatter());
    }

    public function testAddMeasure(): void
    {
        $this->c->addMeasure('foo', $this->s, $this->s + 10, ['a' => 'b'], 'timer');
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('foo', $m[0]['label']);
        $this->assertEquals(10, $m[0]['duration']);
        $this->assertEquals(['a' => 'b'], $m[0]['params']);
        $this->assertEquals('timer', $m[0]['collector']);
    }

    public function testStartStopMeasure(): void
    {
        $this->c->startMeasure('foo', 'bar', 'baz');
        usleep(1000);
        $this->c->stopMeasure('foo', ['bar' => 'baz']);
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('bar', $m[0]['label']);
        $this->assertEquals('baz', $m[0]['collector']);
        $this->assertEquals(['bar' => 'baz'], $m[0]['params']);
        $this->assertTrue($m[0]['start'] < $m[0]['end'], 'Start time should be before end time');
    }

    public function testStartStopNestedMeasuresWithTheSameName(): void
    {
        $this->c->startMeasure('foo', 'outer');
        usleep(1000);
        $this->c->startMeasure('foo', 'inner');
        usleep(1000);
        $this->c->stopMeasure('foo');
        $this->assertTrue($this->c->hasStartedMeasure('foo'), 'The enclosing measure should still be pending');
        $this->c->stopMeasure('foo');
        $this->assertFalse($this->c->hasStartedMeasure('foo'));

        $m = $this->c->getMeasures();
        $this->assertCount(2, $m);
        $this->assertEquals('inner', $m[0]['label']);
        $this->assertEquals('outer', $m[1]['label']);
        $this->assertGreaterThan($m[0]['duration'], $m[1]['duration'], 'The enclosing measure should span the nested one');
    }

    public function testCollectStopsEveryPendingNestedMeasure(): void
    {
        $this->c->startMeasure('foo');
        $this->c->startMeasure('foo');

        $data = $this->c->collect();

        $this->assertCount(2, $data['measures']);
        $this->assertFalse($this->c->hasStartedMeasure('foo'));
    }

    public function testMeasureCanRecurse(): void
    {
        $recurse = function (int $depth) use (&$recurse) {
            return $depth > 0 ? $this->c->measure('rec', fn() => $recurse($depth - 1)) : 'done';
        };

        $this->assertSame('done', $this->c->measure('rec', fn() => $recurse(2)));
        $this->assertCount(3, $this->c->getMeasures());
    }

    public function testCollect(): void
    {
        $this->c->addMeasure('foo', 0, 10);
        $this->c->addMeasure('bar', 10, 20);
        $data = $this->c->collect();
        $this->assertGreaterThan($this->s, $data['end']);
        $this->assertGreaterThan(0, $data['duration']);
        $this->assertCount(2, $data['measures']);
    }

    public function testMeasure(): void
    {
        $returned = $this->c->measure('bar', function (): string {
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
