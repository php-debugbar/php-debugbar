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

namespace DebugBar\Storage;

use PDO;

/**
 * Stores collected data into a database using PDO
 */
class PdoStorage extends AbstractStorage
{
    protected PDO $pdo;

    protected string $tableName;

    protected array $sqlQueries = [
        'save' => "INSERT INTO %tablename% (id, data, meta_utime, meta_datetime, meta_uri, meta_ip, meta_method) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'get' => "SELECT data FROM %tablename% WHERE id = ?",
        'find' => "SELECT data FROM %tablename% %where% ORDER BY meta_datetime DESC LIMIT %limit% OFFSET %offset%",
        'clear' => "DELETE FROM %tablename%",
    ];

    public function __construct(
        PDO $pdo,
        string $tableName = 'phpdebugbar'
    ) {
        $this->pdo = $pdo;
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            throw new \InvalidArgumentException('Invalid table name: ' . $tableName);
        }
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $id, array $data): void
    {
        $stmt = $this->pdo->prepare($this->getSqlQuery('save'));

        $meta = $data['__meta'];
        $stmt->execute([
            $id,
            json_encode($data),
            $meta['utime'],
            $meta['datetime'] ?? null,
            $meta['uri'] ?? null,
            $meta['ip'] ?? null,
            $meta['method'] ?? null,
        ]);

        $this->autoPrune();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): array
    {
        $stmt = $this->pdo->prepare($this->getSqlQuery('get'));
        $stmt->execute([$id]);

        if (($data = $stmt->fetchColumn(0)) !== false) {
            return json_decode($data, true) ?: [];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = [], int $max = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];

        $max = max(1, min(200, $max));
        $offset = max(0, $offset);

        foreach ($filters as $key => $value) {
            if (in_array($key, ['datetime', 'uri', 'ip', 'method'], true)) {
                $where[] = "meta_$key = ?";
                $params[] = $value;
            } elseif ($key === 'utime') {
                $where[] = "meta_utime > ?";
                $params[] = $value;
            }
        }

        $where = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            $this->getSqlQuery('find', [
                'where' => $where,
                'offset' => (string) $offset,
                'limit' => (string) $max,
            ])
        );
        $stmt->execute($params);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data = json_decode($row['data'], true);
            if ($data !== false && isset($data['__meta'])) {
                $results[] = $data['__meta'];
            }

            unset($data);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->pdo->exec($this->getSqlQuery('clear'));
    }

    /**
     * Gets the number of entries in storage
     */
    public function count(): int
    {
        $result = $this->pdo->query("SELECT COUNT(*) FROM {$this->tableName}");
        return (int) $result->fetchColumn(0);
    }

    public function prune(int $hours = 24): void
    {
        // Delete entries older than lifetime
        $cutoffTime = microtime(true) - $hours * 3600;
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE meta_utime <= ?");
        $stmt->execute([$cutoffTime]);
    }

    /**
     * Gets the PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Gets the table name
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
    /**
     *
     * Sets the sql queries to be used
     *
     */
    public function setSqlQueries(array $queries): void
    {
        $this->sqlQueries = array_merge($this->sqlQueries, $queries);
    }

    /**
     * Get a SQL Query for a task, with the variables replaced
     *
     *
     */
    protected function getSqlQuery(string $name, array $vars = []): string
    {
        $sql = $this->sqlQueries[$name];
        $vars = array_merge(['tablename' => $this->getTableName()], $vars);
        foreach ($vars as $k => $v) {
            $sql = str_replace("%$k%", $v, $sql);
        }
        return $sql;
    }

    /**
     * Creates the table and indexes if they don't exist
     */
    protected function createTable(): void
    {
        $tableName = $this->getTableName();

        $this->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$tableName} (
            id TEXT PRIMARY KEY,
            data TEXT,
            meta_utime DOUBLE,
            meta_datetime TEXT,
            meta_uri TEXT,
            meta_ip TEXT,
            meta_method TEXT
        )");

        // Create indexes for better query performance
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_utime ON {$tableName} (meta_utime)");
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_datetime ON {$tableName} (meta_datetime)");
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_uri ON {$tableName} (meta_uri)");
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_ip ON {$tableName} (meta_ip)");
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_{$tableName}_method ON {$tableName} (meta_method)");
    }
}
