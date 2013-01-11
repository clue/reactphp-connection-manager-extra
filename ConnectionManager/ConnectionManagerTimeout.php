<?php

class ConnectionManagerTimeout implements ConnectionManagerInterface
{
    private $connectionManager;
    private $loop;
    private $timeout;
    
    public function __construct(ConnectionManagerInterface $connectionManager, LoopInterface $loop, $timeout)
    {
        $this->connectionManager = $connectionManager;
        $this->loop = $loop;
        $this->timeout = $timeout;
    }
    
    public function getConnection($host, $port)
    {
        $deferred = new Deferred();
        $timedout = false;
        
        $tid = $this->loop->addTimeout($this->timeout, function() use ($deferred, &$timedout) {
            $deferred->reject(new Exception('Connection attempt timed out'));
            $timedout = true;
            // TODO: find a proper way to actually cancel the connection
        });
        
        $loop = $this->loop;
        $this->connectionManager->getConnection($host, $port)->then(function ($connection) use ($tid, $loop, &$timedout, $deferred) {
            if ($timedout) {
                // connection successfully established but timeout already expired => close successful connection
                $connection->end();
            } else {
                $loop->removeTimeout($tid);
                $deferred->resolve($connection);
            }
        }, function ($error) use ($loop, $tid) {
            $loop->removeTimeout($tid);
            throw $error;
        });
        return $deferred->promise();
    }
}
