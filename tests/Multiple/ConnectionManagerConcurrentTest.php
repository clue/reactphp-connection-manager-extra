<?php

use ConnectionManager\Extra\Multiple\ConnectionManagerConcurrent;
use React\Promise;

class ConnectionManagerConcurrentTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyListsThrows()
    {
        new ConnectionManagerConcurrent(array());
    }

    public function testWillForwardToInnerConnector()
    {
        $pending = new Promise\Promise(function() { });

        $only = $this->getMock('React\SocketClient\ConnectorInterface');
        $only->expects($this->once())->method('create')->with('google.com', 80)->willReturn($pending);

        $connector = new ConnectionManagerConcurrent(array($only));

        $promise = $connector->create('google.com', 80);

        $promise->then($this->expectCallableNever(), $this->expectCallableNever());
    }

    public function testWillCancelOtherIfOneResolves()
    {
        $resolved = Promise\resolve($this->getMock('React\Stream\DuplexStreamInterface'));
        $first = $this->getMock('React\SocketClient\ConnectorInterface');
        $first->expects($this->once())->method('create')->with('google.com', 80)->willReturn($resolved);

        $pending = new Promise\Promise(function() { }, $this->expectCallableOnce());
        $second = $this->getMock('React\SocketClient\ConnectorInterface');
        $second->expects($this->once())->method('create')->with('google.com', 80)->willReturn($pending);

        $connector = new ConnectionManagerConcurrent(array($first, $second));

        $promise = $connector->create('google.com', 80);

        $this->assertPromiseResolve($promise);
    }

    public function testWillCloseOtherIfOneResolves()
    {
        $resolved = Promise\resolve($this->getMock('React\Stream\DuplexStreamInterface'));
        $first = $this->getMock('React\SocketClient\ConnectorInterface');
        $first->expects($this->once())->method('create')->with('google.com', 80)->willReturn($resolved);

        $slower = $this->getMock('React\Stream\DuplexStreamInterface');
        $slower->expects($this->once())->method('close');
        $second = $this->getMock('React\SocketClient\ConnectorInterface');
        $second->expects($this->once())->method('create')->with('google.com', 80)->willReturn(Promise\resolve($slower));

        $connector = new ConnectionManagerConcurrent(array($first, $second));

        $promise = $connector->create('google.com', 80);

        $this->assertPromiseResolve($promise);
    }
}
