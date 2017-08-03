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

    public function testRejectThrowsCustomException()
    {
        $cm = new ConnectionManagerReject(function ($uri) {
            throw new RuntimeException('Blocked ' . $uri);
        });

        $promise = $cm->connect('www.google.com:80');

        $exception = null;
        $promise->then($this->expectCallableNever(), function ($e) use (&$exception) {
            $exception = $e;
        });

        $this->assertInstanceOf('RuntimeException', $exception);
        $this->assertEquals('Blocked www.google.com:80', $exception->getMessage());
    }

    public function testRejectReturnsCustomException()
    {
        $cm = new ConnectionManagerReject(function ($uri) {
            return new RuntimeException('Blocked ' . $uri);
        });

        $promise = $cm->connect('www.google.com:80');

        $exception = null;
        $promise->then($this->expectCallableNever(), function ($e) use (&$exception) {
            $exception = $e;
        });

        $this->assertInstanceOf('RuntimeException', $exception);
        $this->assertEquals('Blocked www.google.com:80', $exception->getMessage());
    }
}
