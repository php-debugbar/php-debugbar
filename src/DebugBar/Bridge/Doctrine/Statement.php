<?php

namespace DebugBar\Bridge\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

class Statement extends AbstractStatementMiddleware
{
    /** @var array<mixed> */
    public array $params = [];

    public function __construct(
        StatementInterface $statement,
        private string $sql,
        private DebugBarSQLMiddleware $debugStack,
    ) {
        parent::__construct($statement);
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        $this->params[$param] = $value;

        parent::bindValue($param, $value, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function execute($params = null): Result
    {
        return $this->debugStack->addQuery(fn () => parent::execute($params), $this->sql, $this->params);
    }
}
