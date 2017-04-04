<?php

use ConnectionManager\Extra\ConnectionManagerReject;

class ConnectionManagerRejectTest extends TestCase
{
    public function testReject()
    {
        $cm = new ConnectionManagerReject();
        $promise = $cm->connect('www.google.com:80');

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }
}
