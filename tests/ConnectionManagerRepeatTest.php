<?php

use ConnectionManager\Extra\ConnectionManagerRepeat;
use ConnectionManager\Extra\ConnectionManagerReject;
use React\Promise;

class ConnectionManagerRepeatTest extends TestCase
{
    public function testRepeatRejected()
    {
        $wont = new ConnectionManagerReject();
        $cm = new ConnectionManagerRepeat($wont, 3);
        $promise = $cm->create('www.google.com', 80);

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testTwoTriesWillStartTwoConnectionAttempts()
    {
        $promise = Promise\reject(new \RuntimeException('nope'));

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->exactly(2))->method('create')->with('google.com', 80)->willReturn($promise);

        $cm = new ConnectionManagerRepeat($connector, 2);

        $promise = $cm->create('google.com', 80);

        $this->assertPromiseReject($promise);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidRepetitions()
    {
        $wont = new ConnectionManagerReject();
        $cm = new ConnectionManagerRepeat($wont, -3);
    }

    public function testCancellationWillNotStartAnyFurtherConnections()
    {
        $pending = new Promise\Promise(function () { }, $this->expectCallableOnce());

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('google.com', 80)->willReturn($pending);

        $cm = new ConnectionManagerRepeat($connector, 3);

        $promise = $cm->create('google.com', 80);
        $promise->cancel();
    }

    public function testCancellationWillNotStartAnyFurtherConnectionsIfPromiseRejectsOnCancellation()
    {
        $pending = new Promise\Promise(function () { }, function () {
            throw new \RuntimeException('cancelled');
        });

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('google.com', 80)->willReturn($pending);

        $cm = new ConnectionManagerRepeat($connector, 3);

        $promise = $cm->create('google.com', 80);
        $promise->cancel();
    }
}
