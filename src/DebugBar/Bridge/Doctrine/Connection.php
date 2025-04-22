<?php

namespace DebugBar\Bridge\Doctrine;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;

class Connection extends AbstractConnectionMiddleware
{
    public function __construct(
        ConnectionInterface $connection,
        private DebugBarSQLMiddleware $debugStack,
    ) {
        parent::__construct($connection);
    }

    public function beginTransaction(): void
    {
        $this->debugStack->addQuery(fn () => parent::beginTransaction(), 'Begin Transaction', null, 'transaction');
    }

    public function commit(): void
    {
        $this->debugStack->addQuery(fn () => parent::commit(), 'Commit Transaction', null, 'transaction');
    }

    public function rollBack(): void
    {
        $this->debugStack->addQuery(fn () => parent::commit(), 'Rollback Transaction', null, 'transaction');
    }

    public function prepare(string $sql): StatementInterface
    {
        return new Statement(parent::prepare($sql), $sql, $this->debugStack);
    }

    public function query(string $sql): Result
    {
        return $this->debugStack->addQuery(fn () => parent::query($sql), $sql);
    }
}
