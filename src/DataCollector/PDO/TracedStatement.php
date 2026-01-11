<?php

declare(strict_types=1);

namespace DebugBar\DataCollector\PDO;

/**
 * Holds information about a statement
 */
class TracedStatement
{
    protected string $sql;

    protected ?string $type = null;

    protected int $rowCount;

    protected array $parameters;

    protected float $startTime;

    protected float $endTime;

    protected float $duration;

    protected int $startMemory;

    protected int $endMemory;

    protected int $memoryDelta;

    protected ?\Exception $exception;

    protected ?string $preparedId;

    protected ?array $backtrace = null;

    public function __construct(string $sql, array $params = [], ?string $preparedId = null)
    {
        $this->sql = $sql;
        $this->parameters = $this->checkParameters($params);
        $this->preparedId = $preparedId;
    }

    public function setQueryType(string $type): void
    {
        $this->type = $type;
    }

    public function start(?float $startTime = null, ?int $startMemory = null): void
    {
        $this->startTime = $startTime ?: microtime(true);
        $this->startMemory = $startMemory ?: memory_get_usage(false);
    }

    public function end(?\Exception $exception = null, int $rowCount = 0, ?float $endTime = null, ?int $endMemory = null): void
    {
        $this->endTime = $endTime ?: microtime(true);
        $this->duration = $this->endTime - $this->startTime;
        $this->endMemory = $endMemory ?: memory_get_usage(false);
        $this->memoryDelta = $this->endMemory - $this->startMemory;
        $this->exception = $exception;
        $this->rowCount = $rowCount;
    }

    public function checkBacktrace(int $limit = 15): void
    {
        $offset = 3;    // Internal calls
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit + $offset);
        $stack = array_filter($stack, function ($frame): bool {
            return isset($frame['file']) && !str_contains($frame['file'], '/DebugBar/DataCollector/PDO/');
        });

        $this->backtrace = array_slice($stack, 0, $limit);
    }

    /**
     * Check parameters for illegal (non UTF-8) strings, like Binary data.
     *
     */
    public function checkParameters(array $params): array
    {
        foreach ($params as &$param) {
            if ((is_string($param) || is_array($param)) && !mb_check_encoding($param, 'UTF-8')) {
                $param = '[BINARY DATA]';
            }
        }
        return $params;
    }

    public function getBacktrace(): ?array
    {
        return $this->backtrace;
    }

    /**
     * Returns the SQL string used for the query, without filled parameters
     *
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Returns the number of rows affected/returned
     *
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Returns an array of parameters used with the query
     *
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns the prepared statement id
     *
     */
    public function getPreparedId(): ?string
    {
        return $this->preparedId;
    }

    /**
     * Checks if this is a prepared statement
     *
     * @return boolean
     */
    public function isPrepared(): bool
    {
        return $this->preparedId !== null;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getEndTime(): float
    {
        return $this->endTime;
    }

    /**
     * Returns the duration in seconds + microseconds of the execution
     *
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getStartMemory(): int
    {
        return $this->startMemory;
    }

    public function getEndMemory(): int
    {
        return $this->endMemory;
    }

    /**
     * Returns the memory usage during the execution
     *
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryDelta;
    }

    /**
     * Checks if the statement was successful
     *
     * @return boolean
     */
    public function isSuccess(): bool
    {
        return $this->exception === null;
    }

    /**
     * Returns the exception triggered
     *
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }

    /**
     * Returns the exception's code
     */
    public function getErrorCode(): int|string
    {
        return $this->exception !== null ? $this->exception->getCode() : 0;
    }

    /**
     * Returns the exception's message
     *
     */
    public function getErrorMessage(): string
    {
        return $this->exception !== null ? $this->exception->getMessage() : '';
    }

    /**
     * Returns the query type
     *
     */
    public function getQueryType(): string
    {
        return $this->type !== null ? $this->type : '';
    }
}
