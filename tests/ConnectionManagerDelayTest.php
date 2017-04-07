<?php


use ConnectionManager\Extra\ConnectionManagerDelay;

class ConnectionManagerDelayTest extends TestCase
{
    private $loop;

    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
    }

    public function testDelayTenth()
    {
        $will = $this->createConnectionManagerMock(true);
        $cm = new ConnectionManagerDelay($will, 0.1, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableOnce(), $this->expectCallableNever());
    }

    public function testCancellationOfPromiseBeforeDelayDoesNotStartConnection()
    {
        $unused = $this->getMockBuilder('React\SocketClient\ConnectorInterface')->getMock();
        $unused->expects($this->never())->method('connect');

        $cm = new ConnectionManagerDelay($unused, 1.0, $this->loop);

        $promise = $cm->connect('www.google.com:80');
        $promise->cancel();

        $this->loop->run();
    }
}
