<?php

namespace ConnectionManager\Extra;

use React\SocketClient\ConnectorInterface;
use React\Promise\When;
use \Exception;

// a simple connection manager that rejects every single connection attempt
class ConnectionManagerReject implements ConnectorInterface
{
    public function create($host, $port)
    {
        return When::reject(new Exception('Connection rejected'));
    }
}
