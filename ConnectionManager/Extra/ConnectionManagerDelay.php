<?php

namespace ConnectionManager\Extra;

use ConnectionManager\ConnectionManagerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class ConnectionManagerDelay implements ConnectionManagerInterface
{
    private $connectionManager;
    private $loop;
    private $delay;
    
    public function __construct(ConnectionManagerInterface $connectionManager, LoopInterface $loop, $delay)
    {
        $this->connectionManager = $connectionManager;
        $this->loop = $loop;
        $this->delay = $delay;
    }
    
    public function getConnection($host, $port)
    {
        $deferred = new Deferred();
        
        $connectionManager = $this->connectionManager;
        $this->loop->addTimeout($this->delay, function() use ($deferred, $connectionManager, $host, $port) {
            $connectionManager->getConnect($host, $port)->then(
                array($deferred, 'resolve'),
                array($deferred, 'reject')
            );
        });
        return $deferred->promise();
    }
}
