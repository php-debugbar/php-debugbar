<?php

namespace DebugBar\Bridge\Doctrine;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

class Driver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface $driver,
        private DebugBarSQLMiddleware $debugStack,
    ) {
        parent::__construct($driver);
    }

    /**
     * {@inheritDoc}
     */
    public function connect(array $params): Connection
    {
        return $this->debugStack->addQuery(
            fn () => new Connection(parent::connect($params), $this->debugStack),
            'Connection Established',
            null,
            'transaction'
        );
    }
}
