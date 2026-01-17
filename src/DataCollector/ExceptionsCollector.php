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

use Throwable;

/**
 * Collects info about exceptions
 */
class ExceptionsCollector extends DataCollector implements Renderable, Resettable
{
    protected string $name = 'exceptions';
    protected string $icon = 'bug';
    protected array $exceptions = [];
    protected array $existingWarnings = [];
    protected bool $chainExceptions = false;

    public function __construct(string $name = 'exceptions', string $icon = 'bug')
    {
        $this->name = $name;
        $this->icon = $icon;
    }

    public function reset(): void
    {
        $this->exceptions = [];
        $this->existingWarnings = [];
    }
    /**
     * Adds an exception to be profiled in the debug bar. Same as addThrowable
     */
    public function addException(\Throwable $e): void
    {
        $this->addThrowable($e);
    }

    /**
     * Adds a Throwable to be profiled in the debug bar
     */
    public function addThrowable(\Throwable $e): void
    {
        $this->exceptions[] = $e;
        if ($this->chainExceptions && $previous = $e->getPrevious()) {
            $this->addThrowable($previous);
        }
    }

    /**
     * Configure whether or not all chained exceptions should be shown.
     */
    public function setChainExceptions(bool $chainExceptions = true): void
    {
        $this->chainExceptions = $chainExceptions;
    }

    /**
     * Start collecting warnings, notices and deprecations
     */
    public function collectWarnings(bool $preserveOriginalHandler = true): void
    {
        $originalHandler = $preserveOriginalHandler ? set_error_handler(null) : null;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($originalHandler): mixed {
            $this->addWarning($errno, $errstr, $errfile, $errline);

            if ($originalHandler) {
                return call_user_func($originalHandler, $errno, $errstr, $errfile, $errline);
            }

            return false;
        });
    }

    /**
     * Adds an warning to be profiled in the debug bar
     */
    public function addWarning(int $errno, string $errstr, string $errfile = '', int $errline = 0): void
    {
        $hash = md5("{$errno}-{$errstr}-{$errfile}-{$errline}");
        if (isset($this->existingWarnings[$hash])) {
            $this->existingWarnings[$hash]['count']++;

            return;
        }

        $errorTypes = [
            1    => 'E_ERROR',
            2    => 'E_WARNING',
            4    => 'E_PARSE',
            8    => 'E_NOTICE',
            16   => 'E_CORE_ERROR',
            32   => 'E_CORE_WARNING',
            64   => 'E_COMPILE_ERROR',
            128  => 'E_COMPILE_WARNING',
            256  => 'E_USER_ERROR',
            512  => 'E_USER_WARNING',
            1024 => 'E_USER_NOTICE',
            2048 => 'E_STRICT',
            4096 => 'E_RECOVERABLE_ERROR',
            8192 => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED',
        ];

        $warning = [
            'count' => 1,
            'type' => $errorTypes[$errno] ?? 'UNKNOWN',
            'message' => $errstr,
            'code' => $errno,
            'file' => $this->normalizeFilePath($errfile),
            'line' => $errline,
            'xdebug_link' => $this->getXdebugLink($errfile, $errline),
        ];
        $this->exceptions[] = &$warning;
        $this->existingWarnings[$hash] = &$warning;
    }

    /**
     * Returns the list of exceptions being profiled
     *
     * @return array<Throwable|array>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function collect(): array
    {
        return [
            'count' => count($this->exceptions),
            'exceptions' => array_map([$this, 'formatThrowableData'], $this->exceptions),
        ];
    }

    /**
     * Returns Throwable trace as an formated array
     */
    public function formatTrace(array $trace): array
    {
        if ($this->xdebugReplacements) {
            $trace = array_map(function ($track): mixed {
                if (isset($track['file'])) {
                    $track['file'] = $this->normalizeFilePath($track['file']);
                }
                return $track;
            }, $trace);
        }

        // Remove large objects from the trace
        $trace = array_map(function ($track): mixed {
            if (isset($track['args'])) {
                foreach ($track['args'] as $key => $arg) {
                    if (is_object($arg)) {
                        $track['args'][$key] = '[object ' . $this->getDataFormatter()->formatClassName($arg) . ']';
                    }
                }
            }
            return $track;
        }, $trace);

        return $trace;
    }

    /**
     * Returns Throwable data as an string
     */
    public function formatTraceAsString(\Throwable $e): string
    {
        if ($this->xdebugReplacements) {
            return implode("\n", array_map(function ($track): string {
                $track = explode(' ', $track);
                if (isset($track[1])) {
                    $track[1] = $this->normalizeFilePath($track[1]);
                }

                return implode(' ', $track);
            }, explode("\n", $e->getTraceAsString())));
        }

        return $e->getTraceAsString();
    }

    /**
     * Returns Throwable data as an array
     */
    public function formatThrowableData(\Throwable|array $e): array
    {
        if (is_array($e)) {
            return $e;
        }

        $filePath = $e->getFile();
        if ($filePath && file_exists($filePath)) {
            $lines = file($filePath);
            $start = $e->getLine() - 4;
            $lines = $lines ? array_slice($lines, $start < 0 ? 0 : $start, 7) : [];
        } else {
            $lines = ['Cannot open the file (' . $this->normalizeFilePath($filePath) . ') in which the exception occurred'];
        }

        $traceHtml = null;
        $trace = $e->getTrace();
        if ($trace && $this->isHtmlVarDumperUsed()) {
            $traceHtml = $this->getDataFormatter()->formatVar($this->formatTrace($trace));
        }

        return [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $this->normalizeFilePath($filePath),
            'line' => $e->getLine(),
            'stack_trace' => $traceHtml ? null : $this->formatTraceAsString($e),
            'stack_trace_html' => $traceHtml,
            'surrounding_lines' => $lines,
            'xdebug_link' => $this->getXdebugLink($filePath, $e->getLine()),
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
                'icon' => $this->icon,
                'widget' => 'PhpDebugBar.Widgets.ExceptionsWidget',
                'map' => "$name.exceptions",
                'default' => '[]',
            ],
            "$name:badge" => [
                'map' => "$name.count",
                'default' => 'null',
            ],
        ];
    }
}
