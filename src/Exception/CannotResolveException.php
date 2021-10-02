<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use ReflectionMethod;
use ReflectionParameter;

//https://github.com/spiral/core/blob/86ffeac422f2f368a890ccab71cf6a8b20668176/src/Exception/Container/ArgumentException.php

//https://github.com/symfony/dependency-injection/blob/999cfcf6400502bbc145b2bf36935770770ba6ca/Exception/InvalidParameterTypeException.php#L20

// TODO : renommer en CannotResolveParameterException ???? ou ParameterResolveException. ou renommer en ArgumentException ou ParameterException ou CannotResolveArgumentException
class CannotResolveException extends InjectorException
{
    /**
     * @param string $parameter
     */
    public function __construct(ReflectionParameter $parameter)
    {
        $function = $parameter->getDeclaringFunction();
        $location = $function->getName();

        if ($function instanceof ReflectionMethod) {
            $location = $function->getDeclaringClass()->getName() . '::' . $location;
        }

        // If the class is defined in the PHP core or in a PHP extension, getFileName return false.
        /*
        if ($function->getFileName() !== false) {
            $this->file = $function->getFileName();
            $this->line = $function->getStartLine();
        }*/

        $this->message = sprintf('Cannot resolve a value for parameter "$%s" in callable "%s"', $parameter->getName(), $location);
    }
}
