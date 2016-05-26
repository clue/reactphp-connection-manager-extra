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

    public function testOneRepetitionWillStartTwoConnectionAttempts()
    {
        $promise = Promise\reject(new \RuntimeException('nope'));

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->exactly(2))->method('create')->with('google.com', 80)->willReturn($promise);

        $cm = new ConnectionManagerRepeat($connector, 1);

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
}
