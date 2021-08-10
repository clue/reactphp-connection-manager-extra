<?php

namespace ConnectionManager\Extra;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;
use React\Socket\ConnectorInterface;

class ConnectionManagerDelay implements ConnectorInterface
{
    /** @var ConnectorInterface */
    private $connectionManager;

    /** @var float */
    private $delay;

    /** @var LoopInterface */
    private $loop;

    /**
     * @param ConnectorInterface $connectionManager
     * @param float $delay
     * @param ?LoopInterface $loop
     */
    public function __construct(ConnectorInterface $connectionManager, $delay, LoopInterface $loop = null)
    {
        $this->connectionManager = $connectionManager;
        $this->delay = $delay;
        $this->loop = $loop ?: Loop::get();
    }

    public function connect($uri)
    {
        $connectionManager = $this->connectionManager;

        return Timer\resolve($this->delay, $this->loop)->then(function () use ($connectionManager, $uri) {
            return $connectionManager->connect($uri);
        });
    }
}
