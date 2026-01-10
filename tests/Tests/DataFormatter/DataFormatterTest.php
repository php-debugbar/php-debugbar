<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataFormatter;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataFormatter\DataFormatter;

class DataFormatterTest extends DebugBarTestCase
{
    public function testFormatVar(): void
    {
        $f = new DataFormatter();
        $this->assertEquals("true", $f->formatVar(true));
    }

    public function testFormatVarArray(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Skipping on Windows');
        }
        $f = new DataFormatter();
        $expected = <<<EOTXT
            array:1 [
              "foo" => "bar"
            ]
            EOTXT;

        $simpleArray = ['foo' => 'bar'];
        $this->assertEquals($expected, $f->formatVar($simpleArray));
        $this->assertEquals($expected, $f->formatVar($simpleArray, false));

        $expected = <<<EOTXT
            array:1 [
              "foo" => array:1 [
                "bar" => "baz"
              ]
            ]
            EOTXT;

        $deeperArray = ['foo' => ['bar' => 'baz']];
        $this->assertEquals($expected, $f->formatVar($deeperArray));

        $expected = <<<EOTXT
            array:1 [
              "foo" => array:1 [ …1]
            ]
            EOTXT;
        $this->assertEquals($expected, $f->formatVar($deeperArray, false));

        $f->mergeClonerOptions(['max_depth' => 0]);
        $expected = <<<EOTXT
            array:1 [ …1]
            EOTXT;
        $this->assertEquals($expected, $f->formatVar($deeperArray));
        $this->assertEquals($expected, $f->formatVar($deeperArray, false));

    }

    public function testFormatDuration(): void
    {
        $f = new DataFormatter();
        $this->assertEquals("100μs", $f->formatDuration(0.0001));
        $this->assertEquals("100ms", $f->formatDuration(0.1));
        $this->assertEquals("1s", $f->formatDuration(1));
        $this->assertEquals("1.35s", $f->formatDuration(1.345));
    }

    public function testFormatBytes(): void
    {
        $f = new DataFormatter();
        $this->assertEquals("0B", $f->formatBytes(0));
        $this->assertEquals("1B", $f->formatBytes(1));
        $this->assertEquals("1KB", $f->formatBytes(1024));
        $this->assertEquals("1MB", $f->formatBytes(1024 * 1024));
        $this->assertEquals("-1B", $f->formatBytes(-1));
        $this->assertEquals("-1KB", $f->formatBytes(-1024));
        $this->assertEquals("-1MB", $f->formatBytes(-1024 * 1024));
    }
}
