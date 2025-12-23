<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DebugBar;
use DebugBar\DataCollector\ConfigCollector;

class ConfigCollectorTest extends DebugBarTestCase
{
    public function testCollect()
    {
        $c = new ConfigCollector(['s' => 'bar', 'a' => [], 'o' => new \stdClass()]);
        $data = $c->collect();
        $this->assertArrayHasKey('s', $data);
        $this->assertEquals('bar', $data['s']);
        $this->assertArrayHasKey('a', $data);
        $this->assertEquals("[]", $data['a']);
        $this->assertArrayHasKey('o', $data);
    }

    public function testName()
    {
        $c = new ConfigCollector([], 'foo');
        $this->assertEquals('foo', $c->getName());
        $this->assertArrayHasKey('foo', $c->getWidgets());
    }

    public function testAssets()
    {
        $c = new ConfigCollector();
        $this->assertEmpty($c->getAssets());

        $c->useHtmlVarDumper();
        $this->assertNotEmpty($c->getAssets());
    }

    public function testHtmlRendering()
    {
        $c = new ConfigCollector(['k' => ['one', 'two']]);

        $this->assertFalse($c->isHtmlVarDumperUsed());
        $data = $c->collect();
        $this->assertEquals(['k'], array_keys($data));
        $this->assertStringContainsString('one', $data['k']);
        $this->assertStringContainsString('two', $data['k']);
        $this->assertStringNotContainsString('span', $data['k']);

        $c->useHtmlVarDumper();
        $data = $c->collect();
        $this->assertEquals(['k'], array_keys($data));
        $this->assertStringContainsString('one', $data['k']);
        $this->assertStringContainsString('two', $data['k']);
        $this->assertStringContainsString('span', $data['k']);
    }
}
