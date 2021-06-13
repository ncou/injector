<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Chiron\Injector\Exception\CannotResolveException;
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

//https://github.com/yiisoft/injector/blob/master/src/Injector.php#L121

//https://github.com/illuminate/container/blob/master/BoundMethod.php#L158

//https://github.com/auraphp/Aura.Di/blob/4.x/src/Resolver/Resolver.php#L268

//https://github.com/symfony/symfony/blob/e60a876201b5b306d0c81a24d9a3db997192079c/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php#L188

final class Resolver
{
    /** ContainerInterface */
    private $container;

    /**
     * Invoker constructor.
     *
     * @param $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    //https://github.com/researchgate/injektor/blob/master/src/rg/injektor/DependencyInjectionContainer.php#L768
    public function resolveArguments(ReflectionFunctionAbstract $reflection, array $parameters = []): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {

            try {


                //Information we need to know about argument in order to resolve it's value
                $name = $parameter->getName();
                $class = $parameter->getClass();



            } catch (\ReflectionException $e) {

                //throw new CannotResolveException($parameter);

                // TODO : Remplacer le $fil et $line de l'exception avec le fichier php de la classe qui a levé l'exception ca sera plus clair dans le debuger lorsqu'on affichera le "snipet" du code.

                //Possibly invalid class definition or syntax error
                throw new InvalidArgumentException(sprintf('Invalid value for parameter %s', Reflection::toString($parameter)), $e->getCode(), $e);
                //throw new InvocationException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}", $e->getCode());
                //throw new InvocationException("Unresolvable dependency resolving [$parameter] in function " . $parameter->getDeclaringClass()->getName() . '::' . $parameter->getDeclaringFunction()->getName(), $e->getCode());
            }


            //die(var_dump($class));

            if (isset($parameters[$name]) && is_object($parameters[$name])) {
                //Supplied by user as object
                $arguments[] = $parameters[$name];
                continue;
            }
            //No declared type or scalar type or array
            if (empty($class)) {
                //Provided from outside
                if (array_key_exists($name, $parameters)) {
                    //Make sure it's properly typed
                    $this->assertType($parameter, $parameters[$name]);
                    $arguments[] = $parameters[$name];
                    continue;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    //Default value
                    //$arguments[] = $parameter->getDefaultValue();
                    $arguments[] = Reflection::getParameterDefaultValue($parameter);
                    continue;
                }
                //Unable to resolve scalar argument value
                throw new CannotResolveException($parameter);
            }

            try {
                //Requesting for contextual dependency
                $arguments[] = $this->container->get($class->getName());
                continue;
            } catch (ContainerExceptionInterface $e) {
                if ($parameter->isOptional()) {
                    //This is optional dependency, skip
                    $arguments[] = null;
                    continue;
                }
                throw $e;
            }
        }

        return $arguments;
    }

    /**
     * Assert that given value are matched parameter type.
     *
     * @param \ReflectionParameter        $parameter
     * @param mixed                       $value
     *
     * @throws CannotResolveException
     */
    private function assertType(ReflectionParameter $parameter, $value): void
    {
        if ($value === null) {
            if (!$parameter->isOptional() &&
                !($parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() === null)
            ) {
                throw new CannotResolveException($parameter);
            }
            return;
        }

        // TODO : utiliser la méthode hasType()
        $type = $parameter->getType();

        if ($type === null) {
            return;
        }

        // TODO : on devrait aussi vérifier que la classe est identique, et vérifier aussi le type string pour que cette méthode soit plus générique. Vérifier ce qui se passe si on fait pas cette vérification c'est à dire appeller une fonction avec des paramétres qui n'ont pas le bon typehint !!!!
        $typeName = $type->getName();
        if ($typeName == 'array' && !is_array($value)) {
            throw new CannotResolveException($parameter);
        }
        if (($typeName == 'int' || $typeName == 'float') && !is_numeric($value)) {
            throw new CannotResolveException($parameter);
        }
        if ($typeName == 'bool' && !is_bool($value) && !is_numeric($value)) {
            throw new CannotResolveException($parameter);
        }
    }
}
