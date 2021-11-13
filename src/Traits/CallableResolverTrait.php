<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\NotCallableException;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;

//https://github.com/PHP-DI/Invoker/blob/a812493e87bb4ed413584e4a98208f54c43475ec/src/CallableResolver.php
//https://github.com/yiisoft/yii-event/blob/master/src/CallableFactory.php#L55

// TODO : ajouter dans la phpdoc de cette classe qu'il faut que la variable de classe $this->container soit alimentée !!!
trait CallableResolverTrait
{
    /**
     * Resolve the given callable into a real PHP callable.
     *
     * @param callable|mixed $callable
     *
     * @return callable Real PHP callable.
     *
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     * @throws NotCallableException
     */
    // TODO : passer cette méthode en public ????
    protected function resolveCallable($callable): callable
    {
        // The callable is a string in the form 'class::method'
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable, 2);
        }

        $callable = $this->resolveFromContainer($callable);

        // TODO : remplacer par un simple is_callable lorsqu'on sera passé en php8 !!!
        if (! $this->isCallable($callable)) {
            throw new NotCallableException($callable);
        }

        return $callable;
    }

    /**
     * @param callable|mixed $callable
     *
     * @return callable|mixed
     *
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    private function resolveFromContainer($callable)
    {
        // If it's already a callable there is nothing to do.
        // TODO : remplacer par un simple is_callable lorsqu'on sera passé en php8 !!!
        if ($this->isCallable($callable)) {
            return $callable;
        }

        // The callable is a container entry name.
        if (is_string($callable)) {
            if ($this->container->has($callable)) {
                $callable = $this->container->get($callable);
            }
        }

        // Try to resolve the callable using the container.
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            if ($this->container->has($callable[0])) {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
            }
        }

        return $callable;
    }

    // TODO : Fonction à supprimer une fois qu'on sera passé en PHP8
    private function isCallable($callable): bool
    {
        // Shortcut for a very common use case
        if ($callable instanceof Closure) {
            return true;
        }

        // If it's already a callable there is nothing to do
        if (is_callable($callable)) {
            // TODO with PHP 8 that should not be necessary to check this anymore
            if (! $this->isStaticCallToNonStaticMethod($callable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the callable represents a static call to a non-static method.
     *
     * @param mixed $callable
     *
     * @throws ReflectionException
     */
    // TODO : Fonction à supprimer une fois qu'on sera passé en PHP8
    private function isStaticCallToNonStaticMethod($callable): bool
    {
        if (is_array($callable) && is_string($callable[0])) {
            [$class, $method] = $callable;

            if (! method_exists($class, $method)) {
                return false;
            }

            $reflection = new ReflectionMethod($class, $method);

            return ! $reflection->isStatic();
        }

        return false;
    }
}
