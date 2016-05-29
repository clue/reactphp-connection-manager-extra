<?php

use ConnectionManager\Extra\ConnectionManagerReject;

use React\Stream\Stream;
use ConnectionManager\Extra\ConnectionManagerDelay;
use ConnectionManager\Extra\ConnectionManagerTimeout;
use React\Promise\Promise;
use React\Promise\Timer;

class ConnectionManagerTimeoutTest extends TestCase
{
    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
    }

    public function testTimeoutOkay()
    {
        $will = $this->createConnectionManagerMock(true);
        $cm = new ConnectionManagerTimeout($will, $this->loop, 0.1);

        $promise = $cm->create('www.google.com', 80);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableOnce(), $this->expectCallableNever());
    }

    public function testTimeoutExpire()
    {
        $will = $this->createConnectionManagerMock(new Stream(fopen('php://temp', 'r'), $this->loop));
        $wont = new ConnectionManagerDelay($will, $this->loop, 0.2);

        $cm = new ConnectionManagerTimeout($wont, $this->loop, 0.1);

        $promise = $cm->create('www.google.com', 80);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testTimeoutAbort()
    {
        $wont = new ConnectionManagerReject();

        $cm = new ConnectionManagerTimeout($wont, $this->loop, 0.1);

        $promise = $cm->create('www.google.com', 80);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testWillEndConnectionIfConnectionResolvesDespiteTimeout()
    {
        $stream = $this->getMockBuilder('React\Stream\Stream')->disableOriginalConstructor()->getMock();
        $stream->expects($this->once())->method('end');

        $loop = $this->loop;
        $promise = new Promise(function ($resolve) use ($loop, $stream) {
            $loop->addTimer(0.002, function () use ($resolve, $stream) {
                $resolve($stream);
            });
        });

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('www.google.com', 80)->willReturn($promise);

        $cm = new ConnectionManagerTimeout($connector, $this->loop, 0.001);

        $promise = $cm->create('www.google.com', 80);

        $this->loop->run();

        $this->assertPromiseReject($promise);
    }

    public function testCancellationOfPromiseWillCancelConnectionAttempt()
    {
        $promise = new Promise(function () {}, function () {
            throw new \RuntimeException();
        });

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('www.google.com', 80)->willReturn($promise);

        $cm = new ConnectionManagerTimeout($connector, $this->loop, 5.0);

        $promise = $cm->create('www.google.com', 80);
        $promise->cancel();

        $this->loop->run();

        $this->assertPromiseReject($promise);
    }
}
