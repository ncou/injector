<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\InjectorException;
use Chiron\Injector\Exception\InvalidParameterTypeException;
use Chiron\Injector\Exception\MissingRequiredParameterException;
use Chiron\Injector\Exception\NotCallableException;
use Chiron\Injector\Injector;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use Chiron\Injector\Test\Support\CallStaticObject;
use Chiron\Injector\Test\Support\CallStaticWithSelfObject;
use Chiron\Injector\Test\Support\CallStaticWithStaticObject;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\StaticMethod;
use Chiron\Injector\Test\Support\StaticWithSelfObject;
use Chiron\Injector\Test\Support\StaticWithStaticObject;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use StdClass;

// UNION/ INTERSECTION TESTS :

//https://github.com/symfony/dependency-injection/blob/69c398723857bb19fdea78496cedea0f756decab/Tests/Fixtures/includes/uniontype_classes.php
//https://github.com/symfony/dependency-injection/blob/69c398723857bb19fdea78496cedea0f756decab/Tests/Fixtures/includes/intersectiontype_classes.php
//https://github.com/symfony/dependency-injection/blob/69c398723857bb19fdea78496cedea0f756decab/Tests/Fixtures/includes/autowiring_classes.php#L12

//https://github.com/symfony/dependency-injection/blob/a7e4cff3e3e707827e09b0ff4c1acef40ba5d672/Tests/Compiler/CheckTypeDeclarationsPassTest.php#L959
//https://github.com/symfony/dependency-injection/blob/69c398723857bb19fdea78496cedea0f756decab/Tests/Fixtures/CheckTypeDeclarationsPass/IntersectionConstructor.php

//https://github.com/symfony/dependency-injection/blob/69c398723857bb19fdea78496cedea0f756decab/Tests/Compiler/AutowirePassTest.php#L284

// TODO : créer un test pour l'utilisation de "false" dans l'union
// @see https://php.watch/versions/8.0/union-types

// TEST VARIADIC !!!
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/tests/framework/di/testContainerWithVariadicCallable.php
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/tests/framework/di/ContainerTest.php#L485

class InjectorTest extends TestCase
{
    public function testInvokeClosure(): void
    {
        $container = new Container([EngineInterface::class => new EngineMarkTwo()]);

        $getEngineName = static function (EngineInterface $engine) {
            return $engine->getName();
        };

        $result = (new Injector($container))->invoke($getEngineName);

        $this->assertSame('Mark Two', $result);
    }

    public function testInvokeFunction(): void
    {
        $container = new Container([EngineInterface::class => new EngineMarkTwo()]);

        $result = (new Injector($container))->invoke('strlen', ['string' => 'foobar']);

        $this->assertSame(6, $result);
    }

    public function testInvokeWithStaticMethod(): void
    {
        $container = new Container();
        $result = (new Injector($container))->invoke([StaticMethod::class, 'getName']);

        $this->assertSame('Mark Two', $result);
    }

    public function testInvokeMissingRequiredParameter(): void
    {
        // TODO : améliorer le message d'erreur dans le cas d'une closure. Eventuellement afficher le nom du fichier + ligne, ca sera plus simple à debugger !!!!
        $this->expectExceptionMessage('Missing required value for parameter "$two" when calling "Chiron\Injector\Test\InjectorTest::Chiron\Injector\Test\{closure}');
        $this->expectException(MissingRequiredParameterException::class);

        $container = new Container([
            EngineInterface::class => new EngineMarkTwo(),
        ]);

        $getEngineName = static function (EngineInterface $engine, $two) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $injector->invoke($getEngineName);
    }

    public function testInvokeMissingRequiredClassParameter(): void
    {
        // TODO : améliorer le message d'erreur dans le cas d'une closure. Eventuellement afficher le nom du fichier + ligne, ca sera plus simple à debugger !!!!
        $this->expectExceptionMessage('Missing required value for parameter "$color" when calling "Chiron\Injector\Test\InjectorTest::Chiron\Injector\Test\{closure}');
        $this->expectException(MissingRequiredParameterException::class);

        $container = new Container([
            EngineInterface::class => new EngineMarkTwo(),
        ]);

        $getEngineName = static function (EngineInterface $engine, ColorInterface $color) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $injector->invoke($getEngineName);
    }

