# Changelog

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

