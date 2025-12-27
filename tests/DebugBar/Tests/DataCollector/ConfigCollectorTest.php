<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\ConfigCollector;

class ConfigCollectorTest extends DebugBarTestCase
{
    public function testCollect(): void
    {
        $c = new ConfigCollector(['s' => 'bar', 'a' => [], 'o' => new \stdClass()]);
        $c->useHtmlVarDumper(false);

        $data = $c->collect();
        $this->assertArrayHasKey('s', $data);
        $this->assertEquals('bar', $data['s']);
        $this->assertArrayHasKey('a', $data);
    }

    public function testName(): void
    {
        $c = new ConfigCollector([], 'foo');
        $this->assertEquals('foo', $c->getName());
        $this->assertArrayHasKey('foo', $c->getWidgets());
    }

    public function testAssets(): void
    {
        $c = new ConfigCollector();
        $c->useHtmlVarDumper(false);
        $this->assertEmpty($c->getAssets());

        $c->useHtmlVarDumper();
        $this->assertNotEmpty($c->getAssets());
    }

    public function testHtmlRendering(): void
    {
        $c = new ConfigCollector(['k' => ['one', 'two']]);

        $c->useHtmlVarDumper();
        $data = $c->collect();
        $this->assertEquals(['k'], array_keys($data));
        $this->assertStringContainsString("sf-dump", $data['k']);
        $this->assertStringContainsString('one', $data['k']);
        $this->assertStringContainsString('two', $data['k']);
        $this->assertStringContainsString('span', $data['k']);
    }
}
