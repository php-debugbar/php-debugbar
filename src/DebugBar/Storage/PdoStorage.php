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
class PdoStorage implements StorageInterface
{
    protected $pdo;

    protected $tableName;

    protected $sqlQueries = [
        'save' => "INSERT INTO %tablename% (id, data, meta_utime, meta_datetime, meta_uri, meta_ip, meta_method) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'get' => "SELECT data FROM %tablename% WHERE id = ?",
        'find' => "SELECT data FROM %tablename% %where% ORDER BY meta_datetime DESC LIMIT %limit% OFFSET %offset%",
        'clear' => "DELETE FROM %tablename%",
    ];

    /**
     * @param \PDO $pdo The PDO instance
     * @param string $tableName
     * @param array $sqlQueries
     */
    public function __construct(PDO $pdo, $tableName = 'phpdebugbar', array $sqlQueries = [])
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->setSqlQueries($sqlQueries);
    }

    /**
     * Sets the sql queries to be used
     *
     * @param array $queries
     */
    public function setSqlQueries(array $queries)
    {
        $this->sqlQueries = array_merge($this->sqlQueries, $queries);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data)
    {
        $sql = $this->getSqlQuery('save');
        $stmt = $this->pdo->prepare($sql);
        $meta = $data['__meta'];
        $stmt->execute([$id, serialize($data), $meta['utime'], $meta['datetime'], $meta['uri'], $meta['ip'], $meta['method']]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $sql = $this->getSqlQuery('get');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        if (($data = $stmt->fetchColumn(0)) !== false) {
            return unserialize($data);
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = [], $max = 20, $offset = 0)
    {
        $where = [];
        $params = [];
        foreach ($filters as $key => $value) {
            $where[] = "meta_$key = ?";
            $params[] = $value;
        }
        if (count($where)) {
            $where = " WHERE " . implode(' AND ', $where);
        } else {
            $where = '';
        }

        $sql = $this->getSqlQuery('find', [
            'where' => $where,
            'offset' => $offset,
            'limit' => $max,
        ]);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $data = unserialize($row['data']);
            $results[] = $data['__meta'];
            unset($data);
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->pdo->exec($this->getSqlQuery('clear'));
    }

    /**
     * Get a SQL Query for a task, with the variables replaced
     *
     * @param  string $name
     * @param  array  $vars
     * @return string
     */
    protected function getSqlQuery($name, array $vars = [])
    {
        $sql = $this->sqlQueries[$name];
        $vars = array_merge(['tablename' => $this->tableName], $vars);
        foreach ($vars as $k => $v) {
            $sql = str_replace("%$k%", $v, $sql);
        }
        return $sql;
    }
}
