<?php

use ConnectionManager\Extra\ConnectionManagerReject;

use React\Stream\Stream;
use ConnectionManager\Extra\ConnectionManagerDelay;
use ConnectionManager\Extra\ConnectionManagerTimeout;
use React\Promise\Promise;
use React\Promise\Timer;

class ConnectionManagerTimeoutTest extends TestCase
{
    private $loop;

    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
    }

    public function testTimeoutOkay()
    {
        $will = $this->createConnectionManagerMock(true);
        $cm = new ConnectionManagerTimeout($will, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableOnce(), $this->expectCallableNever());
    }

    public function testTimeoutExpire()
    {
        $will = $this->createConnectionManagerMock(new Stream(fopen('php://temp', 'r+'), $this->loop));
        $wont = new ConnectionManagerDelay($will, 0.2, $this->loop);

        $cm = new ConnectionManagerTimeout($wont, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testTimeoutAbort()
    {
        $wont = new ConnectionManagerReject();

        $cm = new ConnectionManagerTimeout($wont, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testWillEndConnectionIfConnectionResolvesDespiteTimeout()
    {
        $stream = $this->getMockBuilder('React\Stream\DuplexStreamInterface')->disableOriginalConstructor()->getMock();
        $stream->expects($this->once())->method('end');

        $loop = $this->loop;
        $promise = new Promise(function ($resolve) use ($loop, $stream) {
            $loop->addTimer(0.002, function () use ($resolve, $stream) {
                $resolve($stream);
            });
        });

        $connector = $this->getMockBuilder('React\SocketClient\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('www.google.com:80')->willReturn($promise);

        $cm = new ConnectionManagerTimeout($connector, 0.001, $this->loop);

        $promise = $cm->connect('www.google.com:80');

        $this->loop->run();

        $this->assertPromiseReject($promise);
    }

    public function testCancellationOfPromiseWillCancelConnectionAttempt()
    {
        $promise = new Promise(function () {}, function () {
            throw new \RuntimeException();
        });

        $connector = $this->getMockBuilder('React\SocketClient\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('www.google.com:80')->willReturn($promise);

        $cm = new ConnectionManagerTimeout($connector, 5.0, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $promise->cancel();

        $this->loop->run();

        $this->assertPromiseReject($promise);
    }
}
