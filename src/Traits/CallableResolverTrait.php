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
     * @throws NotCallableException
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    // TODO : regrouper les 2 méthode resolveCallable + resolveFromContainer en une seul méthode !!!
    protected function resolveCallable($callable): callable
    {
        // The callable is a string in the form 'class::method'
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable, 2);
        }

        // Try to resolve the callable using the container.
        $resolved = $this->resolveFromContainer($callable);

        if (! is_callable($resolved)) {
            throw NotCallableException::fromInvalidCallable($callable);
        }

        return $resolved;
    }

    /**
     * @param callable|string|array $callable
     *
     * @return callable|mixed Could be a callable, an array[object, string $method] in case the method is private or protected, or unrecognized stuff
     *
     * @throws NotCallableException
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    private function resolveFromContainer($callable)
    {
        // If it's already a callable there is nothing to do.
        // TODO : remplacer par un simple is_callable lorsqu'on sera passé en php8 !!!
        if ($this->isCallable($callable)) {
            return $callable;
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            try {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]); // TODO : eventuellement faire un : if $container->has(XXXX) { $container->get(XXX) } plutot que de faire un try/catch !!!

                return $callable;
            } catch (NotFoundExceptionInterface $e) {
                throw new NotCallableException(sprintf(
                    'Cannot call %s() on %s because it is not a valid container entry.',
                    $callable[1],
                    $callable[0]
                ));
            }
        }

        // Unrecognized stuff, we let it fail later
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
