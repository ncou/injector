<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\InvalidParameterTypeException;
use Chiron\Injector\Injector;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use PHPUnit\Framework\TestCase;
use StdClass;

// TODO : virer le @test comme annotation et faire démarrer le nom de chaque méthode par test_
class InjectorInvokeTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_callable_with_untyped_variadic_parameter()
    {
        $container = new Container();
        $injector = new Injector($container);

        $callable = function (...$param) {
            return $param;
        };
        $this->assertEquals([1, 2, 3], $injector->invoke($callable, [1, 2, 3]), 'non-empty variadic');
        $this->assertEquals([], $injector->invoke($callable, []), 'empty variadic');
    }

    /**
     * @test
     */
    public function invoke_callable_with_scalar_typed_variadic_parameter()
    {
        $container = new Container();
        $injector = new Injector($container);

        $callable = function (string ...$param) {
            return $param;
        };
        $this->assertEquals(['1', '2', '3'], $injector->invoke($callable, ['1', '2', '3']), 'non-empty variadic');
        $this->assertEquals([], $injector->invoke($callable, []), 'empty variadic');
    }

    /**
     * @test
     */
    public function invoke_callable_with_scalar_typed_variadic_parameter_at_last_position()
    {
        $container = new Container();
        $injector = new Injector($container);

        $callable = function (int $integer, string ...$strings) {
            return $strings;
        };
        $this->assertEquals(['1', '2', '3'], $injector->invoke($callable, [123, '1', '2', '3']));
        $this->assertEquals([], $injector->invoke($callable, [123]), 'empty variadic');
    }

    /**
     * @test
     */
    public function invoke_callable_with_scalar_typed_variadic_parameter_at_last_position_with_name()
    {
        $container = new Container();
        $injector = new Injector($container);

        $callable = function (int $integer, string ...$strings) {
            return $strings;
        };
        $this->assertEquals(['1', '2', '3'], $injector->invoke($callable, ['integer' => 123, '1', '2', '3']));
        $this->assertEquals([], $injector->invoke($callable, ['integer' => 123]), 'empty variadic');
    }

    /**
     * @test
     */
    public function invoke_callable_with_object_typed_variadic_parameter()
    {
        $container = new Container();
        $injector = new Injector($container);

        $callable = function (StdClass ...$param) {
            return $param;
        };

        $class = new StdClass();
        $this->assertEquals([$class, $class], $injector->invoke($callable, [$class, $class]), 'non-empty variadic');
        $this->assertEquals([], $injector->invoke($callable, []), 'empty variadic');
    }

    /**
     * @test
     */
    public function invoke_callable_with_wrong_typed_variadic_parameter()
    {
        $expected = 'Argument 1 of "Chiron\Injector\Test\InjectorInvokeTest::Chiron\Injector\Test\{closure}()" accepts "string", "integer" passed.'; //"Unable to resolve 'int' argument in 'Spiral\Tests\Core\Fixtures\TypedClass::__construct'";
        $this->expectExceptionMessage($expected);
        $this->expectException(InvalidParameterTypeException::class);

        $container = new Container();
        $injector = new Injector($container);

        $callable = function (string ...$param) {
            return $param;
        };
        $this->assertEquals([1, 2, 3], $injector->invoke($callable, [1, 2, 3]), 'non-empty variadic');
        $this->assertEquals([], $injector->invoke($callable, []), 'empty variadic');
    }
}
