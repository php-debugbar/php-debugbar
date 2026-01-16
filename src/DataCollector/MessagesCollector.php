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

namespace DebugBar\DataCollector;

use DebugBar\DataFormatter\HasXdebugLinks;
use Psr\Log\AbstractLogger;
use DebugBar\DataFormatter\HasDataFormatter;

/**
 * Provides a way to log messages
 */
class MessagesCollector extends AbstractLogger implements DataCollectorInterface, MessagesAggregateInterface, Renderable, Resettable
{
    use HasDataFormatter;
    use HasXdebugLinks;
    use HasTimeDataCollector;

    protected string $name;

    protected array $messages = [];

    /** @var array<MessagesAggregateInterface> */
    protected array $aggregates = [];
    protected bool $compactDumps = false;

    protected bool $collectFile = false;

    protected int $backtraceLimit = 15;

    /** @var array<string> */
    protected array $backtraceExcludePaths = ['/vendor/'];

    public function __construct(string $name = 'messages')
    {
        $this->name = $name;
    }

    public function reset(): void
    {
        $this->messages = [];

        foreach ($this->aggregates as $collector) {
            if ($collector instanceof Resettable) {
                $collector->reset();
            }
        }
    }

    public function compactDumps(bool $enabled = true): void
    {
        $this->compactDumps = $enabled;
    }

    public function collectFileTrace(bool $enabled = true): void
    {
        $this->collectFile = $enabled;
    }

    public function limitBacktrace(int $limit): void
    {
        $this->backtraceLimit = $limit;
    }

    /**
     * Set paths to exclude from the backtrace
     */
    public function addBacktraceExcludePaths(array $excludePaths): void
    {
        $this->backtraceExcludePaths = array_merge($this->backtraceExcludePaths, $excludePaths);
    }

    /**
     * Check if the given file is to be excluded from analysis
     */
    protected function fileIsInExcludedPath(string $file): bool
    {
        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($this->backtraceExcludePaths as $excludedPath) {
            if (str_contains($normalizedPath, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    protected function compactMessageDump(?string $messageHtml): ?string
    {
        $pos = strpos((string) $messageHtml, 'sf-dump-expanded');
        if ($pos !== false) {
            $messageHtml = substr_replace($messageHtml, 'sf-dump-compact', $pos, 16);
        }

        return $messageHtml;
    }

    protected function getStackTraceItem(array $stacktrace): array
    {
        foreach ($stacktrace as $trace) {
            if (!isset($trace['file']) || $this->fileIsInExcludedPath($trace['file'])) {
                continue;
            }

            return $trace;
        }

        return $stacktrace[0];
    }

    /**
     * Adds a message
     *
     * A message can be anything from an object to a string
     */
    public function addMessage(mixed $message, string $label = 'info', array $context = []): void
    {
        // For string messages, interpolate the context following PSR-3
        if (is_string($message) && $context) {
            $message = $this->interpolate($message, $context);
        }

        $messageText = $message;
        $messageHtml = null;
        $isString = true;
        if (!is_string($message)) {
            // Send both text and HTML representations; the text version is used for searches
            $messageText = $this->getDataFormatter()->formatVar($message);
            if ($this->isHtmlVarDumperUsed()) {
                $messageHtml = $messageText;
                if ($this->compactDumps) {
                    $messageHtml = $this->compactMessageDump($messageHtml);
                }
                $messageText = strip_tags($messageHtml);
            }
            $isString = false;
        }

        if ($context) {
            foreach ($context as $key => $value) {
                $context[$key] = $this->getDataFormatter()->formatVar($value);
            }
        } else {
            $context = null;
        }

        $stackItem = [];
        if ($this->collectFile) {
            $stackItem = $this->getStackTraceItem(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->backtraceLimit));
        }

        $this->messages[] = [
            'message' => $messageText,
            'message_html' => $messageHtml,
            'is_string' => $isString,
            'context' => $context,
            'label' => $label,
            'time' => microtime(true),
            'xdebug_link' => $stackItem ? $this->getXdebugLink($stackItem['file'], $stackItem['line'] ?? null) : null,
        ];

        if ($this->hasTimeDataCollector()) {
            $this->addTimeMeasure("[{$label}]: " . substr($messageText, 0, 100), null, microtime(true));
        }

    }

    /**
     * Aggregates messages from other collectors
     */
    public function aggregate(MessagesAggregateInterface $messages): void
    {
        if ($this->collectFile && method_exists($messages, 'collectFileTrace')) {
            $messages->collectFileTrace();
        }

        $this->aggregates[] = $messages;
    }

    public function getMessages(): array
    {
        $messages = $this->messages;
        foreach ($this->aggregates as $collector) {
            $msgs = array_map(function ($m) use ($collector): array {
                $m['collector'] = $collector->getName();
                return $m;
            }, $collector->getMessages());
            $messages = array_merge($messages, $msgs);
        }

        // sort messages by their timestamp
        usort($messages, function ($a, $b): int {
            if ($a['time'] === $b['time']) {
                return 0;
            }
            return $a['time'] < $b['time'] ? -1 : 1;
        });

        return $messages;
    }

    public function log(mixed $level, mixed $message, array $context = []): void
    {
        $this->addMessage($message, $level, $context);
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    public function interpolate(string $message, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (!str_contains($message, $placeholder)) {
                continue;
            }
            // check that the value can be cast to string
            if (null === $val || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                $replace[$placeholder] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replace[$placeholder] = $val->format("Y-m-d\TH:i:s.uP");
            } elseif ($val instanceof \UnitEnum) {
                $replace[$placeholder] = $val instanceof \BackedEnum ? $val->value : $val->name;
            } elseif (is_object($val)) {
                $replace[$placeholder] = '[object ' . $this->getDataFormatter()->formatClassName($val) . ']';
            } elseif (is_array($val)) {
                $json = @json_encode($val);
                $replace[$placeholder] = false === $json ? 'null' : 'array' . $json;
            } else {
                $replace[$placeholder] = '[' . gettype($val) . ']';
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Deletes all messages
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    public function collect(): array
    {
        $messages = $this->getMessages();
        return [
            'count' => count($messages),
            'messages' => $messages,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            "$name" => [
                'icon' => 'logs',
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "$name.messages",
                "default" => "[]",
            ],
            "$name:badge" => [
                "map" => "$name.count",
                "default" => "null",
            ],
        ];
    }
}
