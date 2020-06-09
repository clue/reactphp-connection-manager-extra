<?php

namespace ConnectionManager\Tests\Extra\Multiple;

use ConnectionManager\Extra\Multiple\ConnectionManagerConsecutive;
use ConnectionManager\Extra\ConnectionManagerReject;
use React\Promise;
use ConnectionManager\Tests\Extra\TestCase;

class ConnectionManagerConsecutiveTest extends TestCase
{
    public function testEmptyListThrows()
    {
        $this->setExpectedException("InvalidArgumentException");
        new ConnectionManagerConsecutive(array());
    }

    public function testReject()
    {
        $wont = new ConnectionManagerReject();

        $cm = new ConnectionManagerConsecutive(array($wont));

        $promise = $cm->connect('www.google.com:80');

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testWillTryAllIfEachRejects()
    {
        $rejected = Promise\reject(new \RuntimeException('nope'));

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->exactly(2))->method('connect')->with('google.com:80')->willReturn($rejected);

        $cm = new ConnectionManagerConsecutive(array($connector, $connector));

        $promise = $cm->connect('google.com:80');

        $this->assertPromiseReject($promise);
    }

    public function testCancellationWillNotStartAnyFurtherConnections()
    {
        $pending = new Promise\Promise(function () { }, $this->expectCallableOnce());

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('google.com:80')->willReturn($pending);

        $cm = new ConnectionManagerConsecutive(array($connector, $connector));

        $promise = $cm->connect('google.com:80');
        $promise->cancel();
    }
}