    public function testInvokeMissingRequiredClassParameterOnFunction(): void
    {
        $container = new Container();

        $this->expectExceptionMessage('Parameter 1 of "trim()" accepts "string", "bool" passed.');
        $this->expectException(InvalidParameterTypeException::class);

        $result = (new Injector($container))->invoke('trim', ['string' => false]);
    }

    public function testInvokeWithNonStaticMethod(): void
    {
        $this->expectExceptionMessage('Non-static method "getNameNonStatic" on class "Chiron\Injector\Test\Support\StaticMethod" should not be called statically.');
        $this->expectException(NotCallableException::class);

        $container = new Container();

        $result = (new Injector($container))->invoke([StaticMethod::class, 'getNameNonStatic']);
    }

    public function testInvokeWithPrivateAndNonStaticMethod(): void
    {
        $this->expectExceptionMessage('Method "getNamePrivateAndNonStatic" on class "Chiron\Injector\Test\Support\StaticMethod" should be public.');
        $this->expectException(NotCallableException::class);

        $container = new Container();

        $result = (new Injector($container))->invoke([StaticMethod::class, 'getNamePrivateAndNonStatic']);
    }

    /**
     * @dataProvider getUndefinedControllers
     */
    public function testGetControllerWithUndefinedController($controller, $exceptionMessage = null)
    {
        $this->expectExceptionMessage($exceptionMessage);
        $this->expectException(NotCallableException::class);

        $container = new Container([
            ControllerTest::class      => new ControllerTest(),
            ControllerEmptyTest::class => new ControllerEmptyTest(),
        ]);

        $result = (new Injector($container))->invoke($controller);
    }

    public function getUndefinedControllers()
    {
        $controller = new ControllerTest();
        $controllerEmpty = new ControllerEmptyTest();

        return [
            ['', '"" is neither a php callable nor a valid container entry.'],
            ['foo', '"foo" is neither a php callable nor a valid container entry.'],
            ['oof::bar', '"oof" is neither a class name nor a valid container entry.'],
            [['oof', 'bar'], '"oof" is neither a class name nor a valid container entry.'],
            ['::', '"" is neither a class name nor a valid container entry.'],
            ['::B', '"" is neither a class name nor a valid container entry.'],
            ['A::', '"A" is neither a class name nor a valid container entry.'],
            ['Chiron\Injector\Test\ControllerTest::staticsAction', 'Expected method "staticsAction" on class "Chiron\Injector\Test\ControllerTest", did you mean "staticAction"?'],
            ['Chiron\Injector\Test\ControllerTest::privateAction', 'Method "privateAction" on class "Chiron\Injector\Test\ControllerTest" should be public.'],
            ['Chiron\Injector\Test\ControllerTest::protectedAction', 'Method "protectedAction" on class "Chiron\Injector\Test\ControllerTest" should be public.'],
            ['Chiron\Injector\Test\ControllerTest::undefinedAction', 'Expected method "undefinedAction" on class "Chiron\Injector\Test\ControllerTest". Available methods: "publicAction", "staticAction".'],
            ['Chiron\Injector\Test\ControllerTest', 'Controller class "Chiron\Injector\Test\ControllerTest" cannot be called without a method name. You need to implement "__invoke" or use one of the available methods: "publicAction", "staticAction".'],
            ['Chiron\Injector\Test\ControllerEmptyTest', 'Controller class "Chiron\Injector\Test\ControllerEmptyTest" cannot be called without a method name. You need to implement "__invoke".'],
            ['Chiron\Injector\Test\ControllerEmptyTest::undefined', 'xpected method "undefined" on class "Chiron\Injector\Test\ControllerEmptyTest".'],
            [[$controller, 'staticsAction'], 'Expected method "staticsAction" on class "Chiron\Injector\Test\ControllerTest", did you mean "staticAction"?'],
            [[$controller, 'privateAction'], 'Method "privateAction" on class "Chiron\Injector\Test\ControllerTest" should be public.'],
            [[$controller, 'protectedAction'], 'Method "protectedAction" on class "Chiron\Injector\Test\ControllerTest" should be public.'],
            [[$controller, 'undefinedAction'], 'Expected method "undefinedAction" on class "Chiron\Injector\Test\ControllerTest". Available methods: "publicAction", "staticAction".'],
            [$controller, 'Controller class "Chiron\Injector\Test\ControllerTest" cannot be called without a method name. You need to implement "__invoke" or use one of the available methods: "publicAction", "staticAction".'],
            [$controllerEmpty, 'Controller class "Chiron\Injector\Test\ControllerEmptyTest" cannot be called without a method name. You need to implement "__invoke".'],
            [[$controllerEmpty, 'undefined'], 'Expected method "undefined" on class "Chiron\Injector\Test\ControllerEmptyTest".'],
            [['a' => 'foo', 'b' => 'bar'], 'Invalid array callable, expected [controller, method].'],
            [['foobar'], 'Invalid array callable, expected [controller, method].'],
            [[], 'Invalid array callable, expected [controller, method].'],
            [null, 'Invalid type for controller given, expected string, array or object, got "null".'],
        ];
    }

