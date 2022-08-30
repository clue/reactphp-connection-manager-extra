<?php

namespace ConnectionManager\Extra\Multiple;

use React\Promise;
use React\Promise\PromiseInterface;
use React\Socket\ConnectorInterface;
use UnderflowException;

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

    public function connect($uri)
    {
        return $this->tryConnection($this->managers, $uri);
    }

    /**
     *
     * @param ConnectorInterface[] $managers
     * @param string $uri
     * @return Promise
     * @internal
     */
    public function tryConnection(array $managers, $uri)
    {
        return new Promise\Promise(function ($resolve, $reject) use (&$managers, &$pending, $uri) {
            $try = function () use (&$try, &$managers, $uri, $resolve, $reject, &$pending) {
                if (!$managers) {
                    return $reject(new UnderflowException('No more managers to try to connect through'));
                }

                $manager = array_shift($managers);
                $pending = $manager->connect($uri);
                $pending->then($resolve, $try);
            };

            $try();
        }, function ($_, $reject) use (&$managers, &$pending) {
            // stop retrying, reject results and cancel pending attempt
            $managers = array();
            $reject(new \RuntimeException('Cancelled'));

            if ($pending instanceof PromiseInterface && \method_exists($pending, 'cancel')) {
                $pending->cancel();
            }
        });
    }
}
