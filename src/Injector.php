<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\Exception\InvocationException;
use Chiron\Injector\Exception\NotCallableException;
use Chiron\Injector\ReflectionCallable;
use Chiron\Injector\ReflectionCallable2;
use Chiron\Injector\Reflection;
use Chiron\Injector\Resolver;
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


//https://github.com/rdlowrey/auryn/blob/master/lib/Injector.php#L237
//https://github.com/yiisoft/injector/blob/master/src/Injector.php

//https://github.com/yiisoft/factory/blob/master/src/Definitions/ArrayBuilder.php#L39

//https://github.com/binsoul/common-reflection/blob/aea5110dc9934c213e7508e48e337c995307868e/src/DefaultReflector.php
//https://github.com/dazet/data-map/blob/19eb76a8e22c23003e57a81cfec03dd0d0191fcb/src/Output/ObjectConstructor.php#L67
//https://github.com/stubbles/stubbles-reflect/blob/e0c64a24b2fe869ddf813e48e7d575dfb04c6bc6/src/main/php/functions.php#L39

//https://github.com/illuminate/container/blob/master/BoundMethod.php
//https://github.com/thirdgerb/container/blob/master/src/BoundMethod.php

// TODO : créer deux classes : Invoker::class et Factory::class qui auront des méthodes "static call()" et "static make()" avec en paramétre le container, et la classe Injector se chargera d'appeller ces méthodes. Cela permet de séparer le code source, mais aussi de pouvoir utiliser uniquement une partie du code (à savoir par exemple dans les classe AbstractBootloader::class on pourra directement faire un appel à Invoker::invoke() et idem pour la partie Factory. Cela évitera d'instancer une classe générique qui est Injector !!!)
// TODO : mettre le code du resolver dans un répertoire Trait et insérer directement le "Trait" dans les classes Factory et Invoker !!!!

final class Injector
{
    /** ContainerInterface */
    private $container;

    /** Resolver */
    private $resolver;

    /**
     * Invoker constructor.
     *
     * @param $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->resolver = new Resolver($container);
    }




    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    // TODO : renommer la fonction en "make()"
    // TODO : il n'y a pas un risque de références circulaires si on appel directement cette méthode qui est public.
    public function build(string $className, array $arguments = [])
    {
        // TODO : vérifier si on a besoin de cette méthode. Et il faudrait surement qu'elle soit aussi appellée dans la partie 'call()'
        $arguments = $this->resolveArguments($arguments);

        $class = $this->reflectClass($className);

        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L534
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L551
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L558
        // TODO : améliorer ce bout de code, on fait 2 fois un new class, alors qu'on pourrait en faire qu'un !!! https://github.com/illuminate/container/blob/master/Container.php#L815
        if ($constructor = $class->getConstructor()) {
            $arguments = $this->resolver->resolveArguments($constructor, $arguments);

            return new $className(...$arguments);
        }

        //$reflection->newInstanceArgs($resolved);
        return new $className();
    }

    // TODO : ajouter la signature dans l'interface
    // TODO : regarder aussi ici : https://github.com/mrferos/di/blob/master/src/Definition/AbstractDefinition.php#L75
    // TODO : regarder ici pour utiliser le arobase @    https://github.com/slince/di/blob/master/DefinitionResolver.php#L210
    // TODO : améliorer le resolve avec la gestion des classes "Raw" et "Reference" =>   https://github.com/thephpleague/container/blob/91a751faabb5e3f5e307d571e23d8aacc4acde88/src/Argument/ArgumentResolverTrait.php#L17
    // TODO : Faire des tests avec des paramétres Variadic (...$variadic) et avec un typehint 'object' qui est supporté depuis PHP 7.3 !!!!
    private function resolveArguments(array $arguments): array
    {
        foreach ($arguments as &$arg) {
            if (! is_string($arg)) {
                continue;
            }

            //if (! is_null($this->container) && $this->container->has($arg)) {
            if ($this->container->has($arg)) {
                $arg = $this->container->get($arg);

                continue;
            }
        }

        return $arguments;
    }

    //https://github.com/auraphp/Aura.Di/blob/4.x/src/Resolver/Reflector.php#L74
    //https://github.com/doctrine/instantiator/blob/master/src/Doctrine/Instantiator/Instantiator.php#L116
    //https://github.com/doctrine/instantiator/blob/master/src/Doctrine/Instantiator/Exception/InvalidArgumentException.php
    private function reflectClass(string $className): ReflectionClass
    {
        // TODO : vérifier si ce test class_exist() est vraiment utile, je pense qu'on peut laisser la ReflectionException qui sera surement levée lors du ReflectionClass($className) se propager.
        if (! class_exists($className)) {
            // TODO  : on devrait pas renvoyer une ContainerException ????
            throw new InvalidArgumentException("Entry '{$className}' cannot be resolved");
        }

        // TODO : vérifier que le constructeur est public !!!! => https://github.com/PHP-DI/PHP-DI/blob/cdcf21d2a8a60605e81ec269342d48b544d0dfc7/src/Definition/Source/ReflectionBasedAutowiring.php#L31
        // TODO : déplacer ce bout de code dans une méthode "reflectClass()"
        $reflection = new ReflectionClass($className);

        // TODO : ajouter une gestion des exceptions circulaires.
        // TODO : améliorer la gestion des classes non instanciables => https://github.com/illuminate/container/blob/master/Container.php#L1001

        // Prevent error if you try to instanciate an abstract class or a class with a private constructor.
        if (! $reflection->isInstantiable()) {
            throw new InvalidArgumentException(sprintf(
                'Entry "%s" cannot be resolved: the class is not instantiable',
                $className
            ));
        }

        return $reflection;
    }




    /**
     * Invoke a callable and inject its dependencies.
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return mixed
     */
    //https://github.com/yiisoft/injector/blob/master/src/Injector.php#L69
    /*
    public function call(callable $callable, array $args = [])
    {
        $args = $this->resolveArguments($args);

        $reflection = $this->reflectCallable($callable);

        return call_user_func_array(
                $callable,
                $this->getParameters($reflection, $args)
            );
    }*/