    public function testInvokeCallStatic(): void
    {
        $container = new Container();

        $result = (new Injector($container))->invoke([CallStaticObject::class, 'foo']);

        $this->assertSame('bar', $result);
    }

    /**
     * @dataProvider dataInvokeStaticWithStaticCalls
     */
    public function testInvokeStaticWithStaticCalls(string $className): void
    {
        if (
            $className === CallStaticWithStaticObject::class
            && version_compare(PHP_VERSION, '8.1.0', '<')
        ) {
            /** @link https://bugs.php.net/bug.php?id=81626 */
            $this->markTestSkipped('Bug in PHP version below 8.1. See https://bugs.php.net/bug.php?id=81626');
        }

        $container = new Container();

        $result = (new Injector($container))->invoke([$className, 'foo']);

        $this->assertSame('bar', $result);
    }

    public function dataInvokeStaticWithStaticCalls(): array
    {
        return [
            [CallStaticWithStaticObject::class],
            [CallStaticWithSelfObject::class],
            [StaticWithStaticObject::class],
            [StaticWithSelfObject::class],
        ];
    }

    /**
     * Injector should be able to invoke static method.
     */
    public function testInvokeAnonymousClass(): void
    {
        $container = new Container([
            EngineInterface::class => new EngineMarkTwo(),
        ]);

        $class = new class () {
            public EngineInterface $engine;

            public function setEngine(EngineInterface $engine): void
            {
                $this->engine = $engine;
            }
        };

        (new Injector($container))->invoke([$class, 'setEngine']);

        $this->assertInstanceOf(EngineInterface::class, $class->engine);
    }

    /**
     * A values collection for a variadic argument can be passed as an array in a named parameter.
     */
    public function testAloneScalarVariadicParameterAndNamedArrayArgument(): void
    {
        $container = new Container();

        $callable = fn (int ...$var) => array_sum($var);

        $result = (new Injector($container))->invoke($callable, ['var' => [1, 2, 3], new stdClass()]);

        $this->assertSame(6, $result);
    }

    public function testAloneScalarVariadicParameterAndNamedAssocArrayArgument(): void
    {
        $container = new Container();

        $callable = fn (string $foo, string ...$bar) => $foo . '--' . implode('-', $bar);

        $result = (new Injector($container))
            ->invoke($callable, ['foo' => 'foo', 'bar' => ['foo' => 'baz', '0' => 'fiz']]);

        $this->assertSame('foo--baz-fiz', $result);
    }

    public function testAloneScalarVariadicParameterAndNamedScalarArgument(): void
    {
        $container = new Container();

        $callable = fn (int ...$var) => array_sum($var);

        $result = (new Injector($container))->invoke($callable, ['var' => 42, new stdClass()]);

        $this->assertSame(42, $result);
    }

    /**
     * If type of a variadic argument is a class and named parameter with values collection is not set then injector
     * will search for objects by class name among all unnamed parameters.
     */
    public function testVariadicArgumentUnnamedParams(): void
    {
        $container = new Container([DateTimeInterface::class => new DateTimeImmutable()]);

        $callable = fn (DateTimeInterface $dateTime, EngineInterface ...$engines) => count($engines);

        $result = (new Injector($container))->invoke(
            $callable,
            [new EngineMarkTwo(), new stdClass(), new EngineMarkTwo(), new stdClass()]
        );

        $this->assertSame(2, $result);
    }

