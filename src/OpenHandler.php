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

use DebugBar\Storage\StorageInterface;

/**
 * Handler to list and open saved dataset
 */
class OpenHandler
{
    protected DebugBar $debugBar;
    protected StorageInterface $storage;

    /**
     * @throws DebugBarException
     */
    public function __construct(DebugBar $debugBar)
    {
        $storage = $debugBar->getStorage();
        if (!$storage) {
            throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");
        }
        $this->debugBar = $debugBar;
        $this->storage = $storage;
    }

    /**
     * Handles the current request
     *
     * @param null|array<string, mixed> $request Request parameters
     *
     * @throws DebugBarException
     */
    public function handle(?array $request = null, bool $echo = true, bool $sendHeader = true): string
    {
        if ($request === null) {
            /** @var array<string, mixed> $request */
            $request = $_REQUEST;
        }

        $op = $request['op'] ?? null;
        if ($op === null || !is_string($request['op'])) {
            throw new DebugBarException("Missing operation parameter 'op' in request");
        }

        if (!$this->debugBar->getStorage()) {
            throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");
        }

        try {
            $response = match ($op) {
                'find' => $this->find($request),
                'get' => $this->get($request),
                'clear' => $this->clear(),
            };
        } catch (\UnhandledMatchError $e) {
            throw new DebugBarException("Invalid operation '{$request['op']}'");
        }

        $response = json_encode($response);
        if ($response === false) {
            throw new DebugBarException("Invalid JSON response");
        }

        if ($sendHeader) {
            $this->debugBar->getHttpDriver()->setHeaders([
                'Content-Type' => 'application/json',
            ]);
        }

        if ($echo) {
            $this->debugBar->getHttpDriver()->output($response);
        }

        return $response;
    }

    /**
     * Find operation
     *
     * @param array<string, mixed> $request
     *
     */
    protected function find(array $request): array
    {
        $max = 20;
        if (isset($request['max'])) {
            $max = (int) $request['max'];
        }

        $offset = 0;
        if (isset($request['offset'])) {
            $offset = (int) $request['offset'];
        }

        $filters = [];
        foreach (['ip', 'uri', 'method'] as $key) {
            if (isset($request[$key])) {
                $filters[$key] = $request[$key];
            }
        }

        return $this->storage->find($filters, $max, $offset);
    }

    /**
     * Get operation
     *
     * @param array<string, mixed> $request
     *
     * @throws DebugBarException
     */
    protected function get(array $request): array
    {
        if (!isset($request['id'])) {
            throw new DebugBarException("Missing 'id' parameter in 'get' operation");
        }
        return $this->storage->get((string) $request['id']);
    }

    /**
     * Clear operation
     */
    protected function clear(): array
    {
        $this->storage->clear();
        return ['success' => true];
    }
}
