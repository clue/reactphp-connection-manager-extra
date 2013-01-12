# connection-manager-extra

Built upon [connection-manager](https://github.com/clue/connection-manager), this project provides extra (in terms
of "additional", "extraordinary", "special" and "unusual") decorators. They all implement the simple
`ConnectionManagerInterface` which only provides a single promise-based method `getConnection($host, $ip)` for
establishing async (non-blocking) connections to remote servers.

## Introduction


## Usage

Once [installed](#install), 


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
