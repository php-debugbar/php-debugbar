<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\HttpDriverInterface;

class MockHttpDriver implements HttpDriverInterface
{
    public array $headers = [];

    public bool $sessionStarted = true;

    public array $session = [];

    public string $output = '';

    public function setHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function output(string $content): void
    {
        $this->output .= $content;
    }

    public function isSessionStarted(): bool
    {
        return $this->sessionStarted;
    }

    public function setSessionValue(string $name, mixed $value): void
    {
        $this->session[$name] = $value;
    }

    public function hasSessionValue(string $name): bool
    {
        return array_key_exists($name, $this->session);
    }

    public function getSessionValue(string $name): mixed
    {
        return $this->session[$name];
    }

    public function deleteSessionValue(string $name): void
    {
        unset($this->session[$name]);
    }
}
