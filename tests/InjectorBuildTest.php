<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\MissingRequiredParameterException;
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
use Chiron\Injector\Test\Support\EngineObject;
use Chiron\Injector\Test\Support\EngineObject2;
use PHPUnit\Framework\TestCase;

use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\MakeEngineCollector;
use Chiron\Injector\Test\Support\TimerUnionTypes;
use Chiron\Injector\Test\Support\FalseUnionTypes;

use Chiron\Injector\Exception\InjectorException;

use DateTimeImmutable;
use DateTimeInterface;

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




    /**
     * If type of a variadic argument is a class and its value is not passed in parameters, then no arguments will be
     * passed, despite the fact that the container has a corresponding value.
     */
    public function testMakeWithVariadicFromContainer(): void
    {
        $container = new Container([EngineInterface::class => new EngineMarkTwo()]);

        $object = (new Injector($container))->build(MakeEngineCollector::class, []);

        $this->assertCount(0, $object->engines);
    }

    public function testMakeWithVariadicFromArguments(): void
    {
        $container = new Container();

        $values = [new EngineMarkTwo(), new EngineMarkTwo()];
        $object = (new Injector($container))->build(MakeEngineCollector::class, $values);

        $this->assertSame($values, $object->engines);
    }


    public function testBuildInternalClass(): void
    {
        $container = new Container();

        $object = (new Injector($container))->build(\SplFileObject::class, [
            'filename' => __FILE__,
            // second parameter skipped
            // third parameter skipped
            'context' => null,
            'other-parameter' => true,
        ]);

        $this->assertSame(basename(__FILE__), $object->getFilename());
    }


    //*****************

    public function testBuildClassWithUnionTypesAsObject(): void
    {
        $container = new Container();

        $this->expectException(MissingRequiredParameterException::class);
        $this->expectExceptionMessage('Missing required value for parameter "$time" when calling "Chiron\Injector\Test\Support\TimerUnionTypes::__construct"');

        $object = (new Injector($container))
            ->build(TimerUnionTypes::class, [new DateTimeImmutable()]);
    }







    public function testBuildClassWithUnionTypesAsDate(): void
    {
        $time = new DateTimeImmutable();
        $container = new Container();

        $object = (new Injector($container))
            ->build(TimerUnionTypes::class, ['time' => $time]);

        $this->assertSame($object->getTime(), $time);
    }

    public function testBuildClassWithUnionTypesAsString(): void
    {
        $time = '8th january 2021';
        $container = new Container();

        $object = (new Injector($container))
            ->build(TimerUnionTypes::class, ['time' => $time]);

        $this->assertSame($object->getTime(), $time);
    }

    public function testBuildClassWithUnionTypesInvalidThrowsInjectorException(): void
    {
        $container = new Container();

        $this->expectException(InjectorException::class);
        $this->expectExceptionMessage('Parameter 1 of "Chiron\Injector\Test\Support\TimerUnionTypes::__construct()" accepts "DateTimeInterface|string", "bool" passed.');

        $object = (new Injector($container))
            ->build(TimerUnionTypes::class, ['time' => false]);
    }



    public function testBuildClassWithUnionTypesAsFalse(): void
    {
        $container = new Container();

        $object = (new Injector($container))
            ->build(FalseUnionTypes::class, ['value' => false, 'extra' => false]);

        $this->assertSame($object->getValue(), false);
        $this->assertSame($object->getExtra(), false);
    }



    public function testPrivateConstructorThrowsInjectorException(): void
    {
        $container = new Container();

        $this->expectException(InjectorException::class);
        $this->expectExceptionMessage('Class "Chiron\Injector\Test\PrivateConstructor" is not instantiable.');

        $object = (new Injector($container))
            ->build(PrivateConstructor::class);
    }

    public function testBuildClassWithSelfKeyword(): void
    {
        $container = new Container();

        $object = (new Injector($container))
            ->build(SelfClass::class, [new SelfClass(null)]);

        $this->assertInstanceOf(SelfClass::class, $object->class);
    }


    public function testBuildClassWithParentKeyword(): void
    {
        $container = new Container();

        $object = (new Injector($container))
            ->build(ParentClass::class, [new SelfClass(null)]);

        $this->assertInstanceOf(SelfClass::class, $object->class);
    }




    /**
     * @requires PHP 8.1
     */
    public function testBuildClassWithIntersectionTypeMissingParameter()
    {
        $container = new Container();

        $this->expectException(MissingRequiredParameterException::class);
        $this->expectExceptionMessage('Missing required value for parameter "$engine" when calling "Chiron\Injector\Test\IntersectionClasses::__construct"');

        $object = (new Injector($container))
            ->build(IntersectionClasses::class, [new \stdClass()]);
    }


    /**
     * @requires PHP 8.1
     */
    public function testBuildClassWithIntersectionTypeUsingWrongType()
    {
        $container = new Container();

        $this->expectException(InvalidParameterTypeException::class);
        $this->expectExceptionMessage('Parameter 1 of "Chiron\Injector\Test\IntersectionClasses::__construct()" accepts "Chiron\Injector\Test\EngineInterface&Chiron\Injector\Test\AnotherInterface", "stdClass" passed.');

        $object = (new Injector($container))
            ->build(IntersectionClasses::class, ['engine' => new \stdClass()]);
    }














}


class PrivateConstructor
{
    private function __construct()
    {
    }
}

class SelfClass
{
    public ?self $class;

    public function __construct(?self $class)
    {
        $this->class = $class;
    }
}


class ParentClass extends SelfClass
{
    public ?parent $class;

    public function __construct(?parent $class)
    {
        $this->class = $class;
    }
}

if (\PHP_VERSION_ID >= 80100) {
    require __DIR__.'/intersectiontype_classes.php';
}
