<?php

namespace DebugBar\Bridge\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\ServerVersionProvider;

class DebugBarSQLMiddleware implements Middleware
{
    public $queries = [];

    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this) implements Driver {
            private $wrapped;
            private $middleware;

            public function __construct(Driver $wrapped, DebugBarSQLMiddleware $middleware)
            {
                $this->wrapped = $wrapped;
                $this->middleware = $middleware;
            }

            public function connect(array $params): Connection
            {
                $conn = $this->wrapped->connect($params);
                return new class($conn, $this->middleware) implements Connection {
                    private $conn;
                    private $middleware;

                    public function __construct(Connection $conn, DebugBarSQLMiddleware $middleware)
                    {
                        $this->conn = $conn;
                        $this->middleware = $middleware;
                    }

                    public function prepare(string $sql): Statement
                    {
                        $stmt = $this->conn->prepare($sql);
                        return new class($stmt, $sql, $this->middleware) implements Statement {
                            private $stmt;
                            private $sql;
                            private $middleware;
                            private $params = [];

                            public function __construct(Statement $stmt, string $sql, DebugBarSQLMiddleware $middleware)
                            {
                                $this->stmt = $stmt;
                                $this->sql = $sql;
                                $this->middleware = $middleware;
                            }

                            public function bindValue($param, $value, $type = null): void
                            {
                                $this->params[$param] = $value;
                                $this->stmt->bindValue($param, $value, $type);
                            }

                            public function execute(?array $params = null): Result
                            {
                                $allParams = $params ?? $this->params;

                                $start = microtime(true);
                                $result = $this->stmt->execute($params);
                                $end = microtime(true);

                                $this->middleware->addQuery($this->sql, $allParams, $end - $start);
                                return $result;
                            }

                            public function __call($name, $arguments)
                            {
                                return $this->stmt->$name(...$arguments);
                            }
                        };
                    }

                    public function query(string $sql): Result
                    {
                        return $this->conn->query($sql);
                    }

                    public function exec(string $sql): int|string
                    {
                        return $this->conn->exec($sql);
                    }

                    public function beginTransaction(): void
                    {
                        $this->conn->beginTransaction();

                        $this->middleware->addQuery('"START TRANSACTION"');
                    }

                    public function commit(): void
                    {
                        $this->conn->commit();

                        $this->middleware->addQuery('"COMMIT"');
                    }

                    public function rollBack(): void
                    {
                        $this->conn->rollBack();

                        $this->middleware->addQuery('"ROLLBACK"');
                    }

                    public function quote(string $value): string
                    {
                        return $this->conn->quote($value);
                    }

                    public function lastInsertId(): int|string
                    {
                        return $this->conn->lastInsertId();
                    }

                    public function getServerVersion(): string
                    {
                        return $this->conn->getServerVersion();
                    }

                    public function getNativeConnection()
                    {
                        return $this->conn->getNativeConnection();
                    }

                };
            }

            public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
            {
                return $this->wrapped->getDatabasePlatform($versionProvider);
            }

            public function getExceptionConverter(): ExceptionConverter
            {
                return $this->wrapped->getExceptionConverter();
            }
        };
    }

    public function addQuery(string $sql, array $params = [], float $executionTime = 0.0)
    {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'executionMS' => $executionTime * 1000,
        ];
    }

    public function getQueries(): array
    {
        return $this->queries;
    }
}
