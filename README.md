# connection-manager-extra

This project provides _extra_ (in terms of "additional", "extraordinary", "special" and "unusual") decorators
built upon [connection-manager](https://github.com/clue/connection-manager).

## Introduction

If you're not already familar with [connection-manager](https://github.com/clue/connection-manager),
think of it as an async (non-blocking) version of [`fsockopen()`](http://php.net/manual/en/function.fsockopen.php)
or [`stream_socket_client()`](http://php.net/manual/en/function.stream-socket-client.php).
I.e. before you can send and receive data to/from a remote server, you first have to establish a connection - which
takes its time because it involves several steps.
In order to be able to establish several connections at the same time, [connection-manager](https://github.com/clue/connection-manager) provides a simple
API to establish simple connections in an async (non-blocking) way.

This project includes several classes that extend this base functionality by implementing the same simple `ConnectionManagerInterface`.
This interface provides a single promise-based method `getConnection($host, $ip)` which can be used to easily notify
when the connection is successfully established or the `ConnectionManager` gives up and the connection fails.

```php
$connectionManager->getConnection('www.google.com', 80)->then(function ($stream) {
    echo 'connection successfully established';
    $stream->write("GET / HTTP/1.0\r\nHost: www.google.com\r\n\r\n");
    $stream->end();
}, function ($exception) {
    echo 'connection attempt failed: ' . $exception->getMessage();
});

```

Because everything uses the same simple API, the resulting `ConnectionManager` classes can be easily interchanged
and be used in places that expect the normal `ConnectionManagerInterface`. This can be used to stack them into each other,
like using [timeouts](#timeout) for TCP connections, [delaying](#delay) SSL/TLS connections,
[retrying](#repeating--retrying) failed connection attemps, [randomly](#random) picking a `ConnectionManager` or
any combination thereof.

## Usage

This section lists all this libraries' features along with some examples.
The examples assume you've [installed](#install) this library and
already [set up a `ConnectionManager` instance `$connectionManager`](https://github.com/clue/connection-manager#async-tcpip-connections).

### Repeating / Retrying

`ConnectionManager\Extra\ConnectionManagerRepeat`

```php
$connectionManagerRepeater = new \ConnectionManager\Extra\ConnectionManagerRepeat($connectionManager, 3);
$connectionManagerRepeater->getConnection('www.google.com', 80)->then(function ($stream) {
    echo 'connection successfully established';
    $stream->close();
});
```

### Timeout

`ConnectionManager\Extra\ConnectionManagerTimeout`
Maximum timeout interval in seconds.

### Delay

`ConnectionManager\Extra\ConnectionManagerDelay`

Similar to [timeout](#timeout), but instead of setting a maximum timeout, initial delay in seconds to pass before connection attempt


### Reject

`ConnectionManager\Extra\ConnectionManagerReject`

Simply reject every single connection attempt (particularly useful for below ManagerSelected)
a simple connection manager that rejects every single connection attempt

### Swappable

`ConnectionManager\Extra\ConnectionManagerSwappable`

Interchangable during runtime.
// connection manager decorator which simplifies exchanging the actual connection manager during runtime

### Consecutive

`ConnectionManager\Extra\Multi\ConnectionManagerConsecutive`

Multiple, select next and try each one once

### Random

`ConnectionManager\Extra\Multi\ConnectionManagerRandom`

### Selective

`ConnectionManager\Extra\Multi\ConnectionManagerSelective`

Similar to firewall / networking access control lists (ACLs).


## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/connection-manager-extra": "dev-master"
    }
}
```

## License

MIT
