<?php

namespace ConnectionManager\Extra;

use React\Socket\ConnectorInterface;
use React\Promise;
use Exception;

// a simple connection manager that rejects every single connection attempt
class ConnectionManagerReject implements ConnectorInterface
{
    public function connect($_)
    {
        return Promise\reject(new Exception('Connection rejected'));
    }
}
