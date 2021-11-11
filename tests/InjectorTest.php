<?php

declare(strict_types=1);

namespace Chiron\Injector\Test;

use Chiron\Injector\Exception\MissingRequiredParameterException;
use Chiron\Injector\Exception\NotCallableException;
use Chiron\Injector\Injector;
use Chiron\Injector\Test\Container\SimpleContainer as Container;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\StaticMethod;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

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

        $result = (new Injector($container))->invoke('strlen', ['str' => 'foobar']);

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
            ControllerTest::class => new ControllerTest(),
            ControllerEmptyTest::class => new ControllerEmptyTest()
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

    /**
     * @tes // TODO : à corriger !!!! + faire un test avec le method_exist voir le comportement !!!!
     */
    public function cannot_invoke_magic_method()
    {
        $this->expectExceptionMessage('Chiron\Injector\Test\InvokerTestMagicMethodFixture::foo() is not a callable. A __call() or __callStatic() method exists but magic methods are not supported.');
        $this->expectException(NotCallableException::class);

        $container = new Container();
        $result = (new Injector($container))->invoke([new InvokerTestMagicMethodFixture, 'foo']);
    }

    /**
     * @tes // TODO : à corriger !!!! + faire un test avec le method_exist voir le comportement !!!!
     */
    public function cannot_invoke_static_magic_method()
    {
        $this->expectExceptionMessage('Chiron\Injector\Test\InvokerTestStaticMagicMethodFixture::foo() is not a callable. A __call() or __callStatic() method exists but magic methods are not supported.');
        $this->expectException(NotCallableException::class);

        $container = new Container();
        $result = (new Injector($container))->invoke([InvokerTestStaticMagicMethodFixture::class, 'foo']);
    }











    /**
     * @requires PHP >= 8.0
     */
    /*
    public function testInjectionUsingUnionTypes(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('union type hint that cannot be inferred unambiguously');

        $container = new Container();

        $container->resolveArguments(new \ReflectionMethod(UnionTypes::class, 'example'));
    }*/
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

    // TODO : à virer !!!!
    public static function staticAction()
    {
    }
}

class ControllerEmptyTest
{
}

class InvokerTestMagicMethodFixture
{
    /** @var bool */
    public $wasCalled = false;
    public function __call(string $name, array $args): string
    {
        if ($name === 'foo') {
            $this->wasCalled = true;
            return 'bar';
        }
        throw new \Exception('Unknown method');
    }
}

class InvokerTestStaticMagicMethodFixture
{
    /** @var bool */
    public static $wasCalled = false;
    public static function __callStatic(string $name, array $args): string
    {
        if ($name === 'foo') {
            static::$wasCalled = true;
            return 'bar';
        }
        throw new \Exception('Unknown method');
    }
}
