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

    public function testRejectWithCustomMessage()
    {
        $cm = new ConnectionManagerReject('Blocked');
        $promise = $cm->connect('www.google.com:80');

        $text = null;
        $promise->then($this->expectCallableNever(), function (Exception $e) use (&$text) {
            $text = $e->getMessage();
        });

        $this->assertEquals('Blocked', $text);
    }
}
