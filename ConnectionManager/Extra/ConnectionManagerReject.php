<?php

namespace ConnectionManager\Extra;

use ConnectionManager\ConnectionManagerInterface;
use React\Promise\When;
use \Exception;

// a simple connection manager that rejects every single connection attempt
class ConnectionManagerReject implements ConnectionManagerInterface
{
    public function getConnection($host, $port)
    {
        return When::reject(new Exception('Connection rejected'));
    }
}
