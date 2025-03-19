<?php
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
class MessagesCollector extends AbstractLogger implements DataCollectorInterface, MessagesAggregateInterface, Renderable, AssetProvider
{
    use HasDataFormatter, HasXdebugLinks;

    protected $name;

    protected $messages = array();

    protected $aggregates = array();

    /** @var bool */
    protected $collectFile = false;

    /** @var int */
    protected $backtraceLimit = 5;

    /** @var array */
    protected $backtraceExcludePaths = ['/vendor/'];

    /**
     * @param string $name
     */
    public function __construct($name = 'messages')
    {
        $this->name = $name;
    }

    /** @return void */
    public function collectFileTrace($enabled = true)
    {
        $this->collectFile = $enabled;
    }

    /**
     * @param int $limit
     *
     * @return void
     */
    public function limitBacktrace($limit)
    {
        $this->backtraceLimit = $limit;
    }

    /**
     * Set paths to exclude from the backtrace
     *
     * @param array $excludePaths Array of file paths to exclude from backtrace
     */
    public function addBacktraceExcludePaths($excludePaths)
    {
        $this->backtraceExcludePaths = array_merge($this->backtraceExcludePaths, $excludePaths);
    }

    /**
     * Check if the given file is to be excluded from analysis
     *
     * @param string $file
     * @return bool
     */
    protected function fileIsInExcludedPath($file)
    {
        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($this->backtraceExcludePaths as $excludedPath) {
            if (strpos($normalizedPath, $excludedPath) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|null $messageHtml
     * @param mixed $message
     *
     * @return string|null
     */
    protected function customizeMessageHtml($messageHtml, $message)
    {
        $pos = strpos((string) $messageHtml, 'sf-dump-expanded');
        if ($pos !== false) {
            $messageHtml = substr_replace($messageHtml, 'sf-dump-compact', $pos, 16);
        }

        return $messageHtml;
    }

    /**
     * @param array $stacktrace
     *
     * @return array
     */
    protected function getStackTraceItem($stacktrace)
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
     *
     * @param mixed $message
     * @param string $label
     */
    public function addMessage($message, $label = 'info', $isString = true)
    {
        $messageText = $message;
        $messageHtml = null;
        if (!is_string($message)) {
            // Send both text and HTML representations; the text version is used for searches
            $messageText = $this->getDataFormatter()->formatVar($message);
            if ($this->isHtmlVarDumperUsed()) {
                $messageHtml = $this->getVarDumper()->renderVar($message);
            }
            $isString = false;
        }

        $stackItem = [];
        if ($this->collectFile) {
            $stackItem = $this->getStackTraceItem(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->backtraceLimit));
        }

        $this->messages[] = array(
            'message' => $messageText,
            'message_html' => $this->customizeMessageHtml($messageHtml, $message),
            'is_string' => $isString,
            'label' => $label,
            'time' => microtime(true),
            'xdebug_link' => $stackItem ? $this->getXdebugLink($stackItem['file'], $stackItem['line'] ?? null) : null,
        );
    }

    /**
     * Aggregates messages from other collectors
     *
     * @param MessagesAggregateInterface $messages
     */
    public function aggregate(MessagesAggregateInterface $messages)
    {
        if ($this->collectFile && method_exists($messages, 'collectFileTrace')) {
            $messages->collectFileTrace();
        }

        $this->aggregates[] = $messages;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        $messages = $this->messages;
        foreach ($this->aggregates as $collector) {
            $msgs = array_map(function ($m) use ($collector) {
                $m['collector'] = $collector->getName();
                return $m;
            }, $collector->getMessages());
            $messages = array_merge($messages, $msgs);
        }

        // sort messages by their timestamp
        usort($messages, function ($a, $b) {
            if ($a['time'] === $b['time']) {
                return 0;
            }
            return $a['time'] < $b['time'] ? -1 : 1;
        });

        return $messages;
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     */
    public function log($level, $message, array $context = array()): void
    {
        // For string messages, interpolate the context following PSR-3
        if (is_string($message)) {
            $message = $this->interpolate($message, $context);
        }
        $this->addMessage($message, $level);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (strpos($message, $placeholder) === false) {
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
                $replace[$placeholder] = '['.gettype($val).']';
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Deletes all messages
     */
    public function clear()
    {
        $this->messages = array();
    }

    /**
     * @return array
     */
    public function collect()
    {
        $messages = $this->getMessages();
        return array(
            'count' => count($messages),
            'messages' => $messages
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $name = $this->getName();
        return array(
            "$name" => array(
                'icon' => 'list-alt',
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "$name.messages",
                "default" => "[]"
            ),
            "$name:badge" => array(
                "map" => "$name.count",
                "default" => "null"
            )
        );
    }
}
