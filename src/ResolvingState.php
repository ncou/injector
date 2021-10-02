<?php

declare(strict_types=1);

namespace Chiron\Injector;

use Chiron\Injector\Exception\InvalidParameterTypeException;
use Generator;
use InvalidArgumentException;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use ReflectionParameter;

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

    /** @var bool */
    private $shouldPushTrailingArguments;

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
        $this->shouldPushTrailingArguments = ! $reflection->isInternal();
        $this->sortArguments($arguments);
    }

    /**
     * @param bool $condition If true then trailing arguments will not be passed.
     */
    public function disablePushTrailingArguments(bool $condition): void
    {
        $this->shouldPushTrailingArguments = $this->shouldPushTrailingArguments && ! $condition;
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

    public function getResolvedValues(): array
    {
        // Throw an exception if the typehint is not respected.
        $this->checkTypeDeclarations($this->reflection, $this->resolvedValues);

/*
        return $this->shouldPushTrailingArguments
            ? array_merge($this->resolvedValues, $this->numericArguments)
            : $this->resolvedValues;
*/

        return $this->resolvedValues;
    }

    /**
     * @param array $arguments
     *
     * @throws InvalidArgumentException
     */
    private function sortArguments(array $arguments): void
    {
        foreach ($arguments as $key => &$value) {
            if (is_int($key)) {
                if (! is_object($value)) {
                    throw new InvalidArgumentException($this->reflection, (string) $key); // TODO : créer l'exception qui va bien !
                }
                $this->numericArguments[] = &$value;
            } else {
                $this->namedArguments[$key] = &$value;
            }
        }
    }

    // TODO : renommer en checkArgumentTypes
    private function checkTypeDeclarations(ReflectionFunctionAbstract $reflectionFunction, array $values): void
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
     * @throws InvalidParameterTypeException When a parameter is not compatible with the declared type
     */
    // https://github.com/symfony/dependency-injection/blob/5.3/Compiler/CheckTypeDeclarationsPass.php#L161
    // TODO : à finir de coder !!!!
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
        if ($type === 'self') {
            $type = $parameter->getDeclaringClass()->getName();
        }
        // TODO : ---- END ----

        if ($value === null && $parameter->allowsNull()) {
            return;
        }

        if (is_object($value)) {
            $class = get_class($value);
        } else {
            $class = gettype($value);
            $class = ['integer' => 'int', 'double' => 'float', 'boolean' => 'bool'][$class] ?? $class;
        }

        if ($type === $class) {
            return;
        }

        if (is_a($class, $type, true)) {
            return;
        }

/*
        if (isset(static::$scalarTypes[$type]) && isset(static::$scalarTypes[$class])) {
            return;
        }
*/

        /*

        if ('string' === $type && method_exists($class, '__toString')) {
            return;
        }

        if ('callable' === $type && (\Closure::class === $class || method_exists($class, '__invoke'))) {
            return;
        }

        if ('callable' === $type && \is_array($value) && isset($value[0]) && ($value[0] instanceof Reference || $value[0] instanceof Definition || \is_string($value[0]))) {
            return;
        }

        if ('iterable' === $type && (\is_array($value) || 'array' === $class || is_subclass_of($class, \Traversable::class))) {
            return;
        }

        if ('object' === $type && !isset(self::BUILTIN_TYPES[$class])) {
            return;
        }

        if ('mixed' === $type) {
            return;
        }

        if ('false' === $type) {
            if (false === $value) {
                return;
            }
        } elseif ($reflectionType->isBuiltin()) {
            $checkFunction = sprintf('is_%s', $type);
            if ($checkFunction($value)) {
                return;
            }
        }
*/

        //throw new InvalidParameterTypeException($this->currentId, \is_object($value) ? $class : get_debug_type($value), $parameter);
        //throw new CannotResolveException($parameter);
        throw new InvalidParameterTypeException(is_object($value) ? $class : (is_object($value) ? get_class($value) : gettype($value)), $parameter);
    }
}
