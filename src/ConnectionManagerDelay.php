<?php

namespace ConnectionManager\Extra;

use React\SocketClient\ConnectorInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;

class ConnectionManagerDelay implements ConnectorInterface
{
    private $connectionManager;
    private $loop;
    private $delay;

    public function __construct(ConnectorInterface $connectionManager, LoopInterface $loop, $delay)
    {
        $this->connectionManager = $connectionManager;
        $this->loop = $loop;
        $this->delay = $delay;
    }

    public function create($host, $port)
    {
        $connectionManager = $this->connectionManager;

        return Timer\resolve($this->delay, $this->loop)->then(function () use ($connectionManager, $host, $port) {
            return $connectionManager->create($host, $port);
        });
    }
}
