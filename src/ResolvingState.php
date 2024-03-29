<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Chiron\Injector\Exception\InjectorException;
use Chiron\Injector\Exception\InvalidParameterTypeException;
use Generator;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

// TODO : renommer en InjectorResolvingState::class ???
final class ResolvingState
{
    private ReflectionFunctionAbstract $reflection;
    /**
     * @psaml-var array<int, object>
     */
    private array $numericArguments = [];
    /**
     * @psaml-var array<string, mixed>
     */
    private array $namedArguments = [];
    /**
     * @psalm-var list<mixed>
     */
    private array $resolvedValues = [];

    /**
     * Invoker constructor.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array $arguments
     */
    // TODO : virer le paramétre $reflection + la variable de classe qui porte cette information.
    // TODO : remonter le code de la méthode sortArguments directement dans le constructeur !!!
    public function __construct(ReflectionFunctionAbstract $reflection, array $arguments)
    {
        $this->reflection = $reflection;
        $this->sortArguments($arguments);
    }

    /**
     * @param array $arguments
     *
     * @throws InjectorException
     */
    // TODO : code à remonter dans le constructeur !!!
    private function sortArguments(array $arguments): void
    {
        foreach ($arguments as $key => &$value) {
            if (is_int($key)) {
                if (! is_object($value)) {
                    //'Injector expect an associative array except for object items.'
                    //protected const EXCEPTION_MESSAGE = 'Invalid argument "%s" when calling "%s"%s. Non-object arguments should be named explicitly when passed.';
                    //throw new InvalidArgumentException($this->reflection, (string) $key);
                    //https://github.com/yiisoft/injector/blob/3df9b504ef721e192dac06d812b5c5b9f8df4b42/src/InvalidArgumentException.php#L7

                    // We expect an associative array for the non-object values.
                    throw new InjectorException('Invalid arguments array. Non-object argument should be named explicitly when passed.');
                }
                $this->numericArguments[] = &$value;
            } else {
                $this->namedArguments[$key] = &$value;
            }
        }
    }

    public function resolveParameterByName(string $name, bool $variadic): bool
    {
        if (! array_key_exists($name, $this->namedArguments)) {
            return false;
        }

        if ($variadic && is_array($this->namedArguments[$name])) {
            array_walk($this->namedArguments[$name], [$this, 'addResolvedValue']);
        } else {
            $this->addResolvedValue($this->namedArguments[$name]);
        }

        return true;
    }

    /**
     * @psalm-param class-string $className
     */
    // TODO : renommer le paramétre en $class tout simplement !!!
    public function resolveParameterByClass(string $className, bool $variadic): bool
    {
        $generator = $this->pullNumericArgument($className);

        if (! $generator->valid()) {
            return false;
        }

        if ($variadic === true) {
            foreach ($generator as &$value) {
                $this->addResolvedValue($value);
            }
        } else {
            $value = $generator->current();
            $this->addResolvedValue($value);
        }

        return true;
    }

    /**
     * @psalm-param class-string|null $className
     *
     * @psalm-return Generator<int, object, mixed, void>
     */
    // TODO : renommer le paramétre en $class tout simplement !!!
    private function &pullNumericArgument(?string $className): Generator
    {
        foreach ($this->numericArguments as $key => &$value) {
            if ($value instanceof $className) {
                unset($this->numericArguments[$key]); // TODO : je pense que si on utilise pas l'option shouldPushTrailingArguments, le unset est inutile. Mais à confirmer en faisant plus de tests (notamment si on invoke/build une fonction qui prend en paramétre 2 fois la même classe mais qu'on ajoute en paramétre 2 instances différentes. ex : foobar(StdClass $class1, StdClass $class2) et qu'on fait un invoke('foobar', [$instance1, $instance2])  si on fait pas le unset on va surement boucler et toujours prendre la 1ere valeur/instance pour les 2 paramétres !!!)

                yield $value;
            }
        }
    }

    /**
     * @param mixed $value
     */
    public function addResolvedValue(&$value): void
    {
        $this->resolvedValues[] = &$value;
    }

