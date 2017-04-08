<?php

namespace ConnectionManager\Extra;

use React\Socket\ConnectorInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;
use Exception;

class ConnectionManagerTimeout implements ConnectorInterface
{
    private $connectionManager;
    private $timeout;
    private $loop;

    public function __construct(ConnectorInterface $connectionManager, $timeout, LoopInterface $loop)
    {
        $this->connectionManager = $connectionManager;
        $this->timeout = $timeout;
        $this->loop = $loop;
    }

    public function connect($uri)
    {
        $promise = $this->connectionManager->connect($uri);

        return Timer\timeout($promise, $this->timeout, $this->loop)->then(null, function ($e) use ($promise) {
            // connection successfully established but timeout already expired => close successful connection
            $promise->then(function ($connection) {
                $connection->end();
            });

            throw $e;
        });
    }
}
