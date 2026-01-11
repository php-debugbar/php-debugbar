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

namespace DebugBar\Storage;

interface StorageInterface
{
    /**
     * Saves collected data
     *
     */
    public function save(string $id, array $data): void;

    /**
     * Returns collected data with the specified id
     *
     *
     */
    public function get(string $id): array;

    /**
     * Returns a metadata about collected data
     *
     * @param integer $max
     * @param integer $offset
     *
     */
    public function find(array $filters = [], int $max = 20, int $offset = 0): array;

    /**
     * Clears all the collected data
     */
    public function clear(): void;

    public function prune(int $hours = 24): void;
}
