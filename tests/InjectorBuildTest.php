<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\InvalidParameterTypeException;
use Chiron\Injector\Injector;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use Chiron\Injector\Test\Fixtures\TypedClass;
use Chiron\Injector\Test\Fixtures\AdvancedTypedClass;
use Chiron\Injector\Test\Fixtures\StringableObject;
use Chiron\Injector\Test\Fixtures\TraversableObject;
use Chiron\Injector\Test\Fixtures\InvokableObject;
use Chiron\Injector\Test\Fixtures\NoConstructorClass;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineObject;
use Chiron\Injector\Test\Support\EngineObject2;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\StaticMethod;
use PHPUnit\Framework\TestCase;

class InjectorBuildTest extends TestCase
{
    public function testNoConstructorClass(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            NoConstructorClass::class);

        $this->assertInstanceOf(NoConstructorClass::class, $object);
    }

    public function testBuildDirectObject(): void
    {
        $engine = new EngineMarkTwo();
        $container = new Container([EngineMarkTwo::class => $engine]);
        $injector = new Injector($container);

        $object = $injector->build(EngineObject2::class);

        $this->assertSame($engine, $object->getEngine());
    }

    public function testBuildInterfacedObject(): void
    {
        $engine = new EngineMarkTwo();
        $container = new Container([EngineInterface::class => $engine]);
        $injector = new Injector($container);

        $object = $injector->build(EngineObject::class);

        $this->assertSame($engine, $object->getEngine());
    }

    public function testAutowireWithObjectAsCallable(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            AdvancedTypedClass::class,
            [
                'callable' => new InvokableObject()
            ]
        );

        $this->assertInstanceOf(AdvancedTypedClass::class, $object);
    }

    public function testAutowireWithClosureAsCallable(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            AdvancedTypedClass::class,
            [
                'callable' => \Closure::fromCallable(new InvokableObject())
            ]
        );

        $this->assertInstanceOf(AdvancedTypedClass::class, $object);
    }

    public function testAutowireWithObjectParameter(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            AdvancedTypedClass::class,
            [
                'object' => new \StdClass()
            ]
        );

        $this->assertInstanceOf(AdvancedTypedClass::class, $object);
    }

    public function testAutowireWithObjectTraversable(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            AdvancedTypedClass::class,
            [
                'iterable' => new TraversableObject()
            ]
        );

        $this->assertInstanceOf(AdvancedTypedClass::class, $object);
    }

    public function testAutowireWithArray(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            AdvancedTypedClass::class,
            [
                'iterable' => []
            ]
        );

        $this->assertInstanceOf(AdvancedTypedClass::class, $object);
    }

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

    public function testAutowireWithObjectAsString(): void
    {
        $container = new Container();
        $injector = new Injector($container);

        $object = $injector->build(
            TypedClass::class,
            [
                'string' => new StringableObject(),
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
        $expected = 'Parameter 1 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "string", "null" passed.';
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
        $expected = 'Parameter 2 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "int", "string" passed.';
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
        $expected = 'Parameter 3 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "float", "string" passed.';
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
        $expected = 'Parameter 4 of "Chiron\Injector\Test\Fixtures\TypedClass::__construct()" accepts "bool", "string" passed.';
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
