<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

// TODO : exemples pour afficher une closure :

// Exemple A
//return 'Closure ('.basename($reflection->getFileName()).':'.$reflection->getStartLine().')';


// Exemple B
//$className = null;
//if (null !== $className = $reflection->getClosureScopeClass()) {
//    $className = '\\'.trim($className->getName(), '\\');
//}

// Exemple C
/*
return sprintf('Closure at %s[%s:%s]',
    $refFunction->getFileName(),
    $refFunction->getStartLine(),
    $refFunction->getEndLine()
);*/

// Ou alors pour récupérer la signature :
// https://github.com/symfony/symfony/blob/61ecf9bf6a934c5df680008daa7b6642742f8bf2/src/Symfony/Component/VarDumper/Caster/ExceptionCaster.php#L328
// https://github.com/symfony/symfony/blob/61ecf9bf6a934c5df680008daa7b6642742f8bf2/src/Symfony/Component/VarDumper/Caster/ReflectionCaster.php#L351
// https://github.com/symfony/symfony/blob/61ecf9bf6a934c5df680008daa7b6642742f8bf2/src/Symfony/Component/VarDumper/Caster/ReflectionCaster.php#L193

// TODO : ajouter le "... in file XXX at line XXX" dans le texte de l'exception.
class InvalidParameterTypeException extends InjectorException
{
    // mixed $value
    public function __construct(ReflectionParameter $parameter, $value)
    {
        $type = is_object($value) ? get_class($value) : get_debug_type($value);

        $this->code = $type; // TODO : c'est pas beau améliorer ce code !!!!

        $acceptedType = $parameter->getType();
        $acceptedType = $acceptedType instanceof ReflectionNamedType ? $acceptedType->getName() : (string) $acceptedType;

        $function = $parameter->getDeclaringFunction();
        $functionName = $function instanceof ReflectionMethod
            ? sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName())
            : $function->getName();

/*
        $fileAndLine = '';
        if ($fileName !== false) {
            $fileAndLine = " in \"$fileName\" at line $line";
        }
*/

        parent::__construct(sprintf('Parameter %d of "%s()" accepts "%s", "%s" passed.', $parameter->getPosition() + 1, $functionName, $acceptedType, $type));
    }
}
