<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Chiron\Injector\Traits\CallableResolverTrait;
use Chiron\Injector\Traits\ParameterResolverTrait;
use Chiron\Injector\Traits\ReflectorTrait;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;

//https://github.com/laravel/framework/blob/8.x/src/Illuminate/Container/BoundMethod.php

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

// TODO : ajouter les interfaces InvokerInterface et FactoryInterface + créer une classe AbstractInjector (on étendrait de cette classe) qui utiliserait un trait pour résoudre les parameters et aurait les méthodes make+invoke, comme ca on pourrait aussi étendre de cette classe abstraite dans le Container sans avoir besoin de faire un new Injector($container) !!!!
// TODO : eventuellement créer une InjectorInterface qui implémenterai les 2 interfaces (InvokerInterface+FactoryInterface) et on pourrait binder comme singleton cette interface dans le constructeur du Container !!!!
final class Injector implements InvokerInterface, FactoryInterface
{
    use CallableResolverTrait;
    use ParameterResolverTrait;
    use ReflectorTrait;

    private ContainerInterface $container;

    /**
     * Invoker constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // TODO : améliorer le code regarder ici   =>   https://github.com/illuminate/container/blob/master/Container.php#L778
    // TODO : améliorer le code et regarder ici => https://github.com/thephpleague/container/blob/68c148e932ef9959af371590940b4217549b5b65/src/Definition/Definition.php#L225
    // TODO : attention on ne gére pas les alias, alors que cela pourrait servir si on veut builder une classe en utilisant l'alias qui est présent dans le container. Réfléchir si ce cas peut arriver.
    // TODO : renommer en buildClass() ????
    // TODO : améliorer le Circular exception avec le code : https://github.com/symfony/dependency-injection/blob/master/Container.php#L236
    // TODO : il n'y a pas un risque de références circulaires si on appel directement cette méthode qui est public.
    // TODO : ajouter le typehint pour le retour de la fonction avec "build(): object"
    public function build(string $class, array $parameters = []): object
    {
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L534
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L551
        // https://github.com/spiral/core/blob/02580dff7f1fcbc5e74caa1f78ea84c0e4c0d92e/src/Container.php#L558
        // TODO : améliorer ce bout de code, on fait 2 fois un new class, alors qu'on pourrait en faire qu'un !!! https://github.com/illuminate/container/blob/master/Container.php#L815
        // TODO : il faudrait que si il n'y a pas de constructeur la méthode resolveArguments retourne d'office un tableau vide, comme ca on peut faire un seul return avec un new $className qui aura en paramétre un tableau vide si le constructeur n'existe pas !!!! => https://github.com/PHP-DI/PHP-DI/blob/78278800b18e7c5582fd4d4e629715f5eebbfcc0/src/Definition/Resolver/ParameterResolver.php#L45

        // Throw an exception if the class is not found or not instantiable.
        $reflection = $this->reflectClass($class);
        $constructor = $reflection->getConstructor();

        // TODO : je pense qu'il faut aussi vérifier que le constructeur est public "if ($constructor && $constructor->isPublic()) {" : https://github.com/PHP-DI/PHP-DI/blob/b71d94b46e4505eed156b12882d1a0dd82f0530d/src/Definition/Source/ReflectionBasedAutowiring.php#L32
        if ($constructor !== null) {
            $arguments = $this->resolveDependencies($constructor, $parameters);
            $instance = $reflection->newInstanceArgs($arguments);
        } else {
            $instance = $reflection->newInstance();
        }

        return $instance;
    }

    /**
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of
     the Container. It additionally allow calling function using named parameters.
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
     *
     * @return mixed the callback return value.
     *
     * @throws MissingRequiredArgumentException  if required argument is missing.
     * @throws ContainerExceptionInterface if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotCallableException if the callable is invalid
     * @throws \ReflectionException
     */
     // TODO : corriger le phpdoc !!!!
    //$callable => callable|array|string
    // TODO : exemple pour gérer les paramétres qui ne sont pas avec un tableau associatif (code à utiliser que dans le cadre d'un invoke() ca n'a pas de sens de ne pas avoir de tableau associatif pour la partie du code ou on va builder un classe !!!! <== hum à vérifier si ce commentaire est pertinent NCOU) : https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L127
     //https://github.com/J7mbo/Auryn/blob/master/lib/Executable.php#L41
    public function invoke(mixed $callable, array $parameters = []): mixed
    {
        $callable = $this->resolveCallable($callable);
        $reflection = $this->reflectCallable($callable);
        // Try to match the callable parameters with the given parameters.
        $arguments = $this->resolveDependencies($reflection, $parameters);

        return $reflection->invokeArgs($arguments);
    }

