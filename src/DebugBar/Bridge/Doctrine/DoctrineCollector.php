<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge\Doctrine;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DebugBarException;

/**
 * Collects Doctrine queries
 *
 * http://doctrine-project.org
 *
 * Uses a middleware to collects data about queries since Doctrine 3.x
 *
 * <code>
 * $config->setMiddlewares([$debugBarSQLMiddleware]);
 * $entityManager = new EntityManager($conn, $config);
 * $debugbar->addCollector(new DoctrineCollector($debugBarSQLMiddleware));
 * </code>
 */
class DoctrineCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $debugStack;

    /**
     * DoctrineCollector constructor.
     * @param DebugBarSQLMiddleware $debugStack
     * @throws DebugBarException
     */
    public function __construct(DebugBarSQLMiddleware $debugStack)
    {
        $this->debugStack = $debugStack;
    }

    /**
     * @return array
     */
    public function collect()
    {
        $queries = array();
        $totalExecTime = 0;
        foreach ($this->debugStack->queries as $q) {
            $queries[] = array(
                'sql' => $q['sql'],
                'params' => (object) $this->getParameters($q['params'] ?? []),
                'duration' => $q['executionMS'],
                'duration_str' => $this->formatDuration($q['executionMS'])
            );
            $totalExecTime += $q['executionMS'];
        }

        return array(
            'nb_statements' => count($queries),
            'accumulated_duration' => $totalExecTime,
            'accumulated_duration_str' => $this->formatDuration($totalExecTime),
            'statements' => $queries
        );
    }

    /**
     * Returns an array of parameters used with the query
     *
     * @return array
     */
    public function getParameters($params) : array
    {
        return array_map(function ($param) {
            if (is_string($param)) {
                return htmlentities($param, ENT_QUOTES, 'UTF-8', false);
            } elseif (is_array($param)) {
                return '[' . implode(', ', $this->getParameters($param)) . ']';
            } elseif (is_numeric($param)) {
                return strval($param);
            } elseif ($param instanceof \DateTimeInterface) {
                return $param->format('Y-m-d H:i:s');
            } elseif (is_object($param)) {
                return json_encode($param);
            }
            return $param ?: '';
        }, $params);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'doctrine';
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return array(
            "database" => array(
                "icon" => "arrow-right",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "doctrine",
                "default" => "[]"
            ),
            "database:badge" => array(
                "map" => "doctrine.nb_statements",
                "default" => 0
            )
        );
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return array(
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js'
        );
    }
}
