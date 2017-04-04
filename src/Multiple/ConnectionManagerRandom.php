<?php

namespace ConnectionManager\Extra\Multiple;

class ConnectionManagerRandom extends ConnectionManagerConsecutive
{
    public function connect($uri)
    {
        $managers = $this->managers;
        shuffle($managers);

        return $this->tryConnection($managers, $uri);
    }
}
