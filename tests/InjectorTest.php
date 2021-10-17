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
    public function testInvoke(): void
    {
        $container = new Container([EngineInterface::class => new EngineMarkTwo()]);

        $getEngineName = static function (EngineInterface $engine) {
            return $engine->getName();
        };

        $engineName = (new Injector($container))->invoke($getEngineName);

        $this->assertSame('Mark Two', $engineName);
    }

    public function testInvokeWithStaticMethod(): void
    {
        $container = new Container();
        $engineName = (new Injector($container))->invoke([StaticMethod::class, 'getName']);

        $this->assertSame('Mark Two', $engineName);
    }

    // TODO : améliorer les tests !!!! https://github.com/PHP-DI/Invoker/blob/master/tests/InvokerTest.php#L363
    public function testInvokeWithNonStaticMethod(): void
    {
        $this->expectExceptionMessage('Cannot call getNameNonStatic() on Chiron\Injector\Test\Support\StaticMethod because it is not a valid container entry.');
        $this->expectException(NotCallableException::class);

        $container = new Container();
        $engineName = (new Injector($container))->invoke([StaticMethod::class, 'getNameNonStatic']);

        $this->assertSame('Mark Two', $engineName);
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
