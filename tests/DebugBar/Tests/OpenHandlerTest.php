<?php

namespace DebugBar\Tests;

use DebugBar\DataHasher;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use DebugBar\OpenHandler;
use DebugBar\Tests\DataCollector\MockActionCollector;
use DebugBar\Tests\DataCollector\MockCollector;
use DebugBar\Tests\Storage\MockStorage;

class OpenHandlerTest extends DebugBarTestCase
{
    private $openHandler;

    public function setUp(): void
    {
        parent::setUp();
        $this->debugbar->setStorage(new MockStorage(array('foo' => array('__meta' => array('id' => 'foo')))));
        $this->openHandler = new OpenHandler($this->debugbar);
    }

    public function testFind()
    {
        $request = array();
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonArrayNotEmpty($result);
    }

    public function testGet()
    {
        $request = array('op' => 'get', 'id' => 'foo');
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonIsObject($result);
        $this->assertJsonHasProperty($result, '__meta');
        $data = json_decode($result, true);
        $this->assertEquals('foo', $data['__meta']['id']);
    }

    public function testGetMissingId()
    {
        $this->expectException(DebugBarException::class);

        $this->openHandler->handle(array('op' => 'get'), false, false);
    }

    public function testClear()
    {
        $result = $this->openHandler->handle(array('op' => 'clear'), false, false);
        $this->assertJsonPropertyEquals($result, 'success', true);
    }

    public function testExecute()
    {
        $this->debugbar->addCollector(new MockActionCollector([], 'mock-action'));
        $dataHasher = new DataHasher('secret');
        DebugBar::setDataHasher($dataHasher);

        $data = [
            'op' => 'execute',
            'collector' => 'mock-action',
            'action' => 'run',
        ];

        $data['signature'] = $dataHasher->sign($data);

        $result = $this->openHandler->handle($data, false, false);
        $this->assertJsonPropertyEquals($result, 'result', 'done');
    }

    public function testExecuteWrongSignature()
    {
        $this->debugbar->addCollector(new MockActionCollector([], 'mock-action'));
        $dataHasher = new DataHasher('secret');
        DebugBar::setDataHasher($dataHasher);

        $data = [
            'op' => 'execute',
            'collector' => 'mock-action',
            'action' => 'run',
            'signature' => 'invalid',
        ];

        $this->expectExceptionMessage("Signature does not match in 'execute' operation");

        $this->openHandler->handle($data, false, false);
    }
}
