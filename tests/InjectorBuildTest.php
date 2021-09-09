<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use Chiron\Injector\Injector;
use Chiron\Injector\MissingRequiredArgumentException;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\StaticMethod;
use Chiron\Injector\Exception\NotCallableException;
use Chiron\Injector\Test\Fixtures\TypedClass;

class InjectorBuildTest extends TestCase
{
    public function testAutowireOptionalString(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => '',
                'int'    => 123,
                'float'  => 1.00,
                'bool'   => true,
                'pong'   => null,
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireTypecastingAndValidatingWrongString(): void
    {
        //$expected = "Unable to resolve 'string' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $expected = 'Cannot resolve a value for parameter "$string" in callable "Chiron\Injector\Test\Fixtures\TypedClass::__construct"';
        $this->expectExceptionMessage($expected);
        $this->expectException(CannotResolveException::class);

        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => null,
                'int'    => 123,
                'float'  => 123.00,
                'bool'   => true,
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireTypecastingAndValidatingWrongArray(): void
    {
        //$expected = "Unable to resolve 'array' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $expected = 'Cannot resolve a value for parameter "$array" in callable "Chiron\Injector\Test\Fixtures\TypedClass::__construct"';
        $this->expectExceptionMessage($expected);
        $this->expectException(CannotResolveException::class);

        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => '',
                'int'    => 123,
                'float'  => 1.00,
                'bool'   => true,
                'array'  => 'not array',
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireTypecastingAndValidatingWrongInt(): void
    {
        $expected = 'Cannot resolve a value for parameter "$int" in callable "Chiron\Injector\Test\Fixtures\TypedClass::__construct"'; //"Unable to resolve 'int' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(CannotResolveException::class);

        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => '',
                'int'    => 'yo!',
                'float'  => 123.00,
                'bool'   => true,
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireTypecastingAndValidatingWrongFloat(): void
    {
        $expected = 'Cannot resolve a value for parameter "$float" in callable "Chiron\Injector\Test\Fixtures\TypedClass::__construct"'; //"Unable to resolve 'float' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(CannotResolveException::class);

        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => '',
                'int'    => 123,
                'float'  => '~',
                'bool'   => true,
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

    public function testAutowireTypecastingAndValidatingWrongBool(): void
    {
        $expected = 'Cannot resolve a value for parameter "$bool" in callable "Chiron\Injector\Test\Fixtures\TypedClass::__construct"'; //"Unable to resolve 'bool' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(CannotResolveException::class);

        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => '',
                'int'    => 123,
                'float'  => 1.00,
                'bool'   => 'true',
            ]
        );

        $this->assertInstanceOf(TypedClass::class, $object);
    }

}
