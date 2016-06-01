<?php

namespace ConnectionManager\Extra;

use React\SocketClient\ConnectorInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;

class ConnectionManagerDelay implements ConnectorInterface
{
    private $connectionManager;
    private $delay;
    private $loop;

    public function __construct(ConnectorInterface $connectionManager, $delay, LoopInterface $loop)
    {
        $this->connectionManager = $connectionManager;
        $this->delay = $delay;
        $this->loop = $loop;
    }

    public function create($host, $port)
    {
        $connectionManager = $this->connectionManager;

        return Timer\resolve($this->delay, $this->loop)->then(function () use ($connectionManager, $host, $port) {
            return $connectionManager->create($host, $port);
        });
    }
}
