<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DebugBarException;
use DebugBar\Tests\DataCollector\MockCollector;
use DebugBar\Tests\Storage\MockStorage;

class DebugBarTest extends DebugBarTestCase
{
    public function testAddCollector(): void
    {
        $this->debugbar->addCollector($c = new MockCollector());
        $this->assertTrue($this->debugbar->hasCollector('mock'));
        $this->assertEquals($c, $this->debugbar->getCollector('mock'));
        $this->assertContains($c, $this->debugbar->getCollectors());
    }

    public function testAddCollectorWithSameName(): void
    {
        $this->debugbar->addCollector(new MockCollector());

        $this->expectException(DebugBarException::class);

        $this->debugbar->addCollector(new MockCollector());
    }

    public function testCollect(): void
    {
        $data = ['foo' => 'bar'];
        $this->debugbar->addCollector(new MockCollector($data));
        $datac = $this->debugbar->collect();

        $this->assertArrayHasKey('mock', $datac);
        $this->assertEquals($datac['mock'], $data);
        $this->assertEquals($datac, $this->debugbar->getData());
    }

    public function testArrayAccess(): void
    {
        $this->debugbar->addCollector($c = new MockCollector());
        $this->assertEquals($c, $this->debugbar['mock']);
        $this->assertArrayHasKey('mock', $this->debugbar);
        $this->assertArrayNotHasKey('foo', $this->debugbar);
    }

    public function testStorage(): void
    {
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector(new MockCollector(['foo']));
        $data = $this->debugbar->collect();

        $this->assertEquals($s->get($this->debugbar->getCurrentRequestId()), $data);
    }

    public function testStorageWithNanData()
    {
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector(new MockCollector([NAN]));

        $data = $this->debugbar->collect();

        // NAN is replaced
        $this->assertEquals(['[NON-FINITE FLOAT]'], $s->get($this->debugbar->getCurrentRequestId())['mock']);
    }

    public function testStorageWithInvalidUtf8Data()
    {
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector(new MockCollector(["\xC3\x28"]));

        $data = $this->debugbar->collect();

        $this->assertEquals($data, $s->get($this->debugbar->getCurrentRequestId()));
    }

    public function testGetDataAsHeaders(): void
    {
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $headers = $this->debugbar->getDataAsHeaders();
        $this->assertArrayHasKey('phpdebugbar', $headers);
    }

    public function testSendDataInHeaders(): void
    {
        /** @var MockHttpDriver $http */
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->addCollector($c = new MockCollector(['foo']));

        $this->debugbar->sendDataInHeaders();
        $this->assertArrayHasKey('phpdebugbar', $http->headers);
    }

    public function testSendDataInHeadersWithOpenHandler(): void
    {
        /** @var MockHttpDriver $http */
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector($c = new MockCollector(['foo']));

        $this->debugbar->sendDataInHeaders(true);
        $this->assertArrayHasKey('phpdebugbar-id', $http->headers);
        $this->assertEquals($this->debugbar->getCurrentRequestId(), $http->headers['phpdebugbar-id']);
    }

    public function testStackedData(): void
    {
        /** @var MockHttpDriver $http */
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $this->debugbar->stackData();

        $this->assertArrayHasKey($ns = $this->debugbar->getStackDataSessionNamespace(), $http->session);
        $this->assertArrayHasKey($id = $this->debugbar->getCurrentRequestId(), $http->session[$ns]);
        $this->assertArrayHasKey('mock', $http->session[$ns][$id]);
        $this->assertEquals($c->collect(), $http->session[$ns][$id]['mock']);
        $this->assertTrue($this->debugbar->hasStackedData());

        $data = $this->debugbar->getStackedData();
        $this->assertArrayNotHasKey($ns, $http->session);
        $this->assertArrayHasKey($id, $data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('mock', $data[$id]);
        $this->assertEquals($c->collect(), $data[$id]['mock']);
    }

    public function testStackedDataWithStorage(): void
    {
        /** @var MockHttpDriver $http */
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $this->debugbar->stackData();

        $id = $this->debugbar->getCurrentRequestId();
        $ns = $this->debugbar->getStackDataSessionNamespace();
        $this->assertNull($http->session[$ns][$id]);

        $data = $this->debugbar->getStackedData();
        $this->assertEquals($c->collect(), $data[$id]['mock']);
    }

    public function testStackedIds(): void
    {
        /** @var MockHttpDriver $http */
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $this->debugbar->stackData();

        $id = $this->debugbar->getCurrentRequestId();
        $ns = $this->debugbar->getStackDataSessionNamespace();
        $this->assertTrue($this->debugbar->hasStackedData());

        $data = $this->debugbar->getStackedIds();
        $this->assertArrayNotHasKey($ns, $http->session);
        $this->assertEquals($id, $data[0]);
        $this->assertCount(1, $data);
    }

    public function testReset(): void
    {
        $data = ['foo' => 'bar'];
        $this->debugbar->addCollector(new MockCollector($data));
        $this->debugbar->reset();

        $datac = $this->debugbar->collect();

        $this->assertArrayHasKey('mock', $datac);
        $this->assertEquals([], $datac['mock']);
        $this->assertEquals([], $this->debugbar->getData()['mock']);
    }
}
