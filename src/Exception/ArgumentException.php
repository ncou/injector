<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use ReflectionFunctionAbstract;

// https://github.com/yiisoft/injector/blob/master/src/ArgumentException.php

// EXEMPLE de CLOSURE SIGNATURE
//https://github.com/symfony/dependency-injection/blob/006f585b01f51188a8b30be06df64d1a489d5dec/LazyProxy/ProxyHelper.php

//https://github.com/symfony/var-dumper/blob/1d3953e627fe4b5f6df503f356b6545ada6351f3/Caster/ArgsStub.php#L52
//https://github.com/symfony/var-dumper/blob/8d1d0a0da905b1332b5bb1fe1b49f302ca280938/Caster/ReflectionCaster.php#L351

// TODO : renommer la classe en BadArgumentException ou directement InvalidArgumentException ????
class ArgumentException extends InjectorException
{
    protected const EXCEPTION_MESSAGE = 'Something is wrong with argument "%s" when calling "%s"%s.';

    public function __construct(ReflectionFunctionAbstract $reflection, string $parameter)
    {
        $function = $reflection->getName();
        /** @psalm-var class-string|null $class */
        $class = $reflection->class ?? null;

        if ($class === null) {
            $method = $function;
            // TODO : https://github.com/symfony/var-dumper/blob/8d1d0a0da905b1332b5bb1fe1b49f302ca280938/Caster/ReflectionCaster.php#L45
            if (substr($method, -9) === '{closure}') {
                $method = $this->getClosureSignature($reflection);
            }
        } else {
            $method = "{$class}::{$function}";
        }

        $fileName = $reflection->getFileName();
        $line = $reflection->getStartLine();

        $fileAndLine = '';
        if (! empty($fileName)) {
            $fileAndLine = " in \"$fileName\" at line $line";
        }

        parent::__construct(sprintf((string) static::EXCEPTION_MESSAGE, $parameter, $method, $fileAndLine));
    }

    private function getClosureSignature(ReflectionFunctionAbstract $reflection): string
    {
        $closureParameters = [];
        $append = static function (string &$parameterString, bool $condition, string $postfix): void {
            if ($condition) {
                $parameterString .= $postfix;
            }
        };
        foreach ($reflection->getParameters() as $parameter) {
            $parameterString = '';
            /** @var ReflectionNamedType|ReflectionUnionType|null $type */
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType) {
                $append($parameterString, $parameter->allowsNull(), '?');
                $parameterString .= $type->getName() . ' ';
            } elseif ($type instanceof ReflectionUnionType) {
                /** @var ReflectionNamedType[] $types */
                $types = $type->getTypes();
                $parameterString .= implode('|', array_map(
                    static fn (ReflectionNamedType $r) => $r->getName(),
                    $types
                )) . ' ';
            }
            $append($parameterString, $parameter->isPassedByReference(), '&');
            $append($parameterString, $parameter->isVariadic(), '...');
            $parameterString .= '$' . $parameter->name;

            // TODO : https://github.com/symfony/var-dumper/blob/8d1d0a0da905b1332b5bb1fe1b49f302ca280938/Caster/ReflectionCaster.php#L374
            if ($parameter->isDefaultValueAvailable()) {
                $parameterString .= ' = ' . var_export($parameter->getDefaultValue(), true);
            }
            $closureParameters[] = $parameterString;
        }

        return 'function (' . implode(', ', $closureParameters) . ')';
    }
}
