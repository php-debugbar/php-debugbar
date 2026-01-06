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
 * Provides an abstraction of PHP native features for easier integration
 * in third party frameworks
 */
interface HttpDriverInterface
{
    /**
     * Sets HTTP headers
     *
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): void;

    /**
     *  Write a string to the output buffer
     */
    public function output(string $content): void;

    /**
     * Checks if the session is started
     *
     * @return boolean
     */
    public function isSessionStarted(): bool;

    /**
     * Sets a value in the session
     *
     */
    public function setSessionValue(string $name, mixed $value): void;

    /**
     * Checks if a value is in the session
     *
     */
    public function hasSessionValue(string $name): bool;

    /**
     * Returns a value from the session
     *
     */
    public function getSessionValue(string $name): mixed;

    /**
     * Deletes a value from the session
     *
     */
    public function deleteSessionValue(string $name): void;
}
