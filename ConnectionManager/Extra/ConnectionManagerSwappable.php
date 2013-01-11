<?php

namespace ConnectionManager\Extra;

use ConnectionManager\ConnectionManagerInterface;

// connection manager decorator which simplifies exchanging the actual connection manager during runtime
class ConnectionManagerSwappable implements ConnectionManagerInterface
{
    protected $connectionManager;

    public function __construct(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function getConnection($host, $port)
    {
        return $this->connectionManager->getConnection($host, $port);
    }

    public function setConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }
}
