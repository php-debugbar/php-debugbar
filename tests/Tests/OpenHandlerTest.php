<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DebugBarException;
use DebugBar\OpenHandler;
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

    public function testClear(): void
    {
        $result = $this->openHandler->handle(['op' => 'clear'], false, false);
        $this->assertJsonPropertyEquals($result, 'success', true);
    }
}
