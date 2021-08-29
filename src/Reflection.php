<?php

declare(strict_types=1);

namespace Chiron\Injector;

//https://github.com/nette/utils/blob/master/src/Utils/Reflection.php
//https://github.com/laravel/framework/blob/70490255a2249045699d0c9878f9fe847ad659b3/src/Illuminate/Support/Reflector.php#L64

final class Reflection
{
    /**
     * @return null|string
     */
    public static function getParameterClassName(\ReflectionParameter $param): ?string
    {
        try {
            return ($ref = $param->getClass()) ? $ref->getName() : null;
        } catch (\ReflectionException $e) {
            if (preg_match('#Class (.+) does not exist#', $e->getMessage(), $m)) {
                return $m[1];
            }
            throw $e;
        }
    }

    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see https://php.net/manual/en/reflectiontype.isbuiltin.php
     */
    public static function isBuiltinType(string $type):bool
    {
        return in_array($type, ['string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'void', 'parent', 'self']);

    }

    public static function getReturnType(\ReflectionFunctionAbstract $func): ?string
    {
        return $func->hasReturnType()
            ? self::normalizeType($func->getReturnType()->getName(), $func)
            : null;
    }

    public static function getParameterType(\ReflectionParameter $param): ?string
    {
        return $param->hasType()
            ? self::normalizeType($param->getType()->getName(), $param)
            : null;
    }

    public static function getPropertyType(\ReflectionProperty $prop): ?string
    {
        // only for PHP 7.4
        return PHP_VERSION_ID >= 70400 && $prop->hasType()
            ? self::normalizeType($prop->getType()->getName(), $prop)
            : null;
    }

    private static function normalizeType(string $type, $reflection): string
    {
        $lower = strtolower($type);
        if ($lower === 'self') {
            return $reflection->getDeclaringClass()->getName();
        } elseif ($lower === 'parent' && $reflection->getDeclaringClass()->getParentClass()) {
            return $reflection->getDeclaringClass()->getParentClass()->getName();
        } else {
            return $type;
        }
    }

    /**
     * @return mixed
     * @throws \ReflectionException when default value is not available or resolvable
     */
    public static function getParameterDefaultValue(\ReflectionParameter $param)
    {
        if ($param->isDefaultValueConstant()) {
            $const = $orig = $param->getDefaultValueConstantName();
            $pair = explode('::', $const);
            if (isset($pair[1])) {
                if (strtolower($pair[0]) === 'self') {
                    $pair[0] = $param->getDeclaringClass()->getName();
                }
                try {
                    $rcc = new \ReflectionClassConstant($pair[0], $pair[1]);
                } catch (\ReflectionException $e) {
                    $name = self::toString($param);
                    throw new \ReflectionException("Unable to resolve constant $orig used as default value of $name.", 0, $e);
                }
                return $rcc->getValue();
            } elseif (!defined($const)) {
                $const = substr((string) strrchr($const, '\\'), 1);
                if (!defined($const)) {
                    $name = self::toString($param);
                    throw new \ReflectionException("Unable to resolve constant $orig used as default value of $name.");
                }
            }
            return constant($const);
        }
        return $param->getDefaultValue();
    }

    /**
     * Are documentation comments available?
     */
    public static function areCommentsAvailable(): bool
    {
        static $res;
        return $res === null
            ? $res = (bool) (new \ReflectionMethod(__METHOD__))->getDocComment()
            : $res;
    }

    /**
     * Returns declaring class or trait.
     */
    public static function getPropertyDeclaringClass(\ReflectionProperty $prop): \ReflectionClass
    {
        foreach ($prop->getDeclaringClass()->getTraits() as $trait) {
            if ($trait->hasProperty($prop->getName())
                && $trait->getProperty($prop->getName())->getDocComment() === $prop->getDocComment()
            ) {
                return self::getPropertyDeclaringClass($trait->getProperty($prop->getName()));
            }
        }
        return $prop->getDeclaringClass();
    }

    public static function toString(\Reflector $ref): string
    {
        if ($ref instanceof \ReflectionClass) {
            return $ref->getName();
        } elseif ($ref instanceof \ReflectionMethod) {
            return $ref->getDeclaringClass()->getName() . '::' . $ref->getName();
        } elseif ($ref instanceof \ReflectionFunction) {
            return $ref->getName();
        } elseif ($ref instanceof \ReflectionProperty) {
            return self::getPropertyDeclaringClass($ref)->getName() . '::$' . $ref->getName();
        } elseif ($ref instanceof \ReflectionParameter) {
            return '$' . $ref->getName() . ' in ' . self::toString($ref->getDeclaringFunction()) . '()';
        } else {
            throw new \InvalidArgumentException;
        }
    }


    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     *
     * @throws \ReflectionException
     */
    /*
    // https://github.com/laravel/framework/blob/8.x/src/Illuminate/Container/BoundMethod.php#L138
    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && ! $callback instanceof Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
                        ? new ReflectionMethod($callback[0], $callback[1])
                        : new ReflectionFunction($callback);
    }*/

}
