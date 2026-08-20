<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DebugBarException;
use DebugBar\OpenHandler;
use DebugBar\Tests\DataCollector\MockCollector;
use DebugBar\Tests\Storage\MockStorage;

class OpenHandlerTest extends DebugBarTestCase
{
    private OpenHandler $openHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->debugbar->setStorage(new MockStorage(['foo' => ['__meta' => ['id' => 'foo']]]));
        $this->openHandler = new OpenHandler($this->debugbar);
    }

    public function testFind(): void
    {
        $request = ['op' => 'find'];
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonArrayNotEmpty($result);
    }

    public function testGet(): void
    {
        $request = ['op' => 'get', 'id' => 'foo'];
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonIsObject($result);
        $this->assertJsonHasProperty($result, '__meta');
        $data = json_decode($result, true);
        $this->assertEquals('foo', $data['__meta']['id']);
    }

    public function testGetMissingId(): void
    {
        $this->expectException(DebugBarException::class);

        $this->openHandler->handle(['op' => 'get'], false, false);
    }

    public function testSummary(): void
    {
        $this->debugbar->addCollector(new MockCollector(
            ['summary' => ['answer' => 42]],
            'mock',
            ['mock' => ['widget' => 'Foo'], 'mock:summary' => ['map' => 'mock.summary']]
        ));
        $this->debugbar->getStorage()->save('bar', ['__meta' => ['id' => 'bar'], 'mock' => ['summary' => ['answer' => 42]]]);

        $result = $this->openHandler->handle(['op' => 'summary', 'id' => 'bar'], false, false);

        $this->assertStringContainsString('# PHP DebugBar summary', $result);
        $this->assertStringContainsString("## Mock\nanswer = 42", $result);
    }

    public function testSummarySendsPlainText(): void
    {
        $this->openHandler->handle(['op' => 'summary', 'id' => 'foo'], false, true);

        $this->assertEquals('text/plain; charset=utf-8', $this->httpDriver->headers['Content-Type']);
    }

    public function testSummaryWithoutIdUsesTheLatestDataset(): void
    {
        $result = $this->openHandler->handle(['op' => 'summary'], false, false);

        $this->assertStringContainsString('id = foo', $result);
    }

    public function testClear(): void
    {
        $result = $this->openHandler->handle(['op' => 'clear'], false, false);
        $this->assertJsonPropertyEquals($result, 'success', true);
    }
}
