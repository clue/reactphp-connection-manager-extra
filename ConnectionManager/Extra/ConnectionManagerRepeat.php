<?php

namespace ConnectionManager\Extra;

use ConnectionManager\ConnectionManagerInterface;
use \InvalidArgumentException;
use React\Promise\Deferred;
use \Exception;

class ConnectionManagerRepeat implements ConnectionManagerInterface
{
    protected $connectionManager;
    protected $maximumRepetitions;
    
    public function __construct(ConnectionManagerInterface $connectionManager, $maximumRepetitons)
    {
        if ($maximumRepetitons < 1) {
            throw new InvalidArgumentException('Maximum number of repetitions must be >= 1');
        }
        $this->connectionManager = $connectionManager;
        $this->maximumRepetitions = $maximumRepetitons;
    }
    
    public function getConnection($host, $port)
    {
        return $this->tryConnection($this->maximumRepetitions, $host, $port);
    }
    
    public function tryConnection($repeat, $host, $port)
    {
        $that = $this;
        return $this->connectionManager->getConnection($host, $port)->then(
            null,
            function ($error) use ($repeat, $that) {
                if ($repeat > 0) {
                    return $that->tryConnection($repeat - 1, $host, $port);
                } else {
                    throw new Exception('Connection still fails even after repeating', 0, $error);
                }
            }
        );
    }
}
