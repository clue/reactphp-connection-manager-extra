<?php

namespace ConnectionManager\Extra\Multiple;

class ConnectionManagerRandom extends ConnectionManagerConsecutive
{
    public function getConnection($host, $port)
    {
        $managers = $this->managers;
        shuffle($managers);
        
        return $this->tryConnection($managers, $host, $port);
    }
}
