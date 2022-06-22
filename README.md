# clue/reactphp-connection-manager-extra

[![CI status](https://github.com/clue/reactphp-connection-manager-extra/actions/workflows/ci.yml/badge.svg)](https://github.com/clue/reactphp-connection-manager-extra/actions)
[![installs on Packagist](https://img.shields.io/packagist/dt/clue/connection-manager-extra?color=blue&label=installs%20on%20Packagist)](https://packagist.org/packages/clue/connection-manager-extra)

This project provides _extra_ (in terms of "additional", "extraordinary", "special" and "unusual") decorators,
built on top of [ReactPHP's Socket](https://github.com/reactphp/socket).

**Table of Contents**

* [Support us](#support-us)
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
* [Tests](#tests)
* [License](#license)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

## Introduction

If you're not already familar with [react/socket](https://github.com/reactphp/socket),
think of it as an async (non-blocking) version of [`fsockopen()`](https://www.php.net/manual/en/function.fsockopen.php)
or [`stream_socket_client()`](https://www.php.net/manual/en/function.stream-socket-client.php).
I.e. before you can send and receive data to/from a remote server, you first have to establish a connection - which
takes its time because it involves several steps.
In order to be able to establish several connections at the same time, [react/socket](https://github.com/reactphp/socket) provides a simple
API to establish simple connections in an async (non-blocking) way.

This project includes several classes that extend this base functionality by implementing the same simple `ConnectorInterface`.
This interface provides a single promise-based method `connect($uri)` which can be used to easily notify
when the connection is successfully established or the `Connector` gives up and the connection fails.

```php
$connector->connect('www.google.com:80')->then(function ($stream) {
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
[retrying](#repeat) failed connection attemps, [randomly](#random) picking a `Connector` or
any combination thereof.

## Usage

This section lists all this libraries' features along with some examples.
The examples assume you've [installed](#install) this library and
already [set up a `Socket/Connector` instance `$connector`](https://github.com/reactphp/socket#connector).

All classes are located in the `ConnectionManager\Extra` namespace.

### Repeat

The `ConnectionManagerRepeat($connector, $tries)` tries connecting to the given location up to a maximum
of `$tries` times when the connection fails.

If you pass a value of `3` to it, it will first issue a normal connection attempt
and then retry up to 2 times if the connection attempt fails:

```php
$connectorRepeater = new ConnectionManagerRepeat($connector, 3);

$connectorRepeater->connect('www.google.com:80')->then(function ($stream) {
    echo 'connection successfully established';
    $stream->close();
});
```

### Timeout

The `ConnectionManagerTimeout($connector, $timeout, $loop = null)` sets a maximum `$timeout` in seconds on when to give up
waiting for the connection to complete.

```php
$connector = new ConnectionManagerTimeout($connector, 3.0);
```

### Delay

The `ConnectionManagerDelay($connector, $delay, $loop = null)` sets a fixed initial `$delay` in seconds before actually
trying to connect. (Not to be confused with [`ConnectionManagerTimeout`](#timeout) which sets a _maximum timeout_.)

```php
$delayed = new ConnectionManagerDelayed($connector, 0.5);
```

### Reject

The `ConnectionManagerReject(null|string|callable $reason)` simply rejects every single connection attempt.
This is particularly useful for the below [`ConnectionManagerSelective`](#selective) to reject connection attempts
to only certain destinations (for example blocking advertisements or harmful sites).

The constructor accepts an optional rejection reason which will be used for
rejecting the resulting promise.

You can explicitly pass a `string` value which will be used as the message for
the `Exception` instance:

```php
$connector = new ConnectionManagerReject('Blocked');
$connector->connect('www.google.com:80')->then(null, function ($e) {
    assert($e instanceof \Exception);
    assert($e->getMessage() === 'Blocked');
});
```

You can explicitly pass a `callable` value which will be used to either
`throw` or `return` a custom `Exception` instance:

```php
$connector = new ConnectionManagerReject(function ($uri) {
    throw new RuntimeException($uri . ' blocked');
});
$connector->connect('www.google.com:80')->then(null, function ($e) {
    assert($e instanceof \RuntimeException);
    assert($e->getMessage() === 'www.google.com:80 blocked');
});
```

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

Similarly, you can also combine any of the other connectors to implement more
advanced connection setups, such as delaying unencrypted connections only and
retrying unreliable hosts:

```php
// delay connection by 2 seconds
$delayed = new ConnectionManagerDelay($connector, 2.0);

// maximum of 3 tries, each taking no longer than 2.0 seconds
$retry = new ConnectionManagerRepeat(
    new ConnectionManagerTimeout($connector, 2.0),
    3
);

$selective = new ConnectionManagerSelective(array(
    '*:80' => $delayed,
    'unreliable.example.com' => $retry,
    '*' => $connector
));
```

Each entry in the list MUST be in the form `host` or `host:port`, where
`host` may contain the `*` wildcard character and `port` may be given as
either an exact port number or as a range in the form of `min-max`.
Passing anything else will result in an `InvalidArgumentException`.

> Note that the host will be matched exactly as-is otherwise. This means that
  if you only block `youtube.com`, this has no effect on `www.youtube.com`.
  You may want to add a second rule for `*.youtube.com` in this case.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/connection-manager-extra:^1.2
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
