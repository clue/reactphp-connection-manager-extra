# clue/connection-manager-extra [![Build Status](https://travis-ci.org/clue/php-connection-manager-extra.svg?branch=master)](https://travis-ci.org/clue/php-connection-manager-extra)

This project provides _extra_ (in terms of "additional", "extraordinary", "special" and "unusual") decorators,
built on top of [ReactPHP's SocketClient](https://github.com/reactphp/socket-client).

**Table of Contents**

* [Introduction](#introduction)
* [Usage](#usage)
  * [Repeat](#repeat)
  * [Timeout](#timeout)
  * [Delay](#delay)
  * [Reject](#reject)
  * [Swappable](#swappable)
  * [Consecutive](#consecutive)
  * [Random](#random)
  * [Concurrent](#concurrent)
  * [Selective](#selective)
* [Install](#install)
* [License](#license)

## Introduction

If you're not already familar with [react/socket-client](https://github.com/reactphp/socket-client),
think of it as an async (non-blocking) version of [`fsockopen()`](http://php.net/manual/en/function.fsockopen.php)
or [`stream_socket_client()`](http://php.net/manual/en/function.stream-socket-client.php).
I.e. before you can send and receive data to/from a remote server, you first have to establish a connection - which
takes its time because it involves several steps.
In order to be able to establish several connections at the same time, [react/socket-client](https://github.com/reactphp/socket-client) provides a simple
API to establish simple connections in an async (non-blocking) way.

This project includes several classes that extend this base functionality by implementing the same simple `ConnectorInterface`.
This interface provides a single promise-based method `create($host, $ip)` which can be used to easily notify
when the connection is successfully established or the `Connector` gives up and the connection fails.

```php
$connector->create('www.google.com', 80)->then(function ($stream) {
    echo 'connection successfully established';
    $stream->write("GET / HTTP/1.0\r\nHost: www.google.com\r\n\r\n");
    $stream->end();
}, function ($exception) {
    echo 'connection attempt failed: ' . $exception->getMessage();
});

```

Because everything uses the same simple API, the resulting `Connector` classes can be easily interchanged
and be used in places that expect the normal `ConnectorInterface`. This can be used to stack them into each other,
like using [timeouts](#timeout) for TCP connections, [delaying](#delay) SSL/TLS connections,
[retrying](#repeating--retrying) failed connection attemps, [randomly](#random) picking a `Connector` or
any combination thereof.

## Usage

This section lists all this libraries' features along with some examples.
The examples assume you've [installed](#install) this library and
already [set up a `SocketClient/Connector` instance `$connector`](https://github.com/reactphp/socket-client#async-tcpip-connections).

All classes are located in the `ConnectionManager\Extra` namespace.

### Repeat

The `ConnectionManagerRepeat($connector, $tries)` tries connecting to the given location up to a maximum
of `$tries` times when the connection fails.

If you pass a value of `3` to it, it will first issue a normal connection attempt
and then retry up to 2 times if the connection attempt fails:

```php
$connectorRepeater = new ConnectionManagerRepeat($connector, 3);

$connectorRepeater->create('www.google.com', 80)->then(function ($stream) {
    echo 'connection successfully established';
    $stream->close();
});
```

### Timeout

The `ConnectionManagerTimeout($connector, $timeout, $loop)` sets a maximum `$timeout` in seconds on when to give up
waiting for the connection to complete.

```php
$connector = new ConnectionManagerTimeout($connector, 3.0, $loop);
```

### Delay

The `ConnectionManagerDelay($connector, $delay, $loop)` sets a fixed initial `$delay` in seconds before actually
trying to connect. (Not to be confused with [`ConnectionManagerTimeout`](#timeout) which sets a _maximum timeout_.)

```php
$delayed = new ConnectionManagerDelayed($connector, 0.5, $loop);
```

### Reject

The `ConnectionManagerReject()` simply rejects every single connection attempt.
This is particularly useful for the below [`ConnectionManagerSelective`](#selective) to reject connection attempts
to only certain destinations (for example blocking advertisements or harmful sites).

### Swappable

The `ConnectionManagerSwappable($connector)` is a simple decorator for other `ConnectionManager`s to
simplify exchanging the actual `ConnectionManager` during runtime (`->setConnectionManager($connector)`).

### Consecutive

The `ConnectionManagerConsecutive($connectors)` establishs connections by trying to connect through
any of the given `ConnectionManager`s in consecutive order until the first one succeeds.

```php
$consecutive = new ConnectionManagerConsecutive(array(
    $connector1,
    $connector2
));
```

### Random

The `ConnectionManagerRandom($connectors)` works much like `ConnectionManagerConsecutive` but instead
of using a fixed order, it always uses a randomly shuffled order.

```php
$random = new ConnectionManagerRandom(array(
    $connector1,
    $connector2
));
```

### Concurrent

The `ConnectionManagerConcurrent($connectors)` establishes connections by trying to connect through
ALL of the given `ConnectionManager`s at once, until the first one succeeds.

```php
$concurrent = new ConnectionManagerConcurrent(array(
    $connector1,
    $connector2
));
```

### Selective

The `ConnectionManagerSelective($connectors)` manages a list of `Connector`s and
forwards each connection through the first matching one.
This can be used to implement networking access control lists (ACLs) or firewill
rules like a blacklist or whitelist.

This allows fine-grained control on how to handle outgoing connections, like
rejecting advertisements, delaying unencrypted HTTP requests or forwarding HTTPS
connection through a foreign country.

If none of the entries in the list matches, the connection will be rejected.
This can be used to implement a very simple whitelist like this: 

```php
$selective = new ConnectionManagerSelective(array(
    'github.com' => $connector,
    '*:443' => $connector
));
```

If you want to implement a blacklist (i.e. reject only certain targets), make
sure to add a default target to the end of the list like this:

```php
$reject = new ConnectionManagerReject();
$selective = new ConnectionManagerSelective(array(
    'ads.example.com' => $reject,
    '*:80-81' => $reject,
    '*' => $connector
));
```

Similarly, you can also combine any other the other connectors to implement more
advanced connection setups, such as delaying unencrypted connections only and
retrying unreliable hosts:

```php
// delay connection by 2 seconds
$delayed = new ConnectionManagerDelay($connector, 2.0, $loop);

// maximum of 3 tries, each taking no longer than 3 seconds
$retry = new ConnectionManagerRepeat(
    new ConnectionManagerTimeout($connector, 3.0, $loop),
    2
);

$selective = new ConnectionManagerSelective(array(
    '*:80' => $delayed,
    'unreliable.example.com' => $retry,
    '*' => $connector
));
```

## Install

The recommended way to install this library is [through Composer](http://getcomposer.org).
[New to Composer?](http://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require clue/connection-manager-extra:^0.5
```

See also the [CHANGELOG](CHANGELOG.md) for more details about version upgrades.

## License

MIT
