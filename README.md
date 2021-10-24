# Chiron Injector

[![Build Status](https://github.com/ncou/injector/workflows/build/badge.svg)](https://github.com/ncou/injector/actions)
[![Static Analysis](https://github.com/ncou/injector/workflows/static%20analysis/badge.svg)](https://github.com/ncou/injector/actions?query=workflow%3A%22static+analysis%22)

[![CodeCov](https://codecov.io/gh/ncou/injector/branch/master/graph/badge.svg)](https://codecov.io/gh/ncou/injector)

[![Latest Stable Version](https://poser.pugx.org/chiron/injector/v/stable.png)](https://packagist.org/packages/chiron/injector)
[![Total Downloads](https://img.shields.io/packagist/dt/chiron/injector.svg?style=flat-square)](https://packagist.org/packages/chiron/injector/stats)
[![Monthly Downloads](https://img.shields.io/packagist/dm/chiron/injector.svg?style=flat-square)](https://packagist.org/packages/chiron/injector/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/chiron/injector.svg?style=flat-square)](https://packagist.org/packages/chiron/injector)

[![Latest Version](https://img.shields.io/github/v/tag/ncou/injector.svg?style=flat-square)](https://packagist.org/packages/chiron/injector)
[![Total Downloads](https://img.shields.io/packagist/dt/chiron/injector.svg?style=flat-square)](https://packagist.org/packages/chiron/injector)

A [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection)
implementation based on autowiring and
[PSR-11](http://www.php-fig.org/psr/psr-11/) compatible dependency injection containers.

#### Features

 * Injects dependencies when calling functions and creating objects
 * Works with any dependency injection container (DIC) that is [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
 * Accepts additional dependencies and arguments passed as array
 * Allows passing arguments *by parameter name* in the array
 * Resolves object type dependencies from the container and the passed array
   by [parameter type declaration](https://www.php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration)
 * Resolves [variadic arguments](https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list)
   i.e. `function (MyClass ...$a)`

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with composer:

```shell
composer require chiron/injector
```
## About

Injector can automatically resolve and inject dependencies when calling
functions and creating objects.

It therefore uses [Reflection](https://www.php.net/manual/en/book.reflection.php) to analyze the
parameters of the function to call, or the constructor of the class to
instantiate and then tries to resolve all arguments by several strategies.

The main purpose is to find dependency objects - that is arguments of type
object that are declared with a classname or an interface - in a (mandatory)
[PSR-11](http://www.php-fig.org/psr/psr-11/) compatible *dependency injection
container* (DIC). The container must therefore use the class or interface name
as ID.

In addition, an array with arguments can be passed that will also be scanned for
matching dependencies. To make things really flexible (and not limited to
objects), arguments in that array can optionally use a function parameter name
as key. This way basically any callable can be invoked and any object
be instantiated by the Injector even if it uses a mix of object dependencies and
arguments of other types.


## Basic Example

```php
// A function to call
$fn = function (\App\Foo $a, \App\Bar $b, int $c) { /* ... */ };

// Arbitrary PSR-11 compatible object container
$container = new \some\di\Container([
    'App\Foo' => new Foo(), // will be used as $a
]);

// Prepare the injector
$injector = new Injector($container);

// Use the injector to call the function and resolve dependencies
$result = $injector->invoke($fn, [
    'c' => 15,  // will be used as $c
    new Bar(),  // will be used as $b
]);
```

## Documentation

Documentation can be found [here](docs/README.md).

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
composer phpunit
```

### Static analysis

The code is statically analyzed with [Phpstan](https://phpstan.org/). To run static analysis:

```shell
composer phpstan
```

### Coding standards

The code should follow the "chiron coding standard" [PHPCode_Sniffer](https://github.com/ncou/coding-standard). To use coding standards:

```shell
# detect violations of the defined coding standard.
composer check-style
```

```shell
# automatically correct coding standard violations.
composer fix-style
```

## License

The Chiron Injector is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.
