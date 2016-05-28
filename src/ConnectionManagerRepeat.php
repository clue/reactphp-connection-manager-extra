<?php

namespace ConnectionManager\Extra;

use React\SocketClient\ConnectorInterface;
use InvalidArgumentException;
use Exception;
use React\Promise\Promise;
use React\Promise\CancellablePromiseInterface;

class ConnectionManagerRepeat implements ConnectorInterface
{
    protected $connectionManager;
    protected $maximumRepetitions;

    public function __construct(ConnectorInterface $connectionManager, $maximumRepetitons)
    {
        if ($maximumRepetitons < 1) {
            throw new InvalidArgumentException('Maximum number of repetitions must be >= 1');
        }
        $this->connectionManager = $connectionManager;
        $this->maximumRepetitions = $maximumRepetitons;
    }

    public function create($host, $port)
    {
        return $this->tryConnection($this->maximumRepetitions, $host, $port);
    }

    public function tryConnection($repeat, $host, $port)
    {
        $tries = $repeat + 1;
        $connector = $this->connectionManager;

        return new Promise(function ($resolve, $reject) use ($host, $port, &$pending, &$tries, $connector) {
            $try = function ($error = null) use (&$try, &$pending, &$tries, $host, $port, $connector, $resolve, $reject) {
                if ($tries > 0) {
                    --$tries;
                    $pending = $connector->create($host, $port);
                    $pending->then($resolve, $try);
                } else {
                    $reject(new Exception('Connection still fails even after repeating', 0, $error));
                }
            };

            $try();
        }, function ($_, $reject) use (&$pending, &$tries) {
            // stop retrying, reject results and cancel pending attempt
            $tries = 0;
            $reject(new \RuntimeException('Cancelled'));

            if ($pending instanceof CancellablePromiseInterface) {
                $pending->cancel();
            }
        });
    }
}
