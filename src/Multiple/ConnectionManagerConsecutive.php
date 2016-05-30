<?php

namespace ConnectionManager\Extra\Multiple;

use React\SocketClient\ConnectorInterface;
use React\Promise;
use UnderflowException;
use React\Promise\CancellablePromiseInterface;

class ConnectionManagerConsecutive implements ConnectorInterface
{
    protected $managers;

    /**
     *
     * @param ConnectorInterface[] $managers
     */
    public function __construct(array $managers)
    {
        if (!$managers) {
            throw new \InvalidArgumentException('List of connectors must not be empty');
        }
        $this->managers = $managers;
    }

    public function create($host, $port)
    {
        return $this->tryConnection($this->managers, $host, $port);
    }

    /**
     *
     * @param ConnectorInterface[] $managers
     * @param string $host
     * @param int $port
     * @return Promise
     * @internal
     */
    public function tryConnection(array $managers, $host, $port)
    {
        return new Promise\Promise(function ($resolve, $reject) use (&$managers, &$pending, $host, $port) {
            $try = function () use (&$try, &$managers, $host, $port, $resolve, $reject, &$pending) {
                if (!$managers) {
                    return $reject(new UnderflowException('No more managers to try to connect through'));
                }

                $manager = array_shift($managers);
                $pending = $manager->create($host, $port);
                $pending->then($resolve, $try);
            };

            $try();
        }, function ($_, $reject) use (&$managers, &$pending) {
            // stop retrying, reject results and cancel pending attempt
            $managers = array();
            $reject(new \RuntimeException('Cancelled'));

            if ($pending instanceof CancellablePromiseInterface) {
                $pending->cancel();
            }
        });
    }
}
