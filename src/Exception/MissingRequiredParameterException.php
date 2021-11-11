<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use ReflectionMethod;
use ReflectionParameter;

//https://github.com/spiral/core/blob/86ffeac422f2f368a890ccab71cf6a8b20668176/src/Exception/Container/ArgumentException.php

//https://github.com/symfony/dependency-injection/blob/999cfcf6400502bbc145b2bf36935770770ba6ca/Exception/InvalidParameterTypeException.php#L20

// TODO : passer l'exception en final ????
// TODO : renommer en DependencyException ?
class MissingRequiredParameterException extends InjectorException
{
    public function __construct(ReflectionParameter $parameter)
    {
        $function = $parameter->getDeclaringFunction();
        $location = $function->getName();

        if ($function instanceof ReflectionMethod) {
            $location = $function->getDeclaringClass()->getName() . '::' . $location;
        }

        // TODO : faire un basename($function->getFileName()) ????
        $fileName = $function->getFileName();
        $line = $function->getStartLine();

        $fileAndLine = '';
        if ($fileName !== false) {
            $fileAndLine = " in \"$fileName\" at line $line";
        }

        // TODO : faire plutot un parent::__construct(sprintf((string)static::EXCEPTION_MESSAGE, $parameter, $method, $fileAndLine));
        $this->message = sprintf('Missing required value for parameter "$%s" when calling "%s"%s.', $parameter->getName(), $location, $fileAndLine); // TODO : utiliser un protected const pour le message ou alors un private si on passe l'exception en final !!!!
    }
}
