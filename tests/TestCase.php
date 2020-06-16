<?php

namespace ConnectionManager\Tests\Extra;

use React\Promise\Deferred;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    protected function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->never())
            ->method('__invoke');

        return $mock;
    }

    protected function expectCallableOnceParameter($type)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf($type));

        return $mock;
    }

    protected function expectCallableOnceValue($type)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf($type));

        return $mock;
    }

    /**
     * @link https://github.com/reactphp/react/blob/master/tests/React/Tests/Socket/TestCase.php (taken from reactphp/react)
     */
    protected function createCallableMock()
    {
        return $this->getMockBuilder('stdClass')->setMethods(array('__invoke'))->getMock();
    }

    protected function createConnectionManagerMock($ret)
    {
        $mock = $this->getMockBuilder('React\Socket\ConnectorInterface')
            ->getMock();

        $deferred = new Deferred();
        $deferred->resolve($ret);

        $mock
            ->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($deferred->promise()));

        return $mock;
    }

    protected function assertPromiseResolve($promise)
    {
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableOnce(), $this->expectCallableNever());
    }

    protected function assertPromiseReject($promise)
    {
        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function setExpectedException($exception, $message = '', $code = 0)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($exception);
            if ($message !== '') {
                $this->expectExceptionMessage($message);
            }
            $this->expectExceptionCode($code);
        } else {
            parent::setExpectedException($exception, $message, $code);
        }
    }
}
