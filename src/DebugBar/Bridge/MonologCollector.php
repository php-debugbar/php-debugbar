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

namespace DebugBar\Bridge;

use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MessagesAggregateInterface;
use DebugBar\DataCollector\Renderable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * A monolog handler as well as a data collector
 *
 * https://github.com/Seldaek/monolog
 *
 * <code>
 * $debugbar->addCollector(new MonologCollector($logger));
 * </code>
 */
class MonologCollector extends AbstractProcessingHandler implements DataCollectorInterface, Renderable, MessagesAggregateInterface
{
    protected string $name;

    protected array $records = [];

    public function __construct(?Logger $logger = null, int|Level|string $level = Level::Debug, ?bool $bubble = true, string $name = 'monolog')
    {
        parent::__construct($level, $bubble);
        $this->name = $name;
        if ($logger !== null) {
            $this->addLogger($logger);
        }
    }

    public function addLogger(Logger $logger): void
    {
        $logger->pushHandler($this);
    }

    protected function write(array|\Monolog\LogRecord $record): void
    {
        $this->records[] = [
            'message' => $record['formatted'],
            'is_string' => true,
            'label' => strtolower($record['level_name']),
            'time' => $record['datetime']->format('U'),
        ];
    }

    public function getMessages(): array
    {
        return $this->records;
    }

    public function collect(): array
    {
        return [
            'count' => count($this->records),
            'records' => $this->records,
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
            $name => [
                "icon" => "briefcase",
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "$name.records",
                "default" => "[]",
            ],
            "$name:badge" => [
                "map" => "$name.count",
                "default" => "null",
            ],
        ];
    }
}
