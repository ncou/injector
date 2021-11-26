<?php

declare(strict_types=1);

namespace Chiron\Injector\Exception;

use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function strlen;

//https://github.com/symfony/http-kernel/blob/6.0/Controller/ControllerResolver.php#36
//https://github.com/symfony/http-kernel/blob/6.0/Controller/ControllerResolver.php#139
//https://github.com/symfony/http-kernel/blob/6.0/Tests/Controller/ControllerResolverTest.php#L157
//https://github.com/symfony/http-kernel/blob/6.0/Tests/Controller/ControllerResolverTest.php#L173

// Passer la classe en final ????
// TODO : renommer en InvalidCallableException::class ??? + changer le début du message d'erreur par "Invalid callable : xxxxxxxx"
class NotCallableException extends InjectorException
{
    /**
     * @param mixed $callable Invalid callable value.
     */
    public function __construct($callable)
    {
        $message = sprintf('Input is not callable : %s', self::getCallableError($callable));

        parent::__construct($message);
    }

    /**
     * @param mixed $callable Invalid callable value.
     *
     * @return string Detailed error message.
     */
    private function getCallableError($callable): string
    {
        if (\is_string($callable)) {
            return sprintf('"%s" is neither a php callable nor a valid container entry.', $callable);
        }

        if (\is_object($callable)) {
            $availableMethods = self::getClassMethodsWithoutMagicMethods($callable);
            $alternativeMsg = $availableMethods ? sprintf(' or use one of the available methods: "%s"', implode('", "', $availableMethods)) : '';

            return sprintf('Controller class "%s" cannot be called without a method name. You need to implement "__invoke"%s.', get_debug_type($callable), $alternativeMsg);
        }

        if (!\is_array($callable)) {
            return sprintf('Invalid type for controller given, expected string, array or object, got "%s".', get_debug_type($callable));
        }

        if (!isset($callable[0]) || !isset($callable[1]) || 2 !== \count($callable)) {
            return 'Invalid array callable, expected [controller, method].';
        }

        [$controller, $method] = $callable;
        if (\is_string($controller) && !class_exists($controller)) {
            return sprintf('"%s" is neither a class name nor a valid container entry.', $controller);
        }

        $className = \is_object($controller) ? get_debug_type($controller) : $controller;

        // TODO : attention je pense que si on passe en nom de méthode __call ou __callStatic la fonction method_exists ne fonctionnera pas bien !!!!
        // TODO : attention depuis la version 7.4 les méthodes privées suite à un extends ne sont plus détectées par l'appel à method_exists !!!!
        if (method_exists($controller, $method)) {
            $reflection = new \ReflectionMethod($controller, $method);

            if ($reflection->isPublic() === false) {
                return sprintf('Method "%s" on class "%s" should be public.', $method, $className);
            }

            if (\is_string($controller) && $reflection->isStatic() === false) {
                return sprintf('Non-static method "%s" on class "%s" should not be called statically.', $method, $className);
            }
        }

        // TODO : améliorer l'algo de levenshtein ???? https://github.com/nette/utils/blob/a828903f85bb513e51ba664b44b61f20d812cf20/src/Utils/Helpers.php#L69
        $collection = self::getClassMethodsWithoutMagicMethods($controller);
        $alternatives = [];
        foreach ($collection as $item) {
            $lev = levenshtein($method, $item);

            if ($lev <= \strlen($method) / 3 || str_contains($item, $method)) {
                $alternatives[] = $item;
            }
        }
        asort($alternatives);

        $message = sprintf('Expected method "%s" on class "%s"', $method, $className);

        if (\count($alternatives) > 0) {
            $message .= sprintf(', did you mean "%s"?', implode('", "', $alternatives));
        } elseif (\count($collection) > 0) {
            $message .= sprintf('. Available methods: "%s".', implode('", "', $collection));
        } else {
            $message .= '.';
        }

        return $message;
    }

    private static function getClassMethodsWithoutMagicMethods($classOrObject): array
    {
        $methods = get_class_methods($classOrObject);

        return array_filter($methods, function (string $method) {
            return strncmp($method, '__', 2) !== 0;
        });
    }
}
