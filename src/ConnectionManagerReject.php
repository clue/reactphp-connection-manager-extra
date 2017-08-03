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
     * @param null|string|callable $reason
     */
    public function __construct($reason = null)
    {
        if ($reason !== null) {
            $this->reason = $reason;
        }
    }

    public function connect($uri)
    {
        $reason = $this->reason;
        if (!is_string($reason)) {
            try {
                $reason = $reason($uri);
            } catch (\Exception $e) {
                $reason = $e;
            }
        }

        if (!$reason instanceof \Exception) {
            $reason = new Exception($reason);
        }

        return Promise\reject($reason);
    }
}
