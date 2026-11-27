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

    public function testNestedMeasures(): void
    {
        $returned = $this->c->measure('outer', fn(): string => $this->c->measure('inner', fn(): string => 'innerValue'));

        $this->assertSame('innerValue', $returned);
        $labels = array_column($this->c->getMeasures(), 'label');
        sort($labels);
        $this->assertSame(['inner', 'outer'], $labels);

        $data = $this->c->collect();
        $this->assertCount(2, $data['measures']);
    }

    public function testCollectsMeasureAfterClosureThrows(): void
    {
        $expected = new \RuntimeException('Expected failure');

        try {
            $this->c->measure('failed', static function () use ($expected): never {
                throw $expected;
            });
            $this->fail('The closure exception should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame($expected, $exception);
        }

        $data = $this->c->collect();

        $this->assertCount(1, $data['measures']);
        $this->assertSame('failed', $data['measures'][0]['label']);
    }
}
