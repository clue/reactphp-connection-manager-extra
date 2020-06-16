<?php

namespace ConnectionManager\Tests\Extra;

use ConnectionManager\Extra\ConnectionManagerReject;
use ConnectionManager\Extra\ConnectionManagerTimeout;
use React\Promise\Deferred;
use React\Promise\Promise;

class ConnectionManagerTimeoutTest extends TestCase
{
    private $loop;

    /**
     * @before
     */
    public function setUpLoop()
    {
        $this->loop = \React\EventLoop\Factory::create();
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

    public function testTimeoutWillRejectPromiseWhenConnectorExceedsTimeLimit()
    {
        $connectionPromise = new Promise(function(){});
        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->willReturn($connectionPromise);

        $cm = new ConnectionManagerTimeout($connector, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testTimeoutWillEndConnectiontWhenConnectorResolvesAfterTimeoutFired()
    {
        $connectionDeferred = new Deferred();
        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->willReturn($connectionDeferred->promise());

        $cm = new ConnectionManagerTimeout($connector, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());

        $connection = $this->getMockBuilder('React\Socket\ConnectionInterface')->getMock();
        $connection->expects($this->once())->method('end');
        $connectionDeferred->resolve($connection);
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
        $stream = $this->getMockBuilder('React\Socket\ConnectionInterface')->getMock();
        $stream->expects($this->once())->method('end');

        $loop = $this->loop;
        $promise = new Promise(function ($resolve) use ($loop, $stream) {
            $loop->addTimer(0.01, function () use ($resolve, $stream) {
                $resolve($stream);
            });
        });

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
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

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('www.google.com:80')->willReturn($promise);

        $cm = new ConnectionManagerTimeout($connector, 5.0, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $promise->cancel();

        $this->loop->run();

        $this->assertPromiseReject($promise);
    }
}
