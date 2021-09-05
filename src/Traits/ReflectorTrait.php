<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\Exception\InjectorException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionObject;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Closure;
use RuntimeException;
use ReflectionFunctionAbstract;
use InvalidArgumentException;
use Throwable;

use ReflectionMethod;
use ReflectionException;
use Reflector;

use Chiron\Injector\Reflection;

trait ReflectorTrait
{
    protected function reflectClass(string $class): ReflectionClass
    {
        // TODO : Lever une exception si la classe n'existe pas, et à ce moment là le try/catch n'est plus nécessaire !!!!

        // TODO : vérifier que le constructeur est public !!!! => https://github.com/PHP-DI/PHP-DI/blob/cdcf21d2a8a60605e81ec269342d48b544d0dfc7/src/Definition/Source/ReflectionBasedAutowiring.php#L31
        // TODO : déplacer ce bout de code dans une méthode "reflectClass()"
        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L508
        // Throws a \ReflectionException if the class does not exist.
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            // TODO : créer un ClassNotFoundException::class
            // TODO : utiliser la classe NotInstantiableException et mettre un message plus générique du genre : Class %s is not instanciable - $e->getMessage()
            throw new InjectorException(sprintf('Class "%s" does not exist.',$class), $e->getCode(), $e);
        }

        // TODO : ajouter une gestion des exceptions circulaires.
        // TODO : améliorer la gestion des classes non instanciables => https://github.com/illuminate/container/blob/master/Container.php#L1001

        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L409
        // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L510
        // TODO : gérer un message d'erreur différent si on a un constructeur privé ou protected ??? https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L207
        // TODO : sortir ce test du isInstanciable et le reporter aprés cette méthode car on souhaite ici refléter une class et pas forcément ajouter de la logique métier dans cette fonction !!!!
        // Prevent error if you try to instanciate an abstract class or a class with a private constructor ou un Trait ou une Interface !!!.
        if (! $reflection->isInstantiable()) {
            // https://github.com/illuminate/container/blob/master/Container.php#L1079
            // TODO : créer un ClassNotInstantiableException::class
            throw new InjectorException(sprintf('Class "%s" is not instantiable.',$class));
        }

        return $reflection;
    }

    // TODO : indiquer que le warp dans une closure permet aussi de faciliter l'invokation de la reflectionfunction en lui passant uniquement en paramétre les arguments.
    protected function reflectCallable(callable $callable): ReflectionFunction
    {
        // Wrap the callable in a Closure to simplify the reflection code.
        $callable = Closure::fromCallable($callable);
        $reflection = new ReflectionFunction($callable);

        return $reflection;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param  \ReflectionParameter  $parameter
     * @return string|null
     */
    // https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Util.php#L52
    /*
    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }*/
}
