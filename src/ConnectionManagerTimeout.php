<?php

namespace ConnectionManager\Extra;

use React\SocketClient\ConnectorInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;
use Exception;

class ConnectionManagerTimeout implements ConnectorInterface
{
    private $connectionManager;
    private $loop;
    private $timeout;

    public function __construct(ConnectorInterface $connectionManager, LoopInterface $loop, $timeout)
    {
        $this->connectionManager = $connectionManager;
        $this->loop = $loop;
        $this->timeout = $timeout;
    }

    public function create($host, $port)
    {
        $promise = $this->connectionManager->create($host, $port);

        return Timer\timeout($promise, $this->timeout, $this->loop)->then(null, function ($e) use ($promise) {
            // connection successfully established but timeout already expired => close successful connection
            $promise->then(function ($connection) {
                $connection->end();
            });

            throw $e;
        });
    }
}
