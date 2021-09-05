<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\Exception\InjectorException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionObject;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Closure;
use RuntimeException;
use ReflectionFunctionAbstract;
use InvalidArgumentException;
use Throwable;

use ReflectionMethod;
use ReflectionException;

use Chiron\Injector\Reflection;

//https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L531
//https://github.com/thephpleague/container/blob/82a57588c630663d2600f046753b23ab6dcda9b5/src/Argument/ArgumentResolverTrait.php#L66

// TODO : regarder l'utilisation du isBuiltinType()
//https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L491

//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L500
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L662

// TODO : utiliser un "cache" pour la reflection des paramétres de la méthode histoire de gagner du temps :
//https://github.com/qunity/dependency-injection/blob/42c5eaac9d184d9db2e717ddf4f1c30b65a7ba91/src/DependencyInjection/Helper/Reflection.php#L56
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L502

// TODO : gérer le reflectionuniontype;;class du PHP8
//https://github.com/qunity/dependency-injection/blob/42c5eaac9d184d9db2e717ddf4f1c30b65a7ba91/src/DependencyInjection/Processor/CollectArguments.php#L132
//https://github.com/spiral/framework/blob/1a8851523ad1eb62bcbb50be7eff47646c711692/src/Core/src/Container.php#L110
//https://github.com/romanzipp/DTO/blob/5adff07cb90a578f5ee3785d1242932bb1b221dd/src/Types/UnionType.php#L21
//https://github.com/IMPHP/reflect/blob/ffad353065144068005b0b018345d6226ba3558f/src/reflect/ReflectUnionType.php
//https://github.com/suvera/winter-boot/blob/06d06230054faa155c5d585e2ac996b08621cf4b/src/reflection/ReflectionUtil.php#L100

//https://github.com/yiisoft/injector/blob/master/src/Injector.php#L121

//https://github.com/illuminate/container/blob/master/BoundMethod.php#L158

//https://github.com/auraphp/Aura.Di/blob/4.x/src/Resolver/Resolver.php#L268

//https://github.com/symfony/symfony/blob/e60a876201b5b306d0c81a24d9a3db997192079c/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php#L188

// TODO : renommer en DependenciesResolverTrait ou DependencyResolverTrait ???? ou ArgumentResolverTrait
trait ParameterResolverTrait
{
    //https://github.com/researchgate/injektor/blob/master/src/rg/injektor/DependencyInjectionContainer.php#L768
    // TODO : il faudrait pas changer le type ReflectionFunctionAbstract en ReflectionMethod (car on ne va pas vraiment dait de reflection sur une fonction globale) ????
    // TODO : renommer en resolveDependencies() ???
    //https://github.com/thephpleague/container/blob/82a57588c630663d2600f046753b23ab6dcda9b5/src/Argument/ArgumentResolverTrait.php#L66
    // TODO : exemple pour gérer les paramétres qui ne sont pas avec un tableau associatif : https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L127
    protected function resolveParameters(?ReflectionFunctionAbstract $reflection = null, array $parameters = []): array
    {
        $arguments = [];

        // In case the constructor is not reflected, the $reflection will be null.
        if (! $reflection) {
            return $arguments;
        }

        foreach ($reflection->getParameters() as $parameter) {

            try {


                //Information we need to know about argument in order to resolve it's value
                $name = $parameter->getName();
                $class = $parameter->getClass();



            } catch (ReflectionException $e) {

                //throw new CannotResolveException($parameter);

                // TODO : Remplacer le $fil et $line de l'exception avec le fichier php de la classe qui a levé l'exception ca sera plus clair dans le debuger lorsqu'on affichera le "snipet" du code.

                //Possibly invalid class definition or syntax error
                throw new InjectorException(sprintf('Invalid value for parameter %s', Reflection::toString($parameter)), $e->getCode(), $e);
                //throw new InvocationException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}", $e->getCode());
                //throw new InvocationException("Unresolvable dependency resolving [$parameter] in function " . $parameter->getDeclaringClass()->getName() . '::' . $parameter->getDeclaringFunction()->getName(), $e->getCode());
            }

            if (isset($parameters[$name]) && is_object($parameters[$name])) {
                //Supplied by user as object
                $arguments[] = $parameters[$name];
                continue;
            }
            //No declared type or scalar type or array
            if (empty($class)) {
                //Provided from outside
                if (array_key_exists($name, $parameters)) {
                    //Make sure it's properly typed
                    $this->assertType($parameter, $parameters[$name]);
                    $arguments[] = $parameters[$name];
                    continue;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    //Default value
                    //$arguments[] = $parameter->getDefaultValue();
                    $arguments[] = Reflection::getParameterDefaultValue($parameter); // TODO : attention car cette méthode peut lever un ReflectionException !!! qui ne sera donc pas catché, il faudrait le catcher et convertir en CannotResolveException pour faire un throw !!!!
                    continue;
                }
                //Unable to resolve scalar argument value
                throw new CannotResolveException($parameter);
            }

            try {
                //Requesting for contextual dependency
                $arguments[] = $this->container->get($class->getName());
                continue;
            } catch (ContainerExceptionInterface $e) {
                if ($parameter->isOptional()) {
                    //This is optional dependency, skip
                    $arguments[] = null;
                    continue;
                }
                throw $e; // TODO : il faudrait plutot renvoyer une CannotResolveException ou une DependencyException du genre : https://github.com/PHP-DI/PHP-DI/blob/78278800b18e7c5582fd4d4e629715f5eebbfcc0/src/Definition/Resolver/ObjectCreator.php#L147
            }
        }

        // TODO : lever une exception si le parametre n'est pas optionnel et qu'on n'a pas réussi à le résoudre !!!! https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L177

        return $arguments;
    }

    /**
     * Assert that given value are matched parameter type.
     *
     * @param \ReflectionParameter        $parameter
     * @param mixed                       $value
     *
     * @throws CannotResolveException
     */
    //https://github.com/symfony/dependency-injection/blob/5.3/Compiler/CheckTypeDeclarationsPass.php#L267
    //https://github.com/symfony/dependency-injection/blob/5.3/Tests/Compiler/CheckTypeDeclarationsPassTest.php
    private function assertType(ReflectionParameter $parameter, $value): void
    {
        if ($value === null) {
            if (!$parameter->isOptional() &&
                !($parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() === null)
            ) {
                throw new CannotResolveException($parameter);
            }
            return;
        }

        // TODO : attention il me semble que en php8 le getType retournera le pint d'interrogation en cas de nullable, exemple ?int ou ?string et par défaut si on a rien précisé il retourne "mixed" et pas null (ce point est quand même à vérifier !!!)
        // TODO : utiliser la méthode hasType()
        $type = $parameter->getType();

        if ($type === null) {
            return;
        }

        // TODO : on devrait aussi vérifier que la classe est identique, et vérifier aussi le type string pour que cette méthode soit plus générique. Vérifier ce qui se passe si on fait pas cette vérification c'est à dire appeller une fonction avec des paramétres qui n'ont pas le bon typehint !!!!
        $typeName = $type->getName(); // TODO : attention ca ne va fonctionner que si c'est un ReflectionNamedType !!!!
        if ($typeName == 'array' && !is_array($value)) {
            throw new CannotResolveException($parameter);
        }
        if (($typeName == 'int' || $typeName == 'float') && !is_numeric($value)) {
            throw new CannotResolveException($parameter);
        }
        if ($typeName == 'bool' && !is_bool($value) && !is_numeric($value)) {
            throw new CannotResolveException($parameter);
        }
    }
}
