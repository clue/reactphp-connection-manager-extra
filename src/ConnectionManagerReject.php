<?php

namespace ConnectionManager\Extra;

use React\Socket\ConnectorInterface;
use React\Promise;
use Exception;

// a simple connection manager that rejects every single connection attempt
class ConnectionManagerReject implements ConnectorInterface
{
    private $reason = 'Connection rejected';

    /**
     *
     * @param ?string $reason
     */
    public function __construct($reason = null)
    {
        if ($reason !== null) {
            $this->reason = $reason;
        }
    }

    public function connect($_)
    {
        return Promise\reject(new Exception($this->reason));
    }
}
