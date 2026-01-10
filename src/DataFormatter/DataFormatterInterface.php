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

namespace DebugBar\DataFormatter;

/**
 * Formats data to be outputed as string
 */
interface DataFormatterInterface
{
    /**
     * Transforms a PHP variable to a string representation
     *
     */
    public function formatVar(mixed $data, bool $deep = true): string;

    /**
     * Transforms a duration in seconds in a readable string
     *
     */
    public function formatDuration(int|float $seconds): string;

    /**
     * Transforms a size in bytes to a human readable string
     *
     * */
    public function formatBytes(float|int|string|null $size, int $precision = 2): string;

    /**
     * Format a classname in a readable string
     *
     */
    public function formatClassName(object $object): string;
}
