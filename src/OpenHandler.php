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

        if ($op === 'summary') {
            $response = $this->summary($request);
            $contentType = 'text/plain; charset=utf-8';
        } else {
            try {
                $data = match ($op) {
                    'find' => $this->find($request),
                    'get' => $this->get($request),
                    'clear' => $this->clear(),
                };
            } catch (\UnhandledMatchError $e) {
                throw new DebugBarException("Invalid operation '{$request['op']}'");
            }

            $response = json_encode($data);
            if ($response === false) {
                throw new DebugBarException("Invalid JSON response");
            }
            $contentType = 'application/json';
        }

        if ($sendHeader) {
            $this->debugBar->getHttpDriver()->setHeaders([
                'Content-Type' => $contentType,
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
        foreach (['utime', 'ip', 'uri', 'method', 'rid'] as $key) {
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
     * Summary operation
     *
     * Returns a stored dataset as plain text rather than JSON, so tooling that can't
     * render the bar - a terminal, a CI job, an agent driving the app over HTTP - can
     * read what happened during a request. Omit 'id' to summarize the latest dataset.
     *
     * @param array<string, mixed> $request
     *
     * @throws DebugBarException
     */
    protected function summary(array $request): string
    {
        $id = isset($request['id']) ? (string) $request['id'] : $this->getLatestId();
        if ($id === null) {
            throw new DebugBarException("No dataset to summarize");
        }

        return $this->debugBar->getSummary($this->storage->get($id));
    }

    /**
     * Returns the id of the most recently stored dataset, if any.
     */
    protected function getLatestId(): ?string
    {
        $latest = $this->storage->find([], 1, 0);
        $meta = reset($latest);
        if (!is_array($meta)) {
            return null;
        }

        // Storage backends return metadata rows, but some return whole datasets
        $id = $meta['id'] ?? $meta['__meta']['id'] ?? null;

        return $id !== null ? (string) $id : null;
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
