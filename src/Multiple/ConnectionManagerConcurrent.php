<?php

namespace ConnectionManager\Extra\Multiple;

use ConnectionManager\Extra\Multiple\ConnectionManagerConsecutive;
use React\Promise;
use React\Promise\CancellablePromiseInterface;

class ConnectionManagerConcurrent extends ConnectionManagerConsecutive
{
    public function connect($uri)
    {
        $all = array();
        foreach ($this->managers as $connector) {
            /* @var $connection Connector */
            $all []= $connector->connect($uri);
        }
        return Promise\any($all)->then(function ($conn) use ($all) {
            // a connection attempt succeeded
            // => cancel all pending connection attempts
            foreach ($all as $promise) {
                if ($promise instanceof CancellablePromiseInterface) {
                    $promise->cancel();
                }

                // if promise resolves despite cancellation, immediately close stream
                $promise->then(function ($stream) use ($conn) {
                    if ($stream !== $conn) {
                        $stream->close();
                    }
                });
            }
            return $conn;
        });
    }
}
