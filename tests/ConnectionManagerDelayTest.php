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
        $cm = new ConnectionManagerDelay($will, $this->loop, 0.1);

        $promise = $cm->create('www.google.com', 80);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $this->loop->run();
        $promise->then($this->expectCallableOnce(), $this->expectCallableNever());
    }

    public function testCancellationOfPromiseBeforeDelayDoesNotStartConnection()
    {
        $unused = $this->getMock('React\SocketClient\ConnectorInterface');
        $unused->expects($this->never())->method('create');

        $cm = new ConnectionManagerDelay($unused, $this->loop, 1.0);

        $promise = $cm->create('www.google.com', 80);
        $promise->cancel();

        $this->loop->run();
    }
}
