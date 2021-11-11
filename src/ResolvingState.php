<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Chiron\Injector\Exception\InjectorException;
use Chiron\Injector\Exception\InvalidParameterTypeException;
use Generator;
use InvalidArgumentException;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use ReflectionParameter;

// TODO : renommer en InjectorResolvingState::class ???
final class ResolvingState
{
    private $reflection;

    /**
     * @var array<int, object>
     */
    private $numericArguments = [];

    /**
     * @var array<string, mixed>
     */
    private $namedArguments = [];

    /**
     * @var array<mixed>
     */
    private $resolvedValues = [];

    /**
     * Invoker constructor.
     *
     * @param $container
     */
    public function __construct(ReflectionFunctionAbstract $reflection, array $arguments)
    {
        $this->reflection = $reflection;
        $this->sortArguments($arguments);
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
     * @psalm-param class-string|null $className
     */
    public function resolveParameterByClass(string $className, bool $variadic): bool
    {
        $generator = $this->pullNumericArgument($className);

        if (! $variadic) {
            // TODO : regarder dans quel cas le generator peut etre invalid !!!
            if (! $generator->valid()) {
                return false;
            }
            $value = $generator->current();
            $this->addResolvedValue($value);

            return true;
        }

        foreach ($generator as &$value) {
            $this->addResolvedValue($value);
        }

        return true;
    }

    /**
     * @psalm-param class-string|null $className
     *
     * @psalm-return Generator<int, object, mixed, void>
     */
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
    public function getResolvedValues(): array
    {
        // Raise an exception if the typehint is not respected.
        $this->checkParametersTypes($this->reflection, $this->resolvedValues);

        return $this->resolvedValues;
    }

    /**
     * @param array $arguments
     *
     * @throws InjectorException
     */
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
                } catch (InvalidParameterTypeException $e) {
                }
            }

            // TODO : rendre le code plus propre en stockant dans l'exception le type et en ajoutant un getType() plutot que de bidouiller en stockant le type dans le code de l'exception.
            throw new InvalidParameterTypeException($e->getCode(), $parameter);
        }

        if ($reflectionType instanceof ReflectionIntersectionType) {
            foreach ($reflectionType->getTypes() as $t) {
                $this->checkType($value, $parameter, $t);
            }

            return;
        }

        // TODO : ---- START Mutualiser le code avec la méthode getParameterClassName car il y a du code dupliqué !!!! ----
        if (! $reflectionType instanceof ReflectionNamedType) {
            return;
        }

        $type = $reflectionType->getName();

        // TODO : il faudrait pas gérer aussi le cas du 'parent' ????
        // TODO : attention il se peut que le getDeclaringClass retourne null !!! donc le getName lévera une erreur !!!!
        if ($type === 'self') {
            $type = $parameter->getDeclaringClass()->getName();
        }
        // TODO : ---- END ----



        if ($value === null && $parameter->allowsNull()) {
            return;
        }

        if (is_object($value)) {
            $class = get_class($value);

            if (is_a($class, $type, true)) {
                return;
            }
            // Function is_string() will not consider an object with __toString() as a string.
            if ('string' === $type && method_exists($class, '__toString')) {
                return;
            }
        }

        if ($reflectionType->isBuiltin()) {
            if ('mixed' === $type) {
                return;
            }
            if ('false' === $type) {
                if (false === $value) {
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
