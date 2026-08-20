<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\SummaryFormatter;
use DebugBar\Tests\DataCollector\MockCollector;

class SummaryFormatterTest extends DebugBarTestCase
{
    public function testFormatsMapsAsKeyValueLines(): void
    {
        $this->assertEquals(
            "count = 2\nfailed = false",
            SummaryFormatter::formatValue(['count' => 2, 'failed' => false])
        );
    }

    public function testFormatsListsAsBullets(): void
    {
        $this->assertEquals(
            "- first\n- second",
            SummaryFormatter::formatValue(['first', 'second'])
        );
    }

    public function testIndentsNestedValues(): void
    {
        $summary = [
            'count' => 1,
            'exceptions' => [
                ['type' => 'RuntimeException', 'at' => 'Foo.php:12', 'trace' => ['Bar.php(3)']],
            ],
        ];

        $this->assertEquals(
            implode("\n", [
                'count = 1',
                'exceptions:',
                '  - type = RuntimeException',
                '    at = Foo.php:12',
                '    trace:',
                '      - Bar.php(3)',
            ]),
            SummaryFormatter::formatValue($summary)
        );
    }

    public function testFormatsNullAndScalars(): void
    {
        $this->assertEquals('null', SummaryFormatter::formatValue(null));
        $this->assertEquals('true', SummaryFormatter::formatValue(true));
        $this->assertEquals('12', SummaryFormatter::formatValue(12));
    }

    public function testGetSummaryUsesDeclaredSummaryWidgets(): void
    {
        $this->debugbar->addCollector(new MockCollector(
            ['summary' => ['answer' => 42]],
            'mock',
            ['mock' => ['widget' => 'Foo', 'map' => 'mock'], 'mock:summary' => ['map' => 'mock.summary']]
        ));

        $summary = $this->debugbar->getSummary();

        $this->assertStringContainsString('# PHP DebugBar summary', $summary);
        $this->assertStringContainsString("## Mock\nanswer = 42", $summary);
    }

    public function testGetSummaryPrefersTheDeclaredControlTitle(): void
    {
        $this->debugbar->addCollector(new MockCollector(
            ['summary' => ['answer' => 42]],
            'mock',
            ['mock' => ['widget' => 'Foo', 'title' => 'Custom title'], 'mock:summary' => ['map' => 'mock.summary']]
        ));

        $this->assertStringContainsString('## Custom title', $this->debugbar->getSummary());
    }

    public function testGetSummaryFallsBackToDatasetsFromUnknownCollectors(): void
    {
        $formatter = new SummaryFormatter($this->debugbar);

        $sections = $formatter->getSections([
            '__meta' => ['id' => 'abc'],
            'unregistered' => ['summary' => ['hello' => 'world']],
        ]);

        $this->assertEquals(['Unregistered' => ['hello' => 'world']], $sections);
    }

    public function testGetSummarySkipsCollectorsWithoutASummary(): void
    {
        $this->debugbar->addCollector(new MessagesCollector());

        $summary = $this->debugbar->getSummary();

        // A collector with nothing to say contributes no section
        $this->assertStringNotContainsString('## Messages', $summary);
    }

    public function testGetSummaryIncludesMetaButNotTheUnmaskedUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/checkout?token=super-secret-value';
        $this->debugbar->addCollector(new RequestDataCollector());

        $summary = $this->debugbar->getSummary();

        $this->assertStringContainsString('datetime = ', $summary);
        $this->assertStringNotContainsString('super-secret-value', $summary);
        $this->assertStringContainsString('uri = /checkout?token=su***ue', $summary);

        unset($_SERVER['REQUEST_URI']);
    }

    public function testTruncatesOverlongSummaries(): void
    {
        $this->debugbar->addCollector(new MockCollector(
            ['summary' => ['big' => str_repeat('x', 500)]],
            'mock',
            ['mock' => ['widget' => 'Foo'], 'mock:summary' => ['map' => 'mock.summary']]
        ));

        $formatter = new SummaryFormatter($this->debugbar);
        $summary = $formatter->format($this->debugbar->getData(), 100);

        $this->assertStringEndsWith("\n\n(summary truncated)", $summary);
    }
}
