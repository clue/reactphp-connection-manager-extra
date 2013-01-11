<?php

class ConnectionManagerConsecutive implements ConnectionManagerInterface
{
    private $managers = array();
    
    public function addConnectionManager(ConnectionManagerInterface $connectionManager)
    {
        $this->managers []= $connectionManager;
    }
    
    public function getConnection($host, $port)
    {
        return tryConnection($this->managers, $host, $port);   
    }
    
    /**
     * 
     * @param ConnectionManagerInterface[] $managers
     * @param string $host
     * @param int $port
     * @return Promise
     * @internal
     */
    public function tryConnection(array $managers, $host, $port)
    {
        if (!$managers) {
            return When::reject(new UnderflowException('No more managers to try to connect through'));
        }
        $manager = array_shift($managers);
        $that = $this;
        return $manager->getConnection($host,$port)->then(null, function() use ($that, $managers, $host, $port) {
            // connection failed, re-try with remaining connection managers
            return $that->tryConnection($managers, $host, $port);
        });
    }
}
