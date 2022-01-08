<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\InjectorException;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

trait ReflectorTrait
{
    // https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L210
    protected function reflectClass(string $class): ReflectionClass
    {
        // TODO : Lever une exception si la classe n'existe pas, et à ce moment là le try/catch n'est plus nécessaire !!!!

        // TODO : vérifier que le constructeur est public !!!! => https://github.com/PHP-DI/PHP-DI/blob/cdcf21d2a8a60605e81ec269342d48b544d0dfc7/src/Definition/Source/ReflectionBasedAutowiring.php#L31
        // TODO : déplacer ce bout de code dans une méthode "reflectClass()"
        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L508
        // Throws a \ReflectionException if the class does not exist.
        try {
            // TODO : ajouter une sorte de cache qui permet de stocker la reflection dans un tableau de cache pour s'en reservir plus tard !!!!
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            // TODO : créer un ClassNotFoundException::class
            // TODO : utiliser la classe NotInstantiableException et mettre un message plus générique du genre : Class %s is not instanciable - $e->getMessage()
            throw new InjectorException(sprintf('Class "%s" does not exist.', $class), $e->getCode(), $e);
        }

        // TODO : ajouter une gestion des exceptions circulaires.
        // TODO : améliorer la gestion des classes non instanciables => https://github.com/illuminate/container/blob/master/Container.php#L1001

        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L409
        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L510
        // TODO : gérer un message d'erreur différent si on a un constructeur privé ou protected ??? https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L207
        // TODO : sortir ce test du isInstanciable et le reporter aprés cette méthode car on souhaite ici refléter une class et pas forcément ajouter de la logique métier dans cette fonction !!!!
        // Not instantiable in case of class Abstract/Trait/Interface or with a private/protected constructor.
        if (! $reflection->isInstantiable()) {
            // https://github.com/illuminate/container/blob/master/Container.php#L1079
            // TODO : créer un ClassNotInstantiableException::class
            throw new InjectorException(sprintf('Class "%s" is not instantiable.', $class));
        }

/*
        if (!class_exists($entity)) {
            throw new ServiceCreationException(sprintf("Class '%s' not found.", $entity));
        } elseif ((new ReflectionClass($entity))->isAbstract()) {
            throw new ServiceCreationException(sprintf('Class %s is abstract.', $entity));
        } elseif (($rm = (new ReflectionClass($entity))->getConstructor()) !== null && !$rm->isPublic()) {
            throw new ServiceCreationException(sprintf('Class %s has %s constructor.', $entity, $rm->isProtected() ? 'protected' : 'private'));
        } elseif ($constructor = (new ReflectionClass($entity))->getConstructor()) {
            $arguments = self::autowireArguments($constructor, $arguments, $getter);
            $this->addDependency($constructor);
        } elseif ($arguments) {
            throw new ServiceCreationException(sprintf(
                'Unable to pass arguments, class %s has no constructor.',
                $entity,
            ));
        }
*/

        return $reflection;
    }

    // https://github.com/PHP-DI/Invoker/blob/master/src/Reflection/CallableReflection.php#L23
    // https://github.com/Wandu/Reflection/blob/eca8daed402eb4706af6dd403879c88655f38b7d/src/ReflectionCallable.php#L76
    // https://github.com/eslym/php-router/blob/96e449b5869338d2b3c30eb3eeb99637c4349204/src/ReflectionCallable.php#L40
    // TODO : indiquer que le warp dans une closure permet aussi de faciliter l'invokation de la reflectionfunction en lui passant uniquement en paramétre les arguments.
    //https://github.com/J7mbo/Auryn/blob/master/lib/Executable.php#L41
    protected function reflectCallable(callable $callable): ReflectionFunction
    {
        // TODO : il faudrait pas vérifier que ce n'est pas DEJA une Closure avant de faire le wrap ???? ou PHP est suffisament malin pour ne pas faire un "wrap de wrap"
        // Wrap the callable in a Closure to simplify the reflection code.
        $callable = Closure::fromCallable($callable);
        $reflection = new ReflectionFunction($callable);

        // TODO : il faudrait surement lever une exception si $reflection->isUserDefined() est à faux, cela bloquera l'utilisation des méthodes __call et __callStatic qui sont compliquées à résoudre. Et parcontre le contre coup c'est que l'on va aussi interdire d'accéder aux fonctions natives PHP par exemple 'trim' ou 'str' si on le appelles de cette maniére cela va aussi lever l'exception !!! ou alors éventuellement utiliser de manière conditionnelle l'instruction "function_exists(xxx)" pour gérer ce cas là !!!!

        return $reflection;
    }

/*
// TODO : attention cela plante avec l'utilisation des méthodes magiques __call et __callStatic !!! car la méthode "virtuelle" n'existe pas vraiment dans la classe !!!!
    protected function reflectCallable_OLD(callable $callable): \ReflectionFunctionAbstract
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        } elseif (is_object($callable) && ! $callable instanceof \Closure)
            $callable = [$callable, '__invoke']; {
        }

        return is_array($callable)
                        ? new \ReflectionMethod($callable[0], $callable[1])
                        : new \ReflectionFunction($callable);
    }
*/

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     *
     * @return \ReflectionFunctionAbstract
     *
     * @throws \ReflectionException
     */
    /*
    //https://github.com/illuminate/container/blob/master/BoundMethod.php#L138
    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && ! $callback instanceof Closure)
            $callback = [$callback, '__invoke']; {
        }

        return is_array($callback)
                        ? new ReflectionMethod($callback[0], $callback[1])
                        : new ReflectionFunction($callback);
    }*/
}
