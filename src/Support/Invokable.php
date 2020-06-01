<?php

declare(strict_types=1);

namespace Chiron\Injector\Support;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\Exception\InvocationException;
use Chiron\Injector\Reflection\ReflectionCallable;
use Chiron\Injector\Reflection\Reflection;
use Chiron\Injector\Injector;
use Chiron\Injector\CallableResolver;
use ReflectionObject;
use ReflectionClass;
use ReflectionFunction;
use Closure;
use RuntimeException;
use ReflectionFunctionAbstract;
use InvalidArgumentException;
use Throwable;

class Invokable
{
    private $callback;

    /**
     * constructor.
     *
     * @param $callback callable|array|string
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(ContainerInterface $container)
    {
        $callable = (new CallableResolver($container))->resolve($this->callback);

        $invoker = new Injector($container);

        return $invoker->invoke($callable);
    }

}
