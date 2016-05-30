<?php

use React\Stream\Stream;

use ConnectionManager\Extra\Multiple\ConnectionManagerSelective;
use ConnectionManager\Extra\ConnectionManagerReject;

class ConnectionManagerSelectiveTest extends TestCase
{
    public function testEmptyWillAlwaysReject()
    {
        $cm = new ConnectionManagerSelective(array());

        $promise = $cm->create('www.google.com', 80);
        $this->assertPromiseReject($promise);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConnectorThrowsException()
    {
        new ConnectionManagerSelective(array(
            'example.com' => false
        ));
    }

    public function provideInvalidMatcher()
    {
        return array(
            'empty' => array(
                ''
            ),
            'no port' => array(
                '*:'
            ),
            'no host' => array(
                ':80'
            ),
            'no nothing' => array(
                ':'
            ),
            'wildcard port' => array(
                'example.com:*'
            ),
            'not a port' => array(
                'example.com:nope'
            ),
            'port min bigger max' => array(
                'example.com:100-10'
            ),
            'port invalid range no max' => array(
                'example.com:100-'
            ),
            'port invalid range no min' => array(
                'example.com:-1000'
            ),
            'port invalid range no min max' => array(
                'example.com:-'
            )
        );
    }

    /**
     * @dataProvider provideInvalidMatcher
     * @expectedException InvalidArgumentException
     *
     * @param string $matcher
     */
    public function testInvalidMatcherThrowsException($matcher)
    {
        $connector = $this->getMock('React\SocketClient\ConnectorInterface');

        new ConnectionManagerSelective(array(
            $matcher => $connector
        ));
    }

    public function testExactDomainMatchForwardsToConnector()
    {
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('example.com', 80)->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            'example.com' => $connector
        ));

        $ret = $cm->create('example.com' , 80);

        $this->assertSame($promise, $ret);
    }

    public function testExactIpv6MatchForwardsToConnector()
    {
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('::1', 80)->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            '::1' => $connector
        ));

        $ret = $cm->create('::1' , 80);

        $this->assertSame($promise, $ret);
    }

    public function testExactIpv6WithPortMatchForwardsToConnector()
    {
        $promise = $this->getMock('React\Promise\PromiseInterface');

        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->once())->method('create')->with('::1', 80)->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            '[::1]:80' => $connector
        ));

        $ret = $cm->create('::1' , 80);

        $this->assertSame($promise, $ret);
    }

    public function testNotMatchingDomainWillReject()
    {
        $connector = $this->getMock('React\SocketClient\ConnectorInterface');
        $connector->expects($this->never())->method('create');

        $cm = new ConnectionManagerSelective(array(
            'example.com' => $connector
        ));

        $promise = $cm->create('other.example.com' , 80);

        $this->assertPromiseReject($promise);
    }

    public function testReject()
    {
        $will = $this->createConnectionManagerMock(new Stream(fopen('php://temp', 'r'), $this->createLoopMock()));

        $cm = new ConnectionManagerSelective(array(
            'www.google.com:443' => $will,
            'www.youtube.com' => $will
        ));

        $this->assertPromiseResolve($cm->create('www.google.com', 443));

        $this->assertPromiseReject($cm->create('www.google.com', 80));

        $this->assertPromiseResolve($cm->create('www.youtube.com', 80));
    }

    public function testFirstEntryWinsIfMultipleMatch()
    {
        $wont = new ConnectionManagerReject();
        $will = $this->createConnectionManagerMock(new Stream(fopen('php://temp', 'r'), $this->createLoopMock()));

        $cm = new ConnectionManagerSelective(array(
            'www.google.com:443' => $will,
            '*' => $wont
        ));

        $this->assertPromiseResolve($cm->create('www.google.com', 443));
        $this->assertPromiseReject($cm->create('www.google.com', 80));
    }

    public function testWildcardsMatch()
    {
        $will = $this->createConnectionManagerMock(new Stream(fopen('php://temp', 'r'), $this->createLoopMock()));

        $cm = new ConnectionManagerSelective(array(
            '*.com' => $will,
            '*:443-444' => $will,
            '*:8080' => $will,
            '*.youtube.*' => $will,
            'youtube.*' => $will,
        ));

        $this->assertPromiseResolve($cm->create('www.google.com', 80));
        $this->assertPromiseReject($cm->create('www.google.de', 80));

        $this->assertPromiseResolve($cm->create('www.google.de', 443));
        $this->assertPromiseResolve($cm->create('www.google.de', 444));
        $this->assertPromiseResolve($cm->create('www.google.de', 8080));
        $this->assertPromiseReject($cm->create('www.google.de', 445));

        $this->assertPromiseResolve($cm->create('www.youtube.de', 80));
        $this->assertPromiseResolve($cm->create('download.youtube.de', 80));
        $this->assertPromiseResolve($cm->create('youtube.de', 80));
    }

    private function createLoopMock()
    {
        return $this->getMockBuilder('React\EventLoop\StreamSelectLoop')
                     ->disableOriginalConstructor()
                     ->getMock();
    }
}
