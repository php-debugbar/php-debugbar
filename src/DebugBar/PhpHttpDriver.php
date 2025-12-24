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
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }

    /**
     * @return bool
     */
    public function isSessionStarted()
    {
        return isset($_SESSION);
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setSessionValue($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasSessionValue($name)
    {
        return array_key_exists($name, $_SESSION);
    }

    /**
     * @param string $name
     */
    public function getSessionValue($name)
    {
        return $_SESSION[$name];
    }

    /**
     * @param string $name
     */
    public function deleteSessionValue($name)
    {
        unset($_SESSION[$name]);
    }
}
