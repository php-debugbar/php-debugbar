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

use ArrayAccess;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Resettable;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\Storage\StorageInterface;

/**
 * Main DebugBar object
 *
 * Manages data collectors. DebugBar provides an array-like access
 * to collectors by name.
 *
 * <code>
 *     $debugbar = new DebugBar();
 *     $debugbar->addCollector(new DataCollector\MessagesCollector());
 *     $debugbar['messages']->addMessage("foobar");
 * </code>
 *
 * @implements ArrayAccess<string, DataCollectorInterface>
 */
class DebugBar implements ArrayAccess
{
    public static bool $useOpenHandlerWhenSendingDataHeaders = false;

    /** @var DataCollectorInterface[] */
    protected array $collectors = [];

    protected ?array $data = null;

    protected ?JavascriptRenderer $jsRenderer = null;

    protected ?RequestIdGeneratorInterface $requestIdGenerator = null;

    protected ?string $requestId = null;

    protected ?StorageInterface $storage = null;

    protected ?HttpDriverInterface $httpDriver = null;

    protected string $stackSessionNamespace = 'PHPDEBUGBAR_STACK_DATA';

    protected bool $useHtmlVarDumper = true;

    protected bool $stackAlwaysUseSessionStorage = false;

    protected ?string $editorTemplate = null;

    protected ?string $editorLinkTemplate = null;

    protected ?array $remotePathReplacements = null;

    /**
     * Adds a data collector
     *
     *
     * @throws DebugBarException
     *
     * @return $this
     */
    public function addCollector(DataCollectorInterface $collector): static
    {
        if ($collector->getName() === '__meta') {
            throw new DebugBarException("'__meta' is a reserved name and cannot be used as a collector name");
        }
        if (isset($this->collectors[$collector->getName()])) {
            throw new DebugBarException("'{$collector->getName()}' is already a registered collector");
        }
        if ($this->useHtmlVarDumper && method_exists($collector, 'useHtmlVarDumper')) {
            $collector->useHtmlVarDumper($this->useHtmlVarDumper);
        }
        if ($this->editorTemplate && method_exists($collector, 'setEditorLinkTemplate')) {
            $collector->setEditorLinkTemplate($this->editorTemplate);
        }
        if ($this->editorLinkTemplate && method_exists($collector, 'setXdebugLinkTemplate')) {
            $collector->setXdebugLinkTemplate($this->editorLinkTemplate);
        }
        if ($this->remotePathReplacements && method_exists($collector, 'setXdebugReplacements')) {
            $collector->setXdebugReplacements($this->remotePathReplacements);
        }
        $this->collectors[$collector->getName()] = $collector;
        return $this;
    }

    /**
     * Checks if a data collector has been added
     *
     *
     * @return boolean
     */
    public function hasCollector(string $name): bool
    {
        return isset($this->collectors[$name]);
    }

    public function getCollector(string $name): DataCollectorInterface
    {
        if (!isset($this->collectors[$name])) {
            throw new DebugBarException("'$name' is not a registered collector");
        }
        return $this->collectors[$name];
    }

    public function removeCollector(string $name): void
    {
        if (!isset($this->collectors[$name])) {
            throw new DebugBarException("'$name' is not a registered collector");
        }

        unset($this->collectors[$name]);
    }
    /**
     * Returns an array of all data collectors
     *
     * @return array|DataCollectorInterface[]
     */
    public function getCollectors(): array
    {
        return $this->collectors;
    }

    /**
     * Sets the request id generator
     *
     * @return $this
     */
    public function setRequestIdGenerator(RequestIdGeneratorInterface $generator): static
    {
        $this->requestIdGenerator = $generator;
        return $this;
    }

    public function getRequestIdGenerator(): RequestIdGeneratorInterface
    {
        if ($this->requestIdGenerator === null) {
            $this->requestIdGenerator = new RequestIdGenerator();
        }
        return $this->requestIdGenerator;
    }

