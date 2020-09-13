<?php
namespace Chiron\Injector\Test;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Chiron\Container\Container;
use Chiron\Injector\Injector;
use Chiron\Injector\MissingRequiredArgumentException;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\StaticMethod;

/**
 * InjectorTest contains tests for \yii\di\Injector
 */
class InjectorTest extends TestCase
{
    public function testCall(): void
    {
        $container = new Container();

        $container->bind(EngineInterface::class, EngineMarkTwo::class);

        $getEngineName = static function (EngineInterface $engine) {
            return $engine->getName();
        };

        $engineName = (new Injector($container))->call($getEngineName);

        $this->assertSame('Mark Two', $engineName);
    }

    public function testCallWithStaticMethod(): void
    {
        $container = new Container();
        $engineName = (new Injector($container))->call([StaticMethod::class, 'getName']);

        $this->assertSame('Mark Two', $engineName);
    }

    public function testCallWithNonStaticMethod(): void
    {
        $container = new Container();
        $engineName = (new Injector($container))->call([StaticMethod::class, 'getNameNonStatic']);

        $this->assertSame('Mark Two', $engineName);
    }

    public function testMissingRequiredParameter(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $getEngineName = static function (EngineInterface $engine, $two) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $this->expectException(NotFoundExceptionInterface::class);
        $injector->call($getEngineName);
    }

    public function testMissingRequiredClassParameter(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $getEngineName = static function (EngineInterface $engine, ColorInterface $color) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $this->expectException(NotFoundExceptionInterface::class);
        $injector->call($getEngineName);
    }
}
