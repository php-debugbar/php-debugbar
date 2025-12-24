<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DebugBar;
use PHPUnit\Framework\TestCase;

abstract class DebugBarTestCase extends TestCase
{
    protected $debugbar;

    public function setUp(): void
    {
        $this->debugbar = new DebugBar();
        $this->debugbar->setHttpDriver($http = new MockHttpDriver());
    }

    public function assertJsonIsArray($json)
    {
        $data = json_decode($json);
        $this->assertIsArray($data);
    }

    public function assertJsonIsObject($json)
    {
        $data = json_decode($json);
        $this->assertIsObject($data);
    }

    public function assertJsonArrayNotEmpty($json)
    {
        $data = json_decode($json, true);
        $this->assertTrue(is_array($data) && !empty($data));
    }

    public function assertJsonHasProperty($json, $property)
    {
        $data = json_decode($json, true);
        $this->assertTrue(array_key_exists($property, $data));
    }

    public function assertJsonPropertyEquals($json, $property, $expected)
    {
        $data = json_decode($json, true);
        $this->assertTrue(array_key_exists($property, $data));
        $this->assertEquals($expected, $data[$property]);
    }
}
