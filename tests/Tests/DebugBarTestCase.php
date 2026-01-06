<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DebugBar;
use PHPUnit\Framework\TestCase;

abstract class DebugBarTestCase extends TestCase
{
    protected DebugBar $debugbar;
    protected MockHttpDriver $httpDriver;

    public function setUp(): void
    {
        $this->debugbar = new DebugBar();
        $this->httpDriver = new MockHttpDriver();
        $this->debugbar->setHttpDriver($this->httpDriver);
    }

    public function assertJsonIsArray(string $json): void
    {
        $data = json_decode($json);
        $this->assertIsArray($data);
    }

    public function assertJsonIsObject(string $json): void
    {
        $data = json_decode($json);
        $this->assertIsObject($data);
    }

    public function assertJsonArrayNotEmpty(string $json): void
    {
        $data = json_decode($json, true);
        $this->assertTrue(is_array($data) && !empty($data));
    }

    public function assertJsonHasProperty(string $json, string $property): void
    {
        $data = json_decode($json, true);
        $this->assertArrayHasKey($property, $data);
    }

    public function assertJsonPropertyEquals(string $json, string $property, mixed $expected): void
    {
        $data = json_decode($json, true);
        $this->assertArrayHasKey($property, $data);
        $this->assertEquals($expected, $data[$property]);
    }
}