    /**
     * Returns the id of the current request
     *
     */
    public function getCurrentRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = $this->getRequestIdGenerator()->generate();
        }
        return $this->requestId;
    }

    /**
     * Sets the storage backend to use to store the collected data
     *
     * @return $this
     */
    public function setStorage(?StorageInterface $storage = null): static
    {
        $this->storage = $storage;
        return $this;
    }

    public function getStorage(): ?StorageInterface
    {
        return $this->storage;
    }

    /**
     * Checks if the data will be persisted
     *
     * @return boolean
     */
    public function isDataPersisted(): bool
    {
        return $this->storage !== null;
    }

    /**
     * Sets the HTTP driver
     *
     * @return $this
     */
    public function setHttpDriver(HttpDriverInterface $driver): static
    {
        $this->httpDriver = $driver;
        return $this;
    }

    /**
     * Returns the HTTP driver
     *
     * If no http driver where defined, a PhpHttpDriver is automatically created
     *
     */
    public function getHttpDriver(): HttpDriverInterface
    {
        if ($this->httpDriver === null) {
            $this->httpDriver = new PhpHttpDriver();
        }
        return $this->httpDriver;
    }

    /**
     * Collects the data from the collectors
     *
     */
    public function collect(): array
    {
        if (php_sapi_name() === 'cli') {
            $ip = gethostname();
            if ($ip) {
                $ip = gethostbyname($ip);
            } else {
                $ip = '127.0.0.1';
            }
            $request_variables = [
                'method' => 'CLI',
                'uri' => isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : null,
                'ip' => $ip,
            ];
        } else {
            $request_variables = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ];
        }
        $this->data = [
            '__meta' => array_merge(
                [
                    'id' => $this->getCurrentRequestId(),
                    'datetime' => date('Y-m-d H:i:s'),
                    'utime' => microtime(true),
                ],
                $request_variables,
            ),
        ];

        $lateCollectors = [];
        foreach ($this->collectors as $name => $collector) {
            if ($collector instanceof TimeDataCollector) {
                $lateCollectors[$name] = $collector;
            } else {
                $this->data[$name] = $collector->collect();
            }
        }

        // Run TimeData collectors last to catch items added during collection
        foreach ($lateCollectors as $name => $collector) {
            $this->data[$name] = $collector->collect();
        }

        // Remove all invalid (non UTF-8) characters
        array_walk_recursive($this->data, function (&$item): void {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        });

        if ($this->storage !== null) {
            $this->storage->save($this->getCurrentRequestId(), $this->data);
        }

        return $this->data;
    }

    /**
     * Returns collected data
     *
     * Will collect the data if none have been collected yet
     *
     */
    public function getData(): array
    {
        if ($this->data === null) {
            $this->collect();
        }
        return $this->data;
    }

    public function reset(): void
    {
        $this->requestId = null;
        $this->data = null;

        foreach ($this->collectors as $collector) {
            if ($collector instanceof Resettable) {
                $collector->reset();
            }
        }
    }

    /**
     * Returns an array of HTTP headers containing the data
     *
     * @param integer $maxHeaderLength
     *
     * @return array<string, string>
     *
     */
    public function getDataAsHeaders(string $headerName = 'phpdebugbar', int $maxHeaderLength = 4096, int $maxTotalHeaderLength = 250000): array
    {
        $data = rawurlencode(json_encode([
            'id' => $this->getCurrentRequestId(),
            'data' => $this->getData(),
        ]));

        if (strlen($data) > $maxTotalHeaderLength) {
            $data = rawurlencode(json_encode([
                'error' => 'Maximum header size exceeded',
            ]));
        }

        $chunks = [];

        while (strlen($data) > $maxHeaderLength) {
            $chunks[] = substr($data, 0, $maxHeaderLength);
            $data = substr($data, $maxHeaderLength);
        }
        $chunks[] = $data;

        $headers = [];
        for ($i = 0, $c = count($chunks); $i < $c; $i++) {
            $name = $headerName . ($i > 0 ? "-$i" : '');
            $headers[$name] = $chunks[$i];
        }

        return $headers;
    }

    /**
     * Sends the data through the HTTP headers
     *
     * @param integer $maxHeaderLength
     *
     * @return $this
     */
    public function sendDataInHeaders(?bool $useOpenHandler = null, string $headerName = 'phpdebugbar', int $maxHeaderLength = 4096): static
    {
        if ($useOpenHandler === null) {
            $useOpenHandler = self::$useOpenHandlerWhenSendingDataHeaders;
        }
        if ($useOpenHandler && $this->isDataPersisted()) {
            $this->getData();
            $headers = ["{$headerName}-id" => $this->getCurrentRequestId()];

            // Only send stacked data in ajax if storage is used
            if (!$this->stackAlwaysUseSessionStorage && $this->hasStackedData()) {
                $stackIds = $this->getStackedIds();
                if (count($stackIds) > 0) {
                    $headers["{$headerName}-stack"] = json_encode($stackIds);
                }
            }
        } else {
            $headers = $this->getDataAsHeaders($headerName, $maxHeaderLength);
        }
        $this->getHttpDriver()->setHeaders($headers);
        return $this;
    }

    /**
     * Stacks the data in the session for later rendering
     */
    public function stackData(): static
    {
        $http = $this->initStackSession();

        $data = null;
        if (!$this->isDataPersisted() || $this->stackAlwaysUseSessionStorage) {
            $data = $this->getData();
        } elseif ($this->data === null) {
            $this->collect();
        }

        $stack = $http->getSessionValue($this->stackSessionNamespace);
        $stack[$this->getCurrentRequestId()] = $data;
        $http->setSessionValue($this->stackSessionNamespace, $stack);
        return $this;
    }

    /**
     * Checks if there is stacked data in the session
     *
     * @return boolean
     */
    public function hasStackedData(): bool
    {
        try {
            $stackedData = $this->getStackedValue(false);
        } catch (DebugBarException $e) {
            return false;
        }

        return count($stackedData) > 0;
    }

    /**
     * @throws DebugBarException
     */
    protected function getStackedValue(bool $delete = true): array
    {
        $http = $this->initStackSession();
        $stackedData = $http->getSessionValue($this->stackSessionNamespace);
        if ($delete) {
            $http->deleteSessionValue($this->stackSessionNamespace);
        }

        if (!is_array($stackedData)) {
            return [];
        }

        return $stackedData;
    }

    /**
     * Returns the data stacked in the session
     *
     * @param boolean $delete Whether to delete the data in the session
     *
     * @return array[]
     *
     */
    public function getStackedData(bool $delete = true): array
    {
        $stackedData = $this->getStackedValue($delete);

        $datasets = [];
        if ($this->isDataPersisted() && !$this->stackAlwaysUseSessionStorage) {
            foreach ($stackedData as $id => $data) {
                $datasets[$id] = $this->getStorage()->get($id);
            }
        } else {
            $datasets = $stackedData;
        }

        return array_filter($datasets);
    }

    public function getStackedIds(bool $delete = true): array
    {
        $stackedData = $this->getStackedValue($delete);

        return array_keys($stackedData);
    }

    /**
     * Sets the key to use in the $_SESSION array
     *
     *
     * @return $this
     */
    public function setStackDataSessionNamespace(string $ns): static
    {
        $this->stackSessionNamespace = $ns;
        return $this;
    }

    /**
     * Returns the key used in the $_SESSION array
     *
     */
    public function getStackDataSessionNamespace(): string
    {
        return $this->stackSessionNamespace;
    }

    /**
     * Sets whether to only use the session to store stacked data even
     * if a storage is enabled
     *
     * @param boolean $enabled
     *
     * @return $this
     */
    public function setStackAlwaysUseSessionStorage(bool $enabled = true): static
    {
        $this->stackAlwaysUseSessionStorage = $enabled;
        return $this;
    }

    /**
     * Checks if the session is always used to store stacked data
     * even if a storage is enabled
     *
     * @return boolean
     */
    public function isStackAlwaysUseSessionStorage(): bool
    {
        return $this->stackAlwaysUseSessionStorage;
    }

    /**
     * Initializes the session for stacked data
     *
     *
     * @throws DebugBarException
     */
    protected function initStackSession(): HttpDriverInterface
    {
        $http = $this->getHttpDriver();
        if (!$http->isSessionStarted()) {
            throw new DebugBarException("Session must be started before using stack data in the debug bar");
        }

        if (!$http->hasSessionValue($this->stackSessionNamespace)) {
            $http->setSessionValue($this->stackSessionNamespace, []);
        }

        return $http;
    }

    /**
     * Returns a JavascriptRenderer for this instance
     *
     */
    public function getJavascriptRenderer(?string $baseUrl = null, ?string $basePath = null): JavascriptRenderer
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }
        return $this->jsRenderer;
    }

    /**
     * Set the editor globally, e.g., `vscode`
     */
    public function setEditor(string $editor): void
    {
        $this->editorTemplate = $editor;
        $this->editorLinkTemplate = null;

        foreach ($this->collectors as $collector) {
            if (method_exists($collector, 'setEditorLinkTemplate')) {
                $collector->setEditorLinkTemplate($this->editorTemplate);
            }
        }
    }

    /**
     * Set the editor link template globally,
     * `%f` = file, `%l` = line, e.g., `vscode://file/%f:%l`
     */
    public function setEditorTemplate(string $editorLinkTemplate, bool $shouldUseAjax = false): void
    {
        $this->editorTemplate = null;
        $this->editorLinkTemplate = !$shouldUseAjax ? $editorLinkTemplate
            : "javascript:(()=>{let r=new XMLHttpRequest;r.open('get','{$editorLinkTemplate}');r.send();})()";

        foreach ($this->collectors as $collector) {
            if (method_exists($collector, 'setXdebugLinkTemplate')) {
                $collector->setXdebugLinkTemplate($this->editorLinkTemplate);
            }
        }
    }

    /**
     * Set server path replacements, server paths will be mapped to local paths
     * e.g., `['/var/www/remote/' => '/home/local/']`,
     * '/var/www/remote/app/path' will become to '/home/local/app/path'
     */
    public function setRemoteReplacements(array $remotePathReplacements): void
    {
        $this->remotePathReplacements = $remotePathReplacements;

        foreach ($this->collectors as $collector) {
            if (method_exists($collector, 'setXdebugReplacements')) {
                $collector->setXdebugReplacements($this->remotePathReplacements);
            }
        }
    }

    // --------------------------------------------
    // ArrayAccess implementation

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new DebugBarException("DebugBar[] is read-only");
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getCollector($offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->hasCollector($offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->hasCollector($offset)) {
            $this->removeCollector($offset);
        }
    }
}
