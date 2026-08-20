<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\RequestDataCollector;
use PHPUnit\Framework\TestCase;

class RequestDataCollectorTest extends TestCase
{
    private RequestDataCollector $collector;

    public function setUp(): void
    {
        $this->collector = new RequestDataCollector();
    }

    public function testLeavesUrisWithoutSensitiveParamsAlone(): void
    {
        $uri = '/orders?page=2&sort=created_at';

        $this->assertEquals($uri, $this->collector->hideMaskedUri($uri));
    }

    public function testLeavesUrisWithoutAQueryStringAlone(): void
    {
        $this->assertEquals('/orders', $this->collector->hideMaskedUri('/orders'));
        $this->assertEquals('/orders?', $this->collector->hideMaskedUri('/orders?'));
    }

    public function testMasksSensitiveQueryParams(): void
    {
        $this->assertEquals(
            '/callback?state=xyz&access_token=ab***yz',
            $this->collector->hideMaskedUri('/callback?state=xyz&access_token=abcdefghijklmnopqrstuvwxyz')
        );
    }

    public function testMasksNestedAndEncodedParamNames(): void
    {
        $this->assertEquals(
            '/search?filter%5Bpassword%5D=hu***',
            $this->collector->hideMaskedUri('/search?filter%5Bpassword%5D=hunter2')
        );
    }

    public function testMasksExplicitlyAddedKeys(): void
    {
        $this->collector->addMaskedKeys(['email']);

        $this->assertEquals(
            '/users?email=ba***nl',
            $this->collector->hideMaskedUri('/users?email=barry@fruitcake.nl')
        );
    }

    public function testKeepsValuelessParams(): void
    {
        $this->assertEquals('/orders?debug&token=****', $this->collector->hideMaskedUri('/orders?debug&token=abcd'));
    }

    public function testSummaryListsParamNamesButNotValues(): void
    {
        $_GET = ['page' => '2', 'q' => 'secret search term'];
        $_SERVER['REQUEST_URI'] = '/search';

        $summary = $this->collector->collect()['summary'];

        $this->assertEquals('page, q', $summary['get']);
        $this->assertStringNotContainsString('secret search term', json_encode($summary));

        $_GET = [];
        unset($_SERVER['REQUEST_URI']);
    }

    public function testSummaryCapsLongKeyLists(): void
    {
        $_SESSION = array_fill_keys(array_map(fn($i) => "key$i", range(1, 13)), 'x');

        $summary = $this->collector->collect()['summary'];

        $this->assertStringEndsWith('(+3 more)', $summary['session']);

        $_SESSION = [];
    }
}
