<?php

namespace ConnectionManager\Extra;

use React\Socket\ConnectorInterface;

// connection manager decorator which simplifies exchanging the actual connection manager during runtime
class ConnectionManagerSwappable implements ConnectorInterface
{
    protected $connectionManager;

    public function __construct(ConnectorInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function connect($uri)
    {
        return $this->connectionManager->connect($uri);
    }

    public function setConnectionManager(ConnectorInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }
}