    /**
     * Resolve a pseudo callable in a valid php callable.
     *
     * @param mixed $callable callable to be resolved.
     *
     * @return callable
     *
     * @throws Chiron\Injector\Exception\NotCallableException if $callable is not valid.
     * @throws ContainerExceptionInterface if an entry (ex: classname) cannot be resolved.
     */
    // TODO : ne pas exposer en "public" cette méthode, mais il faudrait plutot que les packages qui ont besoin de faire un resolveCallable intégre le trait CallableResolverTrait pour accéder à la fonction resolveCallable !!! ca serait plus propre que d'exposer cette méthode !!!!
    // TODO : renommer en resolveCallable !!!!
    public function resolve(mixed $callable): callable
    {
        return $this->resolveCallable($callable);
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
     * Invoke a callback with resolving dependencies based on parameter types.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function passing named arguments.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \Yiisoft\I18n\MessageFormatterInterface $formatter) {
     *    // ...
     * }
     *
     * $injector = new Yiisoft\Injector\Injector($container);
     * $injector->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first argument, and a formatter instance created
     * by the DI container as the second argument.
     *
     * @param callable $callable callable to be invoked.
     * @param array $arguments The array of the function arguments.
     * This can be either a list of arguments, or an associative array where keys are argument names.
     *
     * @throws MissingRequiredArgumentException if required argument is missing.
     * @throws ContainerExceptionInterface if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws ReflectionException
     *
     * @return mixed the callable return value.
     */
    /*
    //https://github.com/yiisoft/injector/blob/master/src/Injector.php#L62
    public function invoke(callable $callable, array $arguments = [])
    {
        $callable = Closure::fromCallable($callable);
        $reflection = new ReflectionFunction($callable);
        return $reflection->invokeArgs($this->resolveDependencies($reflection, $arguments));
    }*/


    /**
     * Creates an object of a given class with resolving constructor dependencies based on parameter types.
     *
     * This methods allows invoking a constructor and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling constructor passing named arguments.
     *
     * For example, the following constructor may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * class StringFormatter
     * {
     *     public function __construct($string, \Yiisoft\I18n\MessageFormatterInterface $formatter)
     *     {
     *         // ...
     *     }
     * }
     *
     * $injector = new Yiisoft\Injector\Injector($container);
     * $stringFormatter = $injector->make(StringFormatter::class, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first argument, and a formatter instance created
     * by the DI container as the second argument.
     *
     * @param string $class name of the class to be created.
     *
     * @psalm-param class-string $class
     *
     * @param array $arguments The array of the function arguments.
     * This can be either a list of arguments, or an associative array where keys are argument names.
     *
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException|MissingRequiredArgumentException
     * @throws ReflectionException
     *
     * @return mixed object of the given class.
     *
     * @psalm-suppress MixedMethodCall
     */
    /*
    //https://github.com/yiisoft/injector/blob/master/src/Injector.php#L107
    public function make(string $class, array $arguments = [])
    {
        $classReflection = new ReflectionClass($class);
        if (!$classReflection->isInstantiable()) {
            throw new \InvalidArgumentException("Class $class is not instantiable.");
        }
        $reflection = $classReflection->getConstructor();
        if ($reflection === null) {
            // Method __construct() does not exist
            return new $class();
        }

        return new $class(...$this->resolveDependencies($reflection, $arguments));
    }*/






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
     *
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
