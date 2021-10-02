<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class InvalidParameterTypeException extends InjectorException
{
    public function __construct(string $type, ReflectionParameter $parameter)
    {
        $acceptedType = $parameter->getType();
        $acceptedType = $acceptedType instanceof ReflectionNamedType ? $acceptedType->getName() : (string) $acceptedType;
        $this->code = $type;

        $function = $parameter->getDeclaringFunction();
        $functionName = $function instanceof ReflectionMethod
            ? sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName())
            : $function->getName();

        parent::__construct(sprintf('Argument %d of "%s()" accepts "%s", "%s" passed.', $parameter->getPosition() + 1, $functionName, $acceptedType, $type));
    }
}
