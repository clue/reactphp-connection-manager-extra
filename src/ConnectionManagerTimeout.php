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
    public function __construct(ConnectorInterface $connectionManager, $timeout, $loop = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { // manual type check to support legacy PHP < 7.1
            throw new \InvalidArgumentException('Argument #3 ($loop) expected null|React\EventLoop\LoopInterface');
        }

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
