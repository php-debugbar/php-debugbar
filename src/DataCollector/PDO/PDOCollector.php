<?php

declare(strict_types=1);

namespace DebugBar\DataCollector\PDO;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\HasTimeDataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataFormatter\QueryFormatter;

/**
 * Collects data about SQL statements executed with PDO
 */
class PDOCollector extends DataCollector implements Renderable, AssetProvider
{
    use HasTimeDataCollector;

    /** @var array<string, TraceablePDO> */
    protected array $connections = [];

    protected ?QueryFormatter $queryFormatter = null;

    protected bool $renderSqlWithParams = true;

    protected bool $durationBackground = true;

    protected ?float $slowThreshold = null;

    protected ?int $backtraceLimit = null;

    public function __construct(?\PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->addConnection($pdo, 'default');
        }
    }

    public function reset(): void
    {
        foreach ($this->connections as $pdo) {
            $pdo->resetExecutedStatements();
        }
    }

    public function getQueryFormatter(): QueryFormatter
    {
        if ($this->queryFormatter === null) {
            $this->queryFormatter = new QueryFormatter();
        }
        return $this->queryFormatter;
    }

    /**
     * Set the backtrace limit to check for PDO statements. Set to null to disable.
     */
    public function enableBacktrace(?int $backtraceLimit = 10): void
    {
        $this->backtraceLimit = $backtraceLimit;
        foreach ($this->connections as $pdo) {
            $pdo->enableBacktrace($backtraceLimit);
        }
    }

    /**
     * Renders the SQL of traced statements with params embeded
     */
    public function setRenderSqlWithParams(bool $enabled = true): void
    {
        $this->renderSqlWithParams = $enabled;
    }

    /**
     * Enable/disable the shaded duration background on queries
     */
    public function setDurationBackground(bool $enabled): void
    {
        $this->durationBackground = $enabled;
    }

    /**
     * Highlights queries that exceed the threshold
     *
     * @param int|float $threshold miliseconds value
     */
    public function setSlowThreshold(int|float $threshold): void
    {
        $this->slowThreshold = $threshold / 1000;
    }

    public function isSqlRenderedWithParams(): bool
    {
        return $this->renderSqlWithParams;
    }

    /**
     * Adds a new PDO instance to be collector
     */
    public function addConnection(\PDO $pdo, ?string $name = null): void
    {
        if ($name === null) {
            $name = spl_object_hash($pdo);
        }
        if (!($pdo instanceof TraceablePDO)) {
            $pdo = new TraceablePDO($pdo);
        }
        $pdo->enableBacktrace($this->backtraceLimit);
        $this->connections[$name] = $pdo;
    }

    /**
     * Returns PDO instances to be collected
     *
     * @return array<string, TraceablePDO>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    public function collect(): array
    {
        $data = [
            'nb_statements' => 0,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0,
            'memory_usage' => 0,
            'peak_memory_usage' => 0,
            'statements' => [],
        ];

        foreach ($this->connections as $name => $pdo) {
            $pdodata = $this->collectPDO($pdo, $name);
            $data['nb_statements'] += $pdodata['nb_statements'];
            $data['nb_failed_statements'] += $pdodata['nb_failed_statements'];
            $data['accumulated_duration'] += $pdodata['accumulated_duration'];
            $data['memory_usage'] += $pdodata['memory_usage'];
            $data['peak_memory_usage'] = max($data['peak_memory_usage'], $pdodata['peak_memory_usage']);
            $data['statements'] = array_merge(
                $data['statements'],
                array_map(function ($s) use ($name): mixed {
                    $s['connection'] = $name;
                    return $s;
                }, $pdodata['statements']),
            );
        }

        $data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);
        $data['memory_usage_str'] = $this->getDataFormatter()->formatBytes($data['memory_usage']);
        $data['peak_memory_usage_str'] = $this->getDataFormatter()->formatBytes($data['peak_memory_usage']);

        return $data;
    }

    /**
     * Collects data from a single TraceablePDO instance
     */
    protected function collectPDO(TraceablePDO $pdo, ?string $connectionName = null): array
    {
        if (!$connectionName || $connectionName === 'default') {
            $connectionName = 'pdo';
        } else {
            $connectionName = 'pdo ' . $connectionName;
        }
        $stmts = [];
        foreach ($pdo->getExecutedStatements() as $stmt) {

            $sql = $stmt->getSql();
            $params = $stmt->getParameters();
            if ($this->renderSqlWithParams) {
                $sql = $this->getQueryFormatter()->formatSqlWithBindings($sql, $params, $pdo);
            } else {
                $sql = $this->getQueryFormatter()->formatSql($sql);
            }

            $backtrace = $stmt->getBacktrace();
            $source = $backtrace ? (object) reset($backtrace) : null;
            $stmts[] = [
                'sql' => $sql,
                'type' => $stmt->getQueryType(),
                'row_count' => $stmt->getRowCount(),
                'params' => (object) $stmt->getParameters(),
                'duration' => $stmt->getDuration(),
                'duration_str' => $this->getDataFormatter()->formatDuration($stmt->getDuration()),
                'memory' => $stmt->getMemoryUsage(),
                'memory_str' => $this->getDataFormatter()->formatBytes($stmt->getMemoryUsage()),
                'end_memory' => $stmt->getEndMemory(),
                'end_memory_str' => $this->getDataFormatter()->formatBytes($stmt->getEndMemory()),
                'is_success' => $stmt->isSuccess(),
                'error_code' => $stmt->getErrorCode(),
                'error_message' => $stmt->getErrorMessage(),
                'backtrace' => $backtrace,
                'filename' => $source ? $this->getQueryFormatter()->formatSource($source, true) : null,
                'xdebug_link' => $source ? $this->getXdebugLink($source->file ?: '', $source->line) : null,
                'slow' => $this->slowThreshold && $this->slowThreshold <= $stmt->getDuration(),
            ];
            if ($this->hasTimeDataCollector()) {
                $this->addTimeMeasure($stmt->getSql(), $stmt->getStartTime(), $stmt->getEndTime(), [], $connectionName);
            }
        }

        $totalTime = $pdo->getAccumulatedStatementsDuration();
        if ($this->durationBackground && $totalTime > 0) {
            // For showing background measure on Queries tab
            $start_percent = 0;
            foreach ($stmts as $i => $stmt) {
                $width_percent = $stmt['duration'] / $totalTime * 100;
                $stmts[$i] = array_merge($stmt, [
                    'start_percent' => round($start_percent, 3),
                    'width_percent' => round($width_percent, 3),
                ]);
                $start_percent += $width_percent;
            }
        }

        return [
            'nb_statements' => count($stmts),
            'nb_failed_statements' => count($pdo->getFailedExecutedStatements()),
            'accumulated_duration' => $totalTime,
            'accumulated_duration_str' => $this->getDataFormatter()->formatDuration($totalTime),
            'memory_usage' => $pdo->getMemoryUsage(),
            'memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'peak_memory_usage' => $pdo->getPeakMemoryUsage(),
            'peak_memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'statements' => $stmts,
        ];
    }

    public function getName(): string
    {
        return 'pdo';
    }

    public function getWidgets(): array
    {
        return [
            "database" => [
                "icon" => "database",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "pdo",
                "default" => "[]",
            ],
            "database:badge" => [
                "map" => "pdo.nb_statements",
                "default" => 0,
            ],
        ];
    }

    public function getAssets(): array
    {
        return [
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js',
        ];
    }
}
