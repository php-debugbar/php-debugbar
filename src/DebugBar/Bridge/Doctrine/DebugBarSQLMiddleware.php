<?php

namespace DebugBar\Bridge\Doctrine;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware;

class DebugBarSQLMiddleware implements Middleware
{
    public array $queries = [];
    private static $logger = null;

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this);
    }

    public static function useLogger(\Closure $logger): void
    {
        static::$logger = $logger;
    }

    /**
     * Measure the execution time of a callable and log it as a query.
     */
    public function addQuery(callable $callable, string $sql, ?array $params = null, ?string $type = null): mixed
    {
        $start = microtime(true);
        $result = $callable();
        $executionMS = (microtime(true) - $start) * 1000;

        $query = compact('sql', 'params', 'executionMS', 'type');
        if (static::$logger) {
            (static::$logger)($query);
        } else {
            $this->queries[] = $query;
        }

        return $result;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }
}