    /**
     * @throws InvalidParameterTypeException When a parameter is not compatible with the declared type.
     */
    // TODO : ajouter en paramétre le $reflection ca évitera qu'on se la traine lors du contructeur !!!
    public function getResolvedValues(): array
    {
        // Raise an exception if the typehint is not respected.
        $this->checkParametersTypes($this->reflection, $this->resolvedValues);

        return $this->resolvedValues;
    }

    /**
     * @throws InvalidParameterTypeException When a parameter is not compatible with the declared type.
     */
    // TODO : renommer en assertParameterTypeMatch() ????
    private function checkParametersTypes(ReflectionFunctionAbstract $reflectionFunction, array $values): void
    {
        $reflectionParameters = $reflectionFunction->getParameters();
        $checksCount = min($reflectionFunction->getNumberOfParameters(), count($values));

        for ($i = 0; $i < $checksCount; ++$i) {
            if (! $reflectionParameters[$i]->hasType() || $reflectionParameters[$i]->isVariadic()) {
                continue;
            }

            $this->checkType($values[$i], $reflectionParameters[$i]);
        }

        if ($reflectionFunction->isVariadic() && ($lastParameter = end($reflectionParameters))->hasType()) {
            $variadicParameters = array_slice($values, $lastParameter->getPosition());

            foreach ($variadicParameters as $variadicParameter) {
                $this->checkType($variadicParameter, $lastParameter);
            }
        }
    }

    /**
     * @throws InvalidParameterTypeException When a parameter is not compatible with the declared type.
     */
    // https://github.com/aphiria/api/blob/1.x/src/Controllers/ControllerParameterResolver.php#L148
    // https://github.com/symfony/dependency-injection/blob/5.3/Compiler/CheckTypeDeclarationsPass.php#L161
    // TODO : à finir de coder !!!!
    // TODO : renommer en assertType()
    private function checkType($value, ReflectionParameter $parameter, ?ReflectionType $reflectionType = null): void
    {
        $reflectionType = $reflectionType ?? $parameter->getType();

        if ($reflectionType instanceof ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $t) {
                try {
                    $this->checkType($value, $parameter, $t);

                    return;
                } catch (InvalidParameterTypeException) {
                }
            }

            throw new InvalidParameterTypeException($parameter, $value);
        }

        if ($reflectionType instanceof ReflectionIntersectionType) {
            foreach ($reflectionType->getTypes() as $t) {
                $this->checkType($value, $parameter, $t);
            }

            return;
        }

        $type = $reflectionType->getName();

        // https://github.com/nette/utils/blob/508fb844b5636bb7f69c8bf0166403323cea755d/src/Utils/Type.php#L88
        if ($type === 'self') {
            $type = $parameter->getDeclaringClass()->getName();
        }

        if ($type === 'parent' && $parent = $parameter->getDeclaringClass()->getParentClass()) {
            $type = $parent->getName();
        }

        if ($value === null && $parameter->allowsNull()) {
            return;
        }

        if (is_object($value)) {
            $class = get_class($value);

            if (is_a($class, $type, true)) {
                return;
            }

            // TODO : faire plutot un check sur instanceof \Stringable une fois qu'on sera passé sur PHP8. Car : => If a class implements a __toString method, PHP automatically considers that it implements the Stringable interface.   +

            /*
            The is_string function checks the type of the variable and return true only if the type of the parameter provided is string. Because objects from classes that implement __toString are objects, is_string() function returns false on objects even if they comply with Stringable interface with or without explicit declaration.
            */

            // Function is_string() will not consider an object with __toString() as a "string".
            if ($type === 'string' && method_exists($class, '__toString')) {
                return;
            }
        }

        if ($reflectionType->isBuiltin()) {
            if ($type === 'mixed') {
                return;
            }
            if ($type === 'false') {
                if ($value === false) {
                    return;
                }
            }
            // Built-in : string/int/float/bool/array/object/callable/iterable/null.
            $checkFunction = sprintf('is_%s', $type);
            if ($checkFunction($value)) {
                return;
            }
        }

        throw new InvalidParameterTypeException($parameter, $value);
    }
}
