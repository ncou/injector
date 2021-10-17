<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\InvalidParameterTypeException;
use Chiron\Injector\Injector;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use Chiron\Injector\Test\Fixtures\TypedClass;
use PHPUnit\Framework\TestCase;

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
        $expected = 'Parameter 1 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "string", "NULL" passed.';
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

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

    public function testAutowireTypecastingAndValidatingWrongInt(): void
    {
        $expected = 'Parameter 2 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "int", "string" passed.'; //"Unable to resolve 'int' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

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
        $expected = 'Parameter 3 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "float", "string" passed.'; //"Unable to resolve 'float' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

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
        $expected = 'Parameter 4 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "bool", "string" passed.'; //"Unable to resolve 'bool' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

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

    public function testAutowireTypecastingAndValidatingWrongArray(): void
    {
        //$expected = "Unable to resolve 'array' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $expected = 'Parameter 5 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "array", "string" passed.';
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

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
}
