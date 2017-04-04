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
    protected $maximumTries;

    public function __construct(ConnectorInterface $connectionManager, $maximumTries)
    {
        if ($maximumTries < 1) {
            throw new InvalidArgumentException('Maximum number of tries must be >= 1');
        }
        $this->connectionManager = $connectionManager;
        $this->maximumTries = $maximumTries;
    }

    public function connect($uri)
    {
        $tries = $this->maximumTries;
        $connector = $this->connectionManager;

        return new Promise(function ($resolve, $reject) use ($uri, &$pending, &$tries, $connector) {
            $try = function ($error = null) use (&$try, &$pending, &$tries, $uri, $connector, $resolve, $reject) {
                if ($tries > 0) {
                    --$tries;
                    $pending = $connector->connect($uri);
                    $pending->then($resolve, $try);
                } else {
                    $reject(new Exception('Connection still fails even after retrying', 0, $error));
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
