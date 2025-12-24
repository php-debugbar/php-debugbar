<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\HttpDriverInterface;

class MockHttpDriver implements HttpDriverInterface
{
    public $headers = [];

    public $sessionStarted = true;

    public $session = [];

    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function isSessionStarted()
    {
        return $this->sessionStarted;
    }

    public function setSessionValue($name, $value)
    {
        $this->session[$name] = $value;
    }

    public function hasSessionValue($name)
    {
        return array_key_exists($name, $this->session);
    }

    public function getSessionValue($name)
    {
        return $this->session[$name];
    }

    public function deleteSessionValue($name)
    {
        unset($this->session[$name]);
    }
}