    public function testVariadicDoesntUseTheContainer(): void
    {
        $container = new Container();

        $this->expectExceptionMessage('Missing required value for parameter "$engines" when calling "Chiron\Injector\Test\InjectorTest::Chiron\Injector\Test\{closure}"');
        $this->expectException(MissingRequiredParameterException::class);

        $callable = fn (EngineInterface ...$engines) => count($engines);

        $result = (new Injector($container))->invoke(
            $callable,
            ['useless' => EngineMarkTwo::class, new stdClass()]
        );
    }

    public function testUnionTypeVariadicArgumentUnnamedParams(): void
    {
        $container = new Container();

        $callable = fn (DateTimeInterface|EngineInterface ...$engines) => count($engines);

        $result = (new Injector($container))->invoke(
            $callable,
            ['engines' => [new EngineMarkTwo(), new DateTimeImmutable(), new EngineMarkTwo()]]
        );

        $this->assertSame(3, $result);
    }

    /**
     * If calling method have an untyped variadic argument then all remaining unnamed parameters will be passed.
     */
    public function testVariadicMixedArgumentWithMixedParams(): void
    {
        $container = new Container([DateTimeInterface::class => new DateTimeImmutable()]);

        $callable = fn (...$engines) => $engines;

        $result = (new Injector($container))->invoke(
            $callable,
            ['engines' => [new EngineMarkTwo(), new stdClass(), new EngineMarkTwo(), new stdClass()]]
        );

        $this->assertCount(4, $result);
    }

    /**
     * Any unnamed parameter can only be an object. Scalar, array, null and other values can only be named parameters.
     */
    public function testVariadicStringArgumentWithUnnamedStringsParams(): void
    {
        $container = new Container([DateTimeInterface::class => new DateTimeImmutable()]);

        $callable = fn (string ...$engines) => $engines;

        $this->expectException(InjectorException::class);
        $this->expectExceptionMessage('Invalid arguments array. Non-object argument should be named explicitly when passed.');

        (new Injector($container))->invoke($callable, ['str 1', 'str 2', 'str 3']);
    }

    /**
     * In the absence of other values to a nullable variadic argument `null` is not passed by default.
     */
    public function testNullableVariadicArgument(): void
    {
        $container = new Container();

        $callable = fn (?EngineInterface ...$engines) => $engines;

        $result = (new Injector($container))->invoke($callable, ['engines' => null]);

        $this->assertSame([null], $result);
    }

    public function testNullableVariadicArgumentUsingNull(): void
    {
        $container = new Container();

        $callable = fn (?EngineInterface ...$engines) => $engines;

        $result = (new Injector($container))->invoke($callable, ['engines' => null]);

        $this->assertSame([null], $result);
    }

    public function testNullableVariadicArgumentUsingObject(): void
    {
        $container = new Container();

        $callable = fn (?EngineInterface ...$engines) => $engines;

        $engine = new EngineMarkTwo();
        $result = (new Injector($container))->invoke($callable, [$engine]);

        $this->assertSame([$engine], $result);
    }

    public function testInvokeReferencedArgumentNamedVariadic(): void
    {
        $container = new Container();

        $callable = static function (DateTimeInterface &...$dates) {
            $dates[0] = false;
            $dates[1] = false;

            return count($dates);
        };
        $foo = new DateTimeImmutable();
        $bar = new DateTimeImmutable();
        $baz = new DateTimeImmutable();

        $result = (new Injector($container))
            ->invoke($callable, [
                $foo,
                &$bar,
                &$baz,
                new DateTime(),
            ]);
        unset($baz);

        $this->assertSame(4, $result);
        $this->assertInstanceOf(DateTimeImmutable::class, $foo);
        $this->assertFalse($bar);
    }
}


class ControllerTest
{
    public function __construct()
    {
    }

    public function __toString(): string
    {
        return '';
    }

    public function publicAction()
    {
    }

    private function privateAction()
    {
    }

    protected function protectedAction()
    {
    }

    public static function staticAction()
    {
    }
}

class ControllerEmptyTest
{
}
