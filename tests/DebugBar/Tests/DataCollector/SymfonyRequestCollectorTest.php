<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\Bridge\Symfony\SymfonyRequestCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\ConfigCollector;
use Symfony\Component\HttpFoundation\Request;

class SymfonyRequestCollectorTest extends DebugBarTestCase
{
    public function testCollect(): void
    {
        $symfonyRequest = Request::create('/index.php');

        $collector = new SymfonyRequestCollector($symfonyRequest);
        $collector->useHtmlVarDumper(false);

        $data = $collector->collect();

        $this->assertEquals('/index.php', $data['data']['uri']);
        $this->assertEquals('200 OK', $data['tooltip']['status']);
    }

    public function testHideDefaultMasks(): void
    {
        $symfonyRequest = Request::create('/index.php', 'POST', [
            'password' => 'secret',
            'foo' => 'bar',
            'masked' => 'masked',
            'auth' => [
                'user' => 'barry',
                'password' => 'secret',
            ]
        ], [], [], [
            'PHP_AUTH_PW' => 'secret'
        ]);

        $collector = new SymfonyRequestCollector($symfonyRequest);
        $collector->useHtmlVarDumper(false);

        $data = $collector->collect();

        $this->assertStringNotContainsString('secret', $data['data']['request_request']);
        $this->assertStringContainsString('masked', $data['data']['request_request']);
        $this->assertStringContainsString('"PHP_AUTH_PW" => "se***"', $data['data']['request_server']);
        $this->assertEquals('200 OK', $data['tooltip']['status']);
    }

    public function testHideAddedMasks(): void
    {
        $symfonyRequest = Request::create('/index.php', 'POST', [
            'foo' => 'bar',
            'masked' => 'my-masked-string',
        ]);

        $collector = new SymfonyRequestCollector($symfonyRequest);
        $collector->useHtmlVarDumper(false);
        $collector->addMaskedKeys(['masked']);

        $data = $collector->collect();

        $this->assertStringNotContainsString('my-masked-string', $data['data']['request_request']);
        $this->assertStringContainsString('"foo" => "bar"', $data['data']['request_request']);
    }


}
