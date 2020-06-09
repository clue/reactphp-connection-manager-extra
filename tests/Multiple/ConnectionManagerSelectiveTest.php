<?php

use ConnectionManager\Extra\Multiple\ConnectionManagerSelective;
use ConnectionManager\Extra\ConnectionManagerReject;

class ConnectionManagerSelectiveTest extends TestCase
{
    public function testEmptyWillAlwaysReject()
    {
        $cm = new ConnectionManagerSelective(array());

        $promise = $cm->connect('www.google.com:80');
        $this->assertPromiseReject($promise);
    }

    public function testInvalidUriWillAlwaysReject()
    {
        $cm = new ConnectionManagerSelective(array());

        $promise = $cm->connect('////');
        $this->assertPromiseReject($promise);
    }

    public function testInvalidConnectorThrowsException()
    {

        $this->setExpectedException("InvalidArgumentException");
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
     *
     * @param string $matcher
     */
    public function testInvalidMatcherThrowsException($matcher)
    {
        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $this->setExpectedException("InvalidArgumentException");
        new ConnectionManagerSelective(array(
            $matcher => $connector
        ));
    }

    public function testExactDomainMatchForwardsToConnector()
    {
        $promise = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('example.com:80')->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            'example.com' => $connector
        ));

        $ret = $cm->connect('example.com:80');

        $this->assertSame($promise, $ret);
    }

    public function testExactIpv6MatchForwardsToConnector()
    {
        $promise = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('[::1]:80')->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            '::1' => $connector
        ));

        $ret = $cm->connect('[::1]:80');

        $this->assertSame($promise, $ret);
    }

    public function testExactIpv6WithPortMatchForwardsToConnector()
    {
        $promise = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();

        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->once())->method('connect')->with('[::1]:80')->willReturn($promise);

        $cm = new ConnectionManagerSelective(array(
            '[::1]:80' => $connector
        ));

        $ret = $cm->connect('[::1]:80');

        $this->assertSame($promise, $ret);
    }

    public function testNotMatchingDomainWillReject()
    {
        $connector = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();
        $connector->expects($this->never())->method('connect');

        $cm = new ConnectionManagerSelective(array(
            'example.com' => $connector
        ));

        $promise = $cm->connect('other.example.com:80');

        $this->assertPromiseReject($promise);
    }

    public function testRejectIfNotMatching()
    {
        $will = $this->createConnectionManagerMock(true);

        $cm = new ConnectionManagerSelective(array(
            'www.google.com:443' => $will,
            'www.youtube.com' => $will
        ));

        $this->assertPromiseResolve($cm->connect('www.google.com:443'));
        $this->assertPromiseResolve($cm->connect('tls://www.google.com:443'));

        $this->assertPromiseReject($cm->connect('www.google.com:80'));
        $this->assertPromiseReject($cm->connect('tcp://www.google.com:80'));

        $this->assertPromiseResolve($cm->connect('www.youtube.com:80'));
        $this->assertPromiseResolve($cm->connect('tcp://www.youtube.com:80'));
    }

    public function testFirstEntryWinsIfMultipleMatch()
    {
        $wont = new ConnectionManagerReject();
        $will = $this->createConnectionManagerMock(true);

        $cm = new ConnectionManagerSelective(array(
            'www.google.com:443' => $will,
            '*' => $wont
        ));

        $this->assertPromiseResolve($cm->connect('www.google.com:443'));
        $this->assertPromiseReject($cm->connect('www.google.com:80'));
    }

    public function testWildcardsMatch()
    {
        $will = $this->createConnectionManagerMock(true);

        $cm = new ConnectionManagerSelective(array(
            '*.com' => $will,
            '*:443-444' => $will,
            '*:8080' => $will,
            '*.youtube.*' => $will,
            'youtube.*' => $will,
        ));

        $this->assertPromiseResolve($cm->connect('www.google.com:80'));
        $this->assertPromiseReject($cm->connect('www.google.de:80'));

        $this->assertPromiseResolve($cm->connect('www.google.de:443'));
        $this->assertPromiseResolve($cm->connect('www.google.de:444'));
        $this->assertPromiseResolve($cm->connect('www.google.de:8080'));
        $this->assertPromiseReject($cm->connect('www.google.de:445'));

        $this->assertPromiseResolve($cm->connect('www.youtube.de:80'));
        $this->assertPromiseResolve($cm->connect('download.youtube.de:80'));
        $this->assertPromiseResolve($cm->connect('youtube.de:80'));
    }

    private function createLoopMock()
    {
        return $this->getMockBuilder('React\EventLoop\StreamSelectLoop')
                     ->disableOriginalConstructor()
                     ->getMock();
    }
}
