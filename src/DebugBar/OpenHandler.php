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

use DebugBar\DataCollector\Actionable;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataFormatter\DataFormatter;
use DebugBar\DataFormatter\DataFormatterInterface;

/**
 * Handler to list and open saved dataset
 */
class OpenHandler
{
    protected DebugBar $debugBar;

    /**
     * @throws DebugBarException
     */
    public function __construct(DebugBar $debugBar)
    {
        if (!$debugBar->isDataPersisted()) {
            throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");
        }
        $this->debugBar = $debugBar;
    }

    /**
     * Handles the current request
     *
     *
     * @throws DebugBarException
     */
    public function handle(?array $request = null, bool $echo = true, bool $sendHeader = true): string
    {
        if ($request === null) {
            $request = $_REQUEST;
        }

        $op = 'find';
        if (isset($request['op'])) {
            $op = $request['op'];
            if (!in_array($op, ['find', 'get', 'clear', 'execute'])) {
                throw new DebugBarException("Invalid operation '{$request['op']}'");
            }
        }

        if ($sendHeader) {
            $this->debugBar->getHttpDriver()->setHeaders([
                'Content-Type' => 'application/json',
            ]);
        }

        $response = json_encode(call_user_func([$this, $op], $request));
        if ($echo) {
            echo $response;
        }

        return $response;
    }

    /**
     * Find operation
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
        foreach (['utime', 'datetime', 'ip', 'uri', 'method'] as $key) {
            if (isset($request[$key])) {
                $filters[$key] = $request[$key];
            }
        }

        return $this->debugBar->getStorage()->find($filters, $max, $offset);
    }

    /**
     * Get operation
     *
     *
     * @throws DebugBarException
     */
    protected function get(array $request): array
    {
        if (!isset($request['id'])) {
            throw new DebugBarException("Missing 'id' parameter in 'get' operation");
        }
        return $this->debugBar->getStorage()->get($request['id']);
    }

    /**
     * Clear operation
     */
    protected function clear(): array
    {
        $this->debugBar->getStorage()->clear();
        return ['success' => true];
    }

    /**
     * Execute an action
     * @param $request
     * @return mixed
     * @throws DebugBarException
     */
    protected function execute($request)
    {
        if (!isset($request['collector']) || !isset($request['action']) || !isset($request['signature'])) {
            throw new DebugBarException("Missing 'collector', 'action' and/or 'signature' parameter in 'execute' operation");
        }

        if (!DebugBar::hasDataHasher()) {
            throw new DebugBarException("Not DataHasher is set in DebugBar, which is required for 'execute' operations");
        }

        // Get the signature and remove if before checking the payload.
        $signature = $request['signature'];

        if (!DebugBar::getDataHasher()->verify($request, $signature)) {
            throw new DebugBarException("Signature does not match in 'execute' operation");
        }

        if (!$this->debugBar->hasCollector($request['collector'])) {
            throw new DebugBarException("Collector {$request['collector']} not found in 'execute' operation");
        }

        $collector = $this->debugBar->getCollector($request['collector']);
        if (!$collector instanceof Actionable) {
            throw new DebugBarException("Collector {$request['collector']} found in 'execute' operation does not implement the Actionable interface");
        }

        return $collector->executionAction($request['action'], $request['payload'] ?? null);
    }
}
