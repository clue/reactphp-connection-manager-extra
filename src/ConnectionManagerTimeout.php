<?php

namespace ConnectionManager\Extra;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Timer;
use React\Socket\ConnectorInterface;

class ConnectionManagerTimeout implements ConnectorInterface
{
    /** @var ConnectorInterface */
    private $connectionManager;

    /** @var float */
    private $timeout;

    /** @var LoopInterface */
    private $loop;

    /**
     * @param ConnectorInterface $connectionManager
     * @param float $timeout
     * @param ?LoopInterface $loop
     */
    public function __construct(ConnectorInterface $connectionManager, $timeout, LoopInterface $loop = null)
    {
        $this->connectionManager = $connectionManager;
        $this->timeout = $timeout;
        $this->loop = $loop ?: Loop::get();
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
