<?php

declare(strict_types=1);

/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

/**
 * HTTP driver for native php
 */
class PhpHttpDriver implements HttpDriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }

    public function output(string $content): void
    {
        echo $content;
    }

    public function isSessionStarted(): bool
    {
        return isset($_SESSION);
    }

    public function setSessionValue(string $name, mixed $value): void
    {
        $_SESSION[$name] = $value;
    }

    public function hasSessionValue(string $name): bool
    {
        return array_key_exists($name, $_SESSION);
    }

    public function getSessionValue(string $name): mixed
    {
        return $_SESSION[$name];
    }

    public function deleteSessionValue(string $name): void
    {
        unset($_SESSION[$name]);
    }
}
