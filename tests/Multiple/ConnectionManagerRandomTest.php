<?php

use ConnectionManager\Extra\Multiple\ConnectionManagerRandom;
use ConnectionManager\Extra\ConnectionManagerReject;
use React\Promise;

class ConnectionManagerRandomTest extends TestCase
{
    public function testEmpty()
    {
        $cm = new ConnectionManagerRandom();

        $promise = $cm->create('www.google.com', 80);

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testReject()
    {
        $wont = new ConnectionManagerReject();

        $cm = new ConnectionManagerRandom();
        $cm->addConnectionManager($wont);

        $promise = $cm->create('www.google.com', 80);

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testWillTryAllIfEachRejects()
    {
        $rejected = Promise\reject(new \RuntimeException('nope'));

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->exactly(2))->method('create')->with('google.com', 80)->willReturn($rejected);

        $cm = new ConnectionManagerRandom();
        $cm->addConnectionManager($connector);
        $cm->addConnectionManager($connector);

        $promise = $cm->create('google.com', 80);

        $this->assertPromiseReject($promise);
    }

    public function testCancellationWillNotStartAnyFurtherConnections()
    {
        $pending = new Promise\Promise(function () { }, $this->expectCallableOnce());

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('google.com', 80)->willReturn($pending);

        $cm = new ConnectionManagerRandom();
        $cm->addConnectionManager($connector);
        $cm->addConnectionManager($connector);

        $promise = $cm->create('google.com', 80);
        $promise->cancel();
    }
}