    /**
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * $container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function.
     * This can be either a list of parameters, or an associative array representing named function parameters.
     * @return mixed the callback return value.
     * @throws MissingRequiredArgumentException  if required argument is missing.
     * @throws ContainerExceptionInterface if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws \ReflectionException
     */
    //$callback => callable|array|string
    public function call($callback, array $params = [])
    {
        $resolved = (new CallableResolver($this->container))->resolve($callback);

        return $this->invoke($resolved, $params);
    }

    public function call2($callback, array $params = [])
    {
        $resolver = new CallableResolver($this->container);
        try {
            $resolved = $resolver->resolve($callback);
        } catch (NotCallableException $e) {
            // check if the method we try to call is private or protected.
            $resolved = $resolver->resolveFromContainer($callback);
            if (! is_callable($resolved) && is_array($resolved) && method_exists($resolved[0], $resolved[1])) {

                $reflection = new ReflectionMethod($resolved[0], $resolved[1]);
                $reflection->setAccessible(true);

                //Invoking factory method with resolved arguments
                return $reflection->invokeArgs(
                    $resolved[0],
                    $this->resolver->resolveArguments($reflection, $params)
                );


            }
        }

        return $this->invoke($resolved, $params);
    }

    public function invoke(callable $callable, array $args = [])
    {
        $reflection = new ReflectionCallable($callable);
        $parameters = $this->resolver->resolveArguments($reflection, $args);

        return call_user_func_array($callable, $parameters);
        //return $reflection->invoke($parameters);
    }

    //***********************


    public function call3($callback, array $params = [])
    {
        $resolved = (new CallableResolver($this->container))->resolveFromContainer($callback);

        return $this->invoke2($resolved, $params);
    }

    public function invoke2($callable, array $args = [])
    {
        $reflection = new ReflectionCallable2($callable);
        $parameters = $this->resolver->resolveArguments($reflection, $args);

        //return call_user_func_array($callable, $parameters);
        return $reflection->invokeArgs($parameters);
    }

    //**********************







/*
// TODO : utiliser ce bout de code et lever une exception si ce n'est pas un callable valide (throw InjectionException::fromInvalidCallable(xxx);)
//https://github.com/rdlowrey/auryn/blob/master/lib/InjectionException.php
//https://github.com/rdlowrey/auryn/blob/master/lib/Injector.php#L237
    private function isExecutable($exe)
    {
        if (is_callable($exe)) {
            return true;
        }
        if (is_string($exe) && method_exists($exe, '__invoke')) {
            return true;
        }
        if (is_array($exe) && isset($exe[0], $exe[1]) && method_exists($exe[0], $exe[1])) {
            return true;
        }

        return false;
    }
*/

    /**
     * @param string $type
     * @return bool
     */
    /*
    //https://github.com/zendframework/zend-di/blob/615fc00b55602d20506f228e939ac70792645e9b/src/Resolver/DependencyResolver.php#L208
    private function isCallableType(string $type): bool
    {
        if ($this->config->isAlias($type)) {
            $type = $this->config->getClassForAlias($type);
        }

        if (! class_exists($type) && ! interface_exists($type)) {
            return false;
        }

        $reflection = new ReflectionClass($type);

        return $reflection->hasMethod('__invoke')
            && $reflection->getMethod('__invoke')->isPublic();
    }*/


}
