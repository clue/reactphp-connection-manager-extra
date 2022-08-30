# Changelog

## 1.3.0 (2022-08-30)

*   Feature: Simplify usage by supporting new default loop.
    (#33 by @SimonFrings)

    ```php
    // old (still supported)
    $connector = new ConnectionManagerTimeout($connector, 3.0, $loop);
    $delayed = new ConnectionManagerDelayed($connector, 0.5, $loop);

    // new (using default loop)
    $connector = new ConnectionManagerTimeout($connector, 3.0);
    $delayed = new ConnectionManagerDelayed($connector, 0.5);
    ```

*   Feature: Full support for PHP 8.1 and PHP 8.2.
    (#36 and #37 by @SimonFrings)

*   Feature: Forward compatibility with upcoming Promise v3.
    (#34 by @clue)

*   Improve test suite and add badge to show number of project installations.
    (#35 by @SimonFrings and #31 by @PaulRotmann)

## 1.2.0 (2020-12-12)

*   Improve test suite and add `.gitattributes` to exclude dev files from exports.
    Prepare PHP 8 support, update to PHPUnit 9 and simplify test matrix.
    (#24, #25 and #26 by @clue and #27, #28, #29 and #30 by @SimonFrings)

## 1.1.0 (2017-08-03)

* Feature: Support custom rejection reason for ConnectionManagerReject
  (#23 by @clue)

  ```php
  $connector = new ConnectionManagerReject(function ($uri) {
      throw new RuntimeException($uri . ' blocked');
  });
  ```

## 1.0.1 (2017-06-23)

* Fix: Ignore URI scheme when matching selective connectors
  (#21 by @clue)

* Fix HHVM build for now again and ignore future HHVM build errors
  (#22 by @clue)

## 1.0.0 (2017-05-09)

* First stable release, now following SemVer

  > Contains no other changes, so it's actually fully compatible with the v0.7 releases.

## 0.7.1 (2017-05-09)

* Fix: Reject promise for invalid URI passed to ConnectionManagerSelective
  (#19 by @clue)

* Feature: Forward compatibility with upcoming Socket v1.0 and v0.8 and
  upcoming EventLoop v1.0 and v0.5
  (#18 and #20 by @clue)

## 0.7.0 (2017-04-10)

* Feature / BC break: Replace deprecated SocketClient with new Socket component
  (#17 by @clue)

  This implies that all connectors from this package now implement the
  `React\Socket\ConnectorInterface` instead of the legacy
  `React\SocketClient\ConnectorInterface`.

## 0.6.0 (2017-04-07)

* Feature / BC break: Update SocketClient to v0.7 or v0.6
  (#16 by @clue)

* Improve test suite by adding PHPUnit to require-dev
  (#15 by @clue)

## 0.5.0 (2016-06-01)

* BC break: Change $retries to $tries
  (#14 by @clue)
  
  ```php
  // old
  // 1 try plus 2 retries => 3 total tries
  $c = new ConnectionManagerRepeat($c, 2);
  
  // new
  // 3 total tries (1 try plus 2 retries)
  $c = new ConnectionManagerRepeat($c, 3);
  ```

* BC break: Timed connectors now use $loop as last argument
  (#13 by @clue)
  
  ```php
  // old
  // $c = new ConnectionManagerDelay($c, $loop, 1.0);
  $c = new ConnectionManagerTimeout($c, $loop, 1.0);
  
  // new
  $c = new ConnectionManagerTimeout($c, 1.0, $loop);
  ```

* BC break: Move all connector lists to the constructor
  (#12 by @clue)

  ```php
  // old
  // $c = new ConnectionManagerConcurrent();
  // $c = new ConnectionManagerRandom();
  $c = new ConnectionManagerConsecutive();
  $c->addConnectionManager($c1);
  $c->addConnectionManager($c2);
  
  // new
  $c = new ConnectionManagerConsecutive(array(
      $c1,
      $c2
  ));
  ```

* BC break: ConnectionManagerSelective now accepts connector list in constructor
  (#11 by @clue)

  ```php
  // old
  $c = new ConnectionManagerSelective();
  $c->addConnectionManagerFor($c1, 'host1');
  $c->addConnectionManagerFor($c2, 'host2');
  
  // new
  $c = new ConnectionManagerSelective(array(
      'host1' => $c1,
      'host2' => $c2
  ));
  ```

## 0.4.0 (2016-05-30)

* Feature: Add `ConnectionManagerConcurrent`
  (#10 by @clue)

* Feature: Support Promise cancellation for all connectors
  (#9 by @clue)

## 0.3.3 (2016-05-29)

* Fix repetitions for `ConnectionManagerRepeat`
  (#8 by @clue)

* First class support for PHP 5.3 through PHP 7 and HHVM
  (#7 by @clue)

## 0.3.2 (2016-03-19)

* Compatibility with react/socket-client:v0.5 (keeping full BC)
  (#6 by @clue)

## 0.3.1 (2014-09-27)

* Support React PHP v0.4 (while preserving BC with React PHP v0.3)
  (#4)

## 0.3.0 (2013-06-24)

* BC break: Switch from (deprecated) `clue/connection-manager` to `react/socket-client`
  and thus replace each occurance of `getConnect($host, $port)` with `create($host, $port)`
  (#1)
  
* Fix: Timeouts in `ConnectionManagerTimeout` now actually work
  (#1)

* Fix: Properly reject promise in `ConnectionManagerSelective` when no targets
  have been found
  (#1)

## 0.2.0 (2013-02-08)

* Feature: Add `ConnectionManagerSelective` which works like a network/firewall ACL

## 0.1.0 (2013-01-12)

* First tagged release

